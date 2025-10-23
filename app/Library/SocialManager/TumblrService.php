<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class TumblrService
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
        $platForm = SocialAccount::where('slug', 'tumblr')->first();
        $this->baseUrl = $platForm?->url ?: 'https://api.tumblr.com/v2';
        $this->scopes = $platForm?->scopes ?? ['basic', 'write'];
        $this->clientId = config('services.tumblr.client_id', '');
        $this->clientSecret = config('services.tumblr.client_secret', '');
        $this->redirectUri = config('services.tumblr.redirect_uri', '');
        $this->platform = 'tumblr';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['image', 'video', 'text'];
    }

    public function getPlatform(): string
    {
        return 'tumblr';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Generate Tumblr OAuth 2.0 authorization URL
     * Scopes: basic, write, offline_access
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        $scopes = empty($scopes) ? $this->scopes : $scopes;
        
        // Ensure scopes is an array
        if (is_string($scopes)) {
            $scopes = Helper::parseScopes($scopes);
        }
        
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'state' => csrf_token(),
            'redirect_uri' => $this->redirectUri,
        ];
        
        return 'https://www.tumblr.com/oauth2/authorize?' . http_build_query($params);
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
        
        $response = Helper::makeHttpRequest('POST', 'https://api.tumblr.com/v2/oauth2/token', $data, $headers, false, $this->platform);
        
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
        
        return Helper::makeHttpRequest('POST', 'https://api.tumblr.com/v2/oauth2/token', $data, $headers, false, $this->platform);
    }

    /**
     * Get user info
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        
        $response = Helper::makeHttpRequest('GET', 'https://api.tumblr.com/v2/user/info', [], $headers);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }
        
        $user = $response['body']['response']['user'] ?? [];
        
        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['name'] ?? null,
                'username' => $user['name'] ?? null,
                'display_name' => $user['name'] ?? null,
                'profile_picture' => null,
                'follower_count' => 0,
                'following_count' => $user['following'] ?? 0,
                'blog_count' => count($user['blogs'] ?? []),
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
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => []
        ];
    }

    /**
     * Create post on Tumblr blog
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        try {
            $headers = [
                'Authorization' => "Bearer {$account->access_token}",
                'Content-Type' => 'application/json'
            ];

            // Tumblr requires blog identifier
            $blogIdentifier = $content['blog'] ?? $account->username;
            if (!$blogIdentifier) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Tumblr blog identifier is required');
            }

            $postFiles = $post->postFiles;
            $postType = 'text';
            $payload = [
                'content' => [],
                'tags' => implode(',', $post->tags ?? []),
                'state' => 'published',
            ];

            if ($postFiles && $postFiles->isNotEmpty()) {
                $firstFile = $postFiles->first();
                $mimeType = $firstFile->mime_type;

                if (str_starts_with($mimeType, 'image/')) {
                    $postType = 'photo';
                    // Upload image as base64
                    $filePath = Storage::disk('private')->path($firstFile->file_path);
                    $imageData = base64_encode(file_get_contents($filePath));
                    $payload['content'][] = [
                        'type' => 'image',
                        'media' => [
                            [
                                'type' => 'image/jpeg',
                                'identifier' => $imageData,
                            ]
                        ]
                    ];
                } elseif (str_starts_with($mimeType, 'video/')) {
                    $postType = 'video';
                    // Tumblr video uploads are complex; typically require external URL
                    $payload['content'][] = [
                        'type' => 'video',
                        'provider' => 'tumblr',
                        'url' => $content['video_url'] ?? '',
                    ];
                }
            }

            // Add text content
            $payload['content'][] = [
                'type' => 'text',
                'text' => $post->description ?? '',
            ];

            $response = Helper::makeHttpRequest('POST', "https://api.tumblr.com/v2/blog/{$blogIdentifier}/posts", json_encode($payload), $headers, false, $this->platform);
            
            if ($response['header_code'] != ResponseAlias::HTTP_CREATED && $response['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($response['header_code'], $response['body'] ?? 'Failed to create post');
            }

            $postData = $response['body']['response'] ?? [];

            return [
                'header_code' => ResponseAlias::HTTP_OK,
                'body' => [
                    'platform_post_id' => $postData['id'] ?? 'unknown',
                    'platform_url' => "https://{$blogIdentifier}.tumblr.com/",
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
            'basic' => 'Read basic user information',
            'write' => 'Create posts',
            'offline_access' => 'Access offline via refresh token',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['image', 'video', 'text'];
    }

    public function getPostingLimits(): array
    {
        return [
            'max_posts_per_day' => 250,
            'max_tags_per_post' => 30,
            'max_image_size_mb' => 10,
        ];
    }
}
