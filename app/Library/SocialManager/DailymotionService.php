<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class DailymotionService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $baseUrl;
    private mixed $scopes;
    private string $platform;
    private mixed $supportedMediaTypes;

    public function __construct()
    {
        $platForm = SocialAccount::where('slug', 'dailymotion')->first();
        $this->baseUrl = $platForm?->url ?: 'https://api.dailymotion.com';
        $this->scopes = $platForm?->scopes ?? ['userinfo', 'manage_videos'];
        $this->clientId = config('services.dailymotion.client_id', '');
        $this->clientSecret = config('services.dailymotion.client_secret', '');
        $this->redirectUri = config('services.dailymotion.redirect_uri', '');
        $this->platform = 'dailymotion';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['video'];
    }

    public function getPlatform(): string
    {
        return 'dailymotion';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Generate Dailymotion OAuth authorization URL
     * Scopes: userinfo, manage_videos, etc.
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        $scopes = empty($scopes) ? (is_array($this->scopes) ? $this->scopes : json_decode($this->scopes, true)) : $scopes;
        
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => csrf_token(),
        ];
        
        return 'https://www.dailymotion.com/oauth/authorize?' . http_build_query($params);
    }

    /**
     * Exchange code for token
     */
    public function exchangeCodeForToken(string $code): array
    {
        $data = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ];

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        
        $response = Helper::makeHttpRequest('POST', 'https://api.dailymotion.com/oauth/token', $data, $headers, false, $this->platform);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to exchange code for token');
        }

        return $response;
    }

    /**
     * Refresh token
     */
    public function refreshToken(string $refreshToken): array
    {
        $data = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
        ];

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        
        return Helper::makeHttpRequest('POST', 'https://api.dailymotion.com/oauth/token', $data, $headers, false, $this->platform);
    }

    /**
     * Get user profile
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $params = [
            'access_token' => $userAccessToken,
            'fields' => 'id,screenname,avatar_large_url,description,followers_count,videos_total'
        ];
        
        $response = Helper::makeHttpRequest('GET', 'https://api.dailymotion.com/me', $params, []);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }
        
        $user = $response['body'] ?? [];
        
        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['id'] ?? null,
                'username' => $user['screenname'] ?? null,
                'display_name' => $user['screenname'] ?? null,
                'profile_picture' => $user['avatar_large_url'] ?? null,
                'bio_description' => $user['description'] ?? null,
                'follower_count' => $user['followers_count'] ?? 0,
                'following_count' => 0,
                'video_count' => $user['videos_total'] ?? 0,
            ]
        ];
    }

    /**
     * Validate content
     */
    public function validateContent(array $mediaFiles, array $metadata = []): array
    {
        $errors = [];

        foreach ($mediaFiles as $filePath) {
            if (!file_exists($filePath)) {
                $errors[] = "File does not exist: {$filePath}";
                continue;
            }

            $mimeType = mime_content_type($filePath);
            if (!str_starts_with($mimeType, 'video/')) {
                $errors[] = "Dailymotion only supports video files";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => []
        ];
    }

    /**
     * Upload video to Dailymotion
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        try {
            $postFile = $post->postFiles->first();
            if (!$postFile) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'No video file found');
            }

            $filePath = Storage::disk('private')->path($postFile->file_path);
            if (!file_exists($filePath)) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Video file not found');
            }

            // Step 1: Get upload URL
            $uploadUrlResponse = Helper::makeHttpRequest('GET', 'https://api.dailymotion.com/file/upload', ['access_token' => $account->access_token], []);
            
            if ($uploadUrlResponse['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($uploadUrlResponse['header_code'], 'Failed to get upload URL');
            }

            $uploadUrl = $uploadUrlResponse['body']['upload_url'] ?? null;
            if (!$uploadUrl) {
                return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'No upload URL received');
            }

            // Step 2: Upload file
            $fileContent = file_get_contents($filePath);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $uploadUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $fileContent,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 300,
            ]);

            $uploadResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($httpCode, 'Video upload failed');
            }

            $uploadData = json_decode($uploadResponse, true);
            $fileUrl = $uploadData['url'] ?? null;

            // Step 3: Create video entry
            $videoData = [
                'access_token' => $account->access_token,
                'url' => $fileUrl,
                'title' => $post->title,
                'description' => $post->description,
                'channel' => 'tech', // Default category
                'published' => 'true',
                'private' => 'false',
            ];

            $createResponse = Helper::makeHttpRequest('POST', 'https://api.dailymotion.com/me/videos', $videoData, [], false, $this->platform);
            
            if ($createResponse['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($createResponse['header_code'], $createResponse['body'] ?? 'Failed to create video');
            }

            $videoId = $createResponse['body']['id'] ?? 'unknown';

            return [
                'header_code' => ResponseAlias::HTTP_OK,
                'body' => [
                    'platform_post_id' => $videoId,
                    'platform_url' => "https://www.dailymotion.com/video/{$videoId}",
                    'status' => 'published'
                ]
            ];

        } catch (\Exception $e) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Publishing failed: ' . $e->getMessage());
        }
    }

    /**
     * Get available scopes
     */
    public function getAvailableScopes(): array
    {
        return [
            'userinfo' => 'Access to user profile information',
            'email' => 'Access to user email',
            'manage_videos' => 'Upload, edit and delete videos',
            'manage_playlists' => 'Create, edit and delete playlists',
            'manage_comments' => 'Post, edit and delete comments',
            'manage_favorites' => 'Add and remove favorites',
            'manage_subscriptions' => 'Subscribe and unsubscribe to users',
            'manage_friends' => 'Add and remove friends',
            'manage_groups' => 'Join and leave groups',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['video'];
    }

    public function getPostingLimits(): array
    {
        return [
            'max_video_size_gb' => 4,
            'max_video_duration_hours' => 24,
            'supported_formats' => ['mp4', 'mov', 'avi', 'wmv'],
        ];
    }
}
