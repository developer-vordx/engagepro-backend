<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class BlueskyService
{
    private string $baseUrl;
    private string $platform;
    private mixed $supportedMediaTypes;

    public function __construct()
    {
        $platForm = SocialAccount::where('slug', 'bluesky')->first();
        $this->baseUrl = $platForm?->url ?: 'https://bsky.social';
        $this->platform = 'bluesky';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['image', 'text'];
    }

    public function getPlatform(): string
    {
        return 'bluesky';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Bluesky uses app password authentication (AT Protocol)
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        return 'https://bsky.app/settings/app-passwords';
    }

    /**
     * Not applicable - uses app password
     */
    public function exchangeCodeForToken(string $code): array
    {
        return $this->errorResponse(ResponseAlias::HTTP_NOT_IMPLEMENTED, 'Bluesky uses app password authentication, not OAuth');
    }

    /**
     * Create session with app password
     */
    public function createSession(string $identifier, string $appPassword): array
    {
        $data = [
            'identifier' => $identifier,
            'password' => $appPassword
        ];

        $url = "{$this->baseUrl}/xrpc/com.atproto.server.createSession";
        $response = Helper::makeHttpRequest('POST', $url, $data, ['Content-Type' => 'application/json'], false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to create session');
        }

        return $response;
    }

    /**
     * Get user profile
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        
        $url = "{$this->baseUrl}/xrpc/com.atproto.server.getSession";
        $response = Helper::makeHttpRequest('GET', $url, [], $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        $session = $response['body'] ?? [];

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $session['did'] ?? null,
                'username' => $session['handle'] ?? null,
                'display_name' => $session['handle'] ?? null,
                'profile_picture' => null,
                'email' => $session['email'] ?? null,
                'follower_count' => 0,
                'following_count' => 0,
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

            // Bluesky image limits: 1MB
            if (str_starts_with($mimeType, 'image/') && $fileSize > 1 * 1024 * 1024) {
                $errors[] = "Image exceeds Bluesky's 1MB limit";
            }

            // Bluesky doesn't support videos yet
            if (str_starts_with($mimeType, 'video/')) {
                $errors[] = "Bluesky doesn't support video uploads yet";
            }
        }

        // Text limit: 300 characters
        $textLength = strlen($metadata['description'] ?? '');
        if ($textLength > 300) {
            $errors[] = "Text exceeds Bluesky's 300 character limit";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => []
        ];
    }

    /**
     * Create post on Bluesky
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        try {
            $headers = [
                'Authorization' => "Bearer {$account->access_token}",
                'Content-Type' => 'application/json'
            ];

            $text = Helper::truncateText($content['description'] ?? $post->description ?? '', 300);
            
            $record = [
                'text' => $text,
                'createdAt' => now()->toIso8601String(),
                '$type' => 'app.bsky.feed.post',
            ];

            // Upload images if present
            $postFiles = $post->postFiles;
            if ($postFiles && $postFiles->isNotEmpty()) {
                $images = [];
                
                foreach ($postFiles->take(4) as $file) { // Max 4 images
                    $filePath = Storage::disk('private')->path($file->file_path);
                    
                    if (file_exists($filePath) && str_starts_with($file->mime_type, 'image/')) {
                        $uploadResult = $this->uploadBlob($filePath, $account->access_token);
                        
                        if ($uploadResult['header_code'] == ResponseAlias::HTTP_OK) {
                            $images[] = [
                                'image' => $uploadResult['body']['blob'] ?? null,
                                'alt' => $post->title ?? ''
                            ];
                        }
                    }
                }

                if (!empty($images)) {
                    $record['embed'] = [
                        '$type' => 'app.bsky.embed.images',
                        'images' => $images
                    ];
                }
            }

            $payload = [
                'repo' => $account->identifier,
                'collection' => 'app.bsky.feed.post',
                'record' => $record
            ];

            $url = "{$this->baseUrl}/xrpc/com.atproto.repo.createRecord";
            $response = Helper::makeHttpRequest('POST', $url, json_encode($payload), $headers, false, $this->platform);

            if ($response['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($response['header_code'], $response['body'] ?? 'Failed to create post');
            }

            $postData = $response['body'] ?? [];

            return [
                'header_code' => ResponseAlias::HTTP_OK,
                'body' => [
                    'platform_post_id' => $postData['uri'] ?? 'unknown',
                    'platform_url' => "https://bsky.app/profile/{$account->username}",
                    'status' => 'published'
                ]
            ];

        } catch (\Exception $e) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Publishing failed: ' . $e->getMessage());
        }
    }

    /**
     * Upload blob (image)
     */
    private function uploadBlob(string $filePath, string $accessToken): array
    {
        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => mime_content_type($filePath)
        ];

        $url = "{$this->baseUrl}/xrpc/com.atproto.repo.uploadBlob";
        $fileContent = file_get_contents($filePath);

        return Helper::makeHttpRequest('POST', $url, $fileContent, $headers, false, $this->platform);
    }

    /**
     * Get available scopes
     */
    public function getAvailableScopes(): array
    {
        return [
            'app_password' => 'App password authentication (no traditional OAuth scopes)',
            'post' => 'Create posts',
            'follow' => 'Follow users',
            'like' => 'Like posts',
            'repost' => 'Repost content',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['image', 'text'];
    }

    public function getPostingLimits(): array
    {
        return [
            'max_text_length' => 300,
            'max_image_size_mb' => 1,
            'max_images_per_post' => 4,
            'supported_image_formats' => ['jpg', 'jpeg', 'png', 'gif'],
            'video_support' => false,
        ];
    }
}
