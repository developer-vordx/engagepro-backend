<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class MastodonService
{
    private string $instanceUrl;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private mixed $scopes;
    private string $platform;
    private mixed $supportedMediaTypes;

    public function __construct()
    {
        $platForm = SocialAccount::where('slug', 'mastodon')->first();
        $this->instanceUrl = $platForm?->url ?: 'https://mastodon.social';
        $this->scopes = $platForm?->scopes ?? ['read', 'write', 'follow', 'push'];
        $this->clientId = config('services.mastodon.client_id', '');
        $this->clientSecret = config('services.mastodon.client_secret', '');
        $this->redirectUri = config('services.mastodon.redirect_uri', '');
        $this->platform = 'mastodon';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['image', 'video', 'text'];
    }

    public function getPlatform(): string
    {
        return 'mastodon';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Generate Mastodon OAuth authorization URL
     * Scopes: read, write, follow, push (write includes write:statuses, write:media, etc.)
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        $scopes = empty($scopes) ? (is_array($this->scopes) ? $this->scopes : json_decode($this->scopes, true)) : $scopes;
        
        $params = [
            'client_id' => $this->clientId,
            'scope' => implode(' ', $scopes),
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'state' => csrf_token(),
        ];
        
        return $this->instanceUrl . '/oauth/authorize?' . http_build_query($params);
    }

    /**
     * Exchange code for token
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
        
        $response = Helper::makeHttpRequest('POST', $this->instanceUrl . '/oauth/token', $data, $headers, false, $this->platform);
        
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
        
        $response = Helper::makeHttpRequest('GET', $this->instanceUrl . '/api/v1/accounts/verify_credentials', [], $headers);
        
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
                'is_locked' => $user['locked'] ?? false,
                'is_bot' => $user['bot'] ?? false,
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

            // Mastodon image limit: 8MB
            if (str_starts_with($mimeType, 'image/') && $fileSize > 8 * 1024 * 1024) {
                $errors[] = "Image exceeds Mastodon's 8MB limit";
            }

            // Mastodon video limit: 40MB
            if (str_starts_with($mimeType, 'video/') && $fileSize > 40 * 1024 * 1024) {
                $errors[] = "Video exceeds Mastodon's 40MB limit";
            }
        }

        // Text limit: 500 characters (default, can vary by instance)
        $textLength = strlen($metadata['description'] ?? '');
        if ($textLength > 500) {
            $errors[] = "Post text exceeds Mastodon's 500 character limit";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => []
        ];
    }

    /**
     * Create toot (post) on Mastodon
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
                'status' => $content['description'] ?? $post->description ?? '',
                'visibility' => 'public', // public, unlisted, private, direct
            ];

            if (!empty($mediaIds)) {
                $payload['media_ids'] = $mediaIds;
            }

            $response = Helper::makeHttpRequest('POST', $this->instanceUrl . '/api/v1/statuses', json_encode($payload), $headers, false, $this->platform);
            
            if ($response['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($response['header_code'], $response['body'] ?? 'Failed to create status');
            }

            $statusData = $response['body'] ?? [];

            return [
                'header_code' => ResponseAlias::HTTP_OK,
                'body' => [
                    'platform_post_id' => $statusData['id'] ?? 'unknown',
                    'platform_url' => $statusData['url'] ?? $this->instanceUrl,
                    'status' => 'published'
                ]
            ];

        } catch (\Exception $e) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Publishing failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload media to Mastodon
     */
    private function uploadMedia(string $filePath, string $accessToken): array
    {
        $headers = [
            'Authorization' => "Bearer {$accessToken}",
        ];

        $data = [
            'file' => new \CURLFile($filePath, mime_content_type($filePath), basename($filePath))
        ];

        return Helper::makeHttpRequest('POST', $this->instanceUrl . '/api/v2/media', $data, $headers, true, $this->platform);
    }

    /**
     * Get available scopes (granular)
     */
    public function getAvailableScopes(): array
    {
        return [
            'read' => 'Read all account data',
            'read:accounts' => 'Read account information',
            'read:blocks' => 'Read blocked users',
            'read:bookmarks' => 'Read bookmarks',
            'read:favourites' => 'Read favourited statuses',
            'read:filters' => 'Read filters',
            'read:follows' => 'Read follows',
            'read:lists' => 'Read lists',
            'read:mutes' => 'Read muted users',
            'read:notifications' => 'Read notifications',
            'read:search' => 'Search',
            'read:statuses' => 'Read statuses',
            'write' => 'Write all account data',
            'write:accounts' => 'Modify account information',
            'write:blocks' => 'Block and unblock users',
            'write:bookmarks' => 'Bookmark and unbookmark statuses',
            'write:conversations' => 'Manage conversations',
            'write:favourites' => 'Favourite and unfavourite statuses',
            'write:filters' => 'Manage filters',
            'write:follows' => 'Follow and unfollow users',
            'write:lists' => 'Manage lists',
            'write:media' => 'Upload media',
            'write:mutes' => 'Mute and unmute users',
            'write:notifications' => 'Clear notifications',
            'write:reports' => 'Report users and statuses',
            'write:statuses' => 'Create, edit, and delete statuses',
            'follow' => 'Follow, unfollow, block, unblock users',
            'push' => 'Receive push notifications',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['image', 'video', 'text'];
    }

    public function getPostingLimits(): array
    {
        return [
            'max_text_length' => 500, // Default, varies by instance
            'max_image_size_mb' => 8,
            'max_video_size_mb' => 40,
            'max_media_attachments' => 4,
            'supported_image_formats' => ['jpg', 'png', 'gif'],
            'supported_video_formats' => ['mp4', 'webm'],
        ];
    }
}
