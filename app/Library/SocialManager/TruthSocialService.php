<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class TruthSocialService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private mixed $scopes;
    private string $platform;
    private mixed $supportedMediaTypes;

    public function __construct()
    {
        $platForm = SocialAccount::where('slug', 'truthsocial')->first();
        // Truth Social is a Mastodon fork
        $this->baseUrl = $platForm?->url ?: 'https://truthsocial.com';
        $this->scopes = $platForm?->scopes ?? ['read', 'write', 'write:statuses', 'write:media', 'follow'];
        $this->clientId = config('services.truthsocial.client_id', '');
        $this->clientSecret = config('services.truthsocial.client_secret', '');
        $this->redirectUri = config('services.truthsocial.redirect_uri', '');
        $this->platform = 'truthsocial';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['image', 'video', 'text'];
    }

    public function getPlatform(): string
    {
        return 'truthsocial';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Generate Truth Social OAuth URL (Mastodon-compatible)
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        $scopes = empty($scopes) ? Helper::parseScopes($this->scopes) : $scopes;
        
        $params = [
            'client_id' => $this->clientId,
            'scope' => implode(' ', $scopes),
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'state' => csrf_token(),
        ];
        
        return $this->baseUrl . '/oauth/authorize?' . http_build_query($params);
    }

    /**
     * Exchange code for token (Mastodon-compatible)
     */
    public function exchangeCodeForToken(string $code): array
    {
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
        ];

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        
        $response = Helper::makeHttpRequest('POST', $this->baseUrl . '/oauth/token', $data, $headers, false, $this->platform);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to exchange code for token');
        }

        return $response;
    }

    /**
     * Get user profile (Mastodon-compatible)
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        
        $response = Helper::makeHttpRequest('GET', $this->baseUrl . '/api/v1/accounts/verify_credentials', [], $headers);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }
        
        $user = $response['body'] ?? [];
        
        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['id'] ?? null,
                'username' => $user['username'] ?? null,
                'display_name' => $user['display_name'] ?? null,
                'profile_picture' => $user['avatar'] ?? null,
                'bio_description' => strip_tags($user['note'] ?? ''),
                'follower_count' => $user['followers_count'] ?? 0,
                'following_count' => $user['following_count'] ?? 0,
                'post_count' => $user['statuses_count'] ?? 0,
            ]
        ];
    }

    /**
     * Validate content (same as Mastodon)
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

            if (str_starts_with($mimeType, 'image/') && $fileSize > 8 * 1024 * 1024) {
                $errors[] = "Image exceeds 8MB limit";
            }

            if (str_starts_with($mimeType, 'video/') && $fileSize > 40 * 1024 * 1024) {
                $errors[] = "Video exceeds 40MB limit";
            }
        }

        $textLength = strlen($metadata['description'] ?? '');
        if ($textLength > 500) {
            $errors[] = "Text exceeds 500 character limit";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => []
        ];
    }

    /**
     * Publish post (Mastodon-compatible API)
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        try {
            $headers = [
                'Authorization' => "Bearer {$account->access_token}",
                'Content-Type' => 'application/json'
            ];

            $mediaIds = [];
            
            // Upload media if present
            $postFiles = $post->postFiles;
            if ($postFiles && $postFiles->isNotEmpty()) {
                foreach ($postFiles as $file) {
                    $filePath = Storage::disk('private')->path($file->file_path);
                    if (file_exists($filePath)) {
                        $mediaResult = $this->uploadMedia($filePath, $account->access_token);
                        if ($mediaResult['header_code'] == ResponseAlias::HTTP_OK && isset($mediaResult['body']['id'])) {
                            $mediaIds[] = $mediaResult['body']['id'];
                        }
                    }
                }
            }

            $payload = [
                'status' => Helper::truncateText($content['description'] ?? $post->description ?? '', 500),
                'visibility' => 'public',
            ];

            if (!empty($mediaIds)) {
                $payload['media_ids'] = $mediaIds;
            }

            $response = Helper::makeHttpRequest('POST', $this->baseUrl . '/api/v1/statuses', json_encode($payload), $headers, false, $this->platform);
            
            if ($response['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($response['header_code'], $response['body'] ?? 'Failed to create status');
            }

            $statusData = $response['body'] ?? [];

            return [
                'header_code' => ResponseAlias::HTTP_OK,
                'body' => [
                    'platform_post_id' => $statusData['id'] ?? 'unknown',
                    'platform_url' => $statusData['url'] ?? $this->baseUrl,
                    'status' => 'published'
                ]
            ];

        } catch (\Exception $e) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Publishing failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload media (Mastodon-compatible)
     */
    private function uploadMedia(string $filePath, string $accessToken): array
    {
        $headers = ['Authorization' => "Bearer {$accessToken}"];

        $data = [
            'file' => new \CURLFile($filePath, mime_content_type($filePath), basename($filePath))
        ];

        return Helper::makeHttpRequest('POST', $this->baseUrl . '/api/v2/media', $data, $headers, true, $this->platform);
    }

    public function getAvailableScopes(): array
    {
        return [
            'read' => 'Read all account data',
            'write' => 'Write all account data',
            'write:statuses' => 'Create posts',
            'write:media' => 'Upload media',
            'follow' => 'Follow/unfollow users',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['image', 'video', 'text'];
    }

    public function getPostingLimits(): array
    {
        return [
            'max_text_length' => 500,
            'max_image_size_mb' => 8,
            'max_video_size_mb' => 40,
            'max_media_attachments' => 4,
            'note' => 'Truth Social is a Mastodon fork with compatible API',
        ];
    }
}
