<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class VimeoService
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
        $platForm = SocialAccount::where('slug', 'vimeo')->first();
        $this->baseUrl = $platForm?->url ?: 'https://api.vimeo.com';
        $this->scopes = $platForm?->scopes ?? ['public', 'private', 'upload', 'edit', 'delete'];
        $this->clientId = config('services.vimeo.client_id', '');
        $this->clientSecret = config('services.vimeo.client_secret', '');
        $this->redirectUri = config('services.vimeo.redirect_uri', '');
        $this->platform = 'vimeo';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['video'];
    }

    public function getPlatform(): string
    {
        return 'vimeo';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Generate Vimeo OAuth authorization URL
     * Scopes: public, private, purchased, create, edit, delete, interact, upload, etc.
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        $scopes = empty($scopes) ? $this->scopes : $scopes;
        
        // Ensure scopes is an array
        if (is_string($scopes)) {
            $scopes = Helper::parseScopes($scopes);
        }
        
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => csrf_token(),
        ];
        
        return 'https://api.vimeo.com/oauth/authorize?' . http_build_query($params);
    }

    /**
     * Exchange code for token
     */
    public function exchangeCodeForToken(string $code): array
    {
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ];

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => Helper::buildBasicAuthHeader($this->clientId, $this->clientSecret)
        ];
        
        $response = Helper::makeHttpRequest('POST', 'https://api.vimeo.com/oauth/access_token', $data, $headers, false, $this->platform);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to exchange code for token');
        }

        return $response;
    }

    /**
     * Get user profile
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        
        $response = Helper::makeHttpRequest('GET', 'https://api.vimeo.com/me', [], $headers);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }
        
        $user = $response['body'] ?? [];
        
        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['uri'] ?? null,
                'username' => $user['name'] ?? null,
                'display_name' => $user['name'] ?? null,
                'profile_picture' => $user['pictures']['sizes'][0]['link'] ?? null,
                'bio_description' => $user['bio'] ?? null,
                'follower_count' => $user['metadata']['connections']['followers']['total'] ?? 0,
                'following_count' => $user['metadata']['connections']['following']['total'] ?? 0,
                'video_count' => $user['metadata']['connections']['videos']['total'] ?? 0,
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

            $fileSize = filesize($filePath);
            $mimeType = mime_content_type($filePath);

            // Vimeo supports video files
            if (!str_starts_with($mimeType, 'video/')) {
                $errors[] = "Vimeo only supports video files";
            }

            // Basic tier: 500MB per week upload limit
            if ($fileSize > 500 * 1024 * 1024) {
                $errors[] = "Video exceeds recommended 500MB size (basic tier limit)";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => []
        ];
    }

    /**
     * Upload video to Vimeo
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        try {
            $headers = [
                'Authorization' => "Bearer {$account->access_token}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/vnd.vimeo.*+json;version=3.4'
            ];

            $postFile = $post->postFiles->first();
            if (!$postFile) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'No video file found');
            }

            $filePath = Storage::disk('private')->path($postFile->file_path);
            if (!file_exists($filePath)) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Video file not found');
            }

            $fileSize = filesize($filePath);

            // Step 1: Create video entry
            $createPayload = [
                'upload' => [
                    'approach' => 'tus',
                    'size' => $fileSize
                ],
                'name' => $post->title,
                'description' => $post->description,
                'privacy' => [
                    'view' => 'anybody', // anybody, nobody, contacts, password, disable, unlisted
                    'embed' => 'public'
                ]
            ];

            $createResponse = Helper::makeHttpRequest('POST', 'https://api.vimeo.com/me/videos', json_encode($createPayload), $headers, false, $this->platform);
            
            if ($createResponse['header_code'] != ResponseAlias::HTTP_OK && $createResponse['header_code'] != ResponseAlias::HTTP_CREATED) {
                return $this->errorResponse($createResponse['header_code'], $createResponse['body'] ?? 'Failed to create video entry');
            }

            $videoData = $createResponse['body'] ?? [];
            $uploadLink = $videoData['upload']['upload_link'] ?? null;
            $videoUri = $videoData['uri'] ?? null;

            if (!$uploadLink) {
                return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'No upload link received from Vimeo');
            }

            // Step 2: Upload video file using TUS protocol (resumable upload)
            // For simplicity, we'll use the upload_link directly
            $fileContent = file_get_contents($filePath);
            $uploadHeaders = [
                'Tus-Resumable' => '1.0.0',
                'Upload-Offset' => '0',
                'Content-Type' => 'application/offset+octet-stream',
                'Accept' => 'application/vnd.vimeo.*+json;version=3.4'
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $uploadLink,
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_POSTFIELDS => $fileContent,
                CURLOPT_HTTPHEADER => array_map(function($key, $val) { return "{$key}: {$val}"; }, array_keys($uploadHeaders), $uploadHeaders),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 300,
            ]);

            $uploadResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode < 200 || $httpCode >= 300) {
                return $this->errorResponse($httpCode, 'Video upload failed');
            }

            return [
                'header_code' => ResponseAlias::HTTP_OK,
                'body' => [
                    'platform_post_id' => $videoUri ?? 'unknown',
                    'platform_url' => $videoData['link'] ?? 'https://vimeo.com/',
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
            'public' => 'View public videos',
            'private' => 'View private videos',
            'purchased' => 'View purchased videos',
            'create' => 'Create videos, folders, albums, etc.',
            'edit' => 'Edit videos, folders, albums, etc.',
            'delete' => 'Delete videos, folders, albums, etc.',
            'interact' => 'Like, comment on, and follow users',
            'upload' => 'Upload videos',
            'promo_codes' => 'Manage promo codes',
            'stats' => 'View video stats',
            'video_files' => 'Access video files',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['video'];
    }

    public function getPostingLimits(): array
    {
        return [
            'max_video_size_gb' => 128,
            'supported_formats' => ['mp4', 'mov', 'avi', 'wmv', 'flv'],
            'weekly_upload_limit_gb' => 0.5, // Free tier
        ];
    }
}
