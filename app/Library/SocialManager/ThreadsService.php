<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class ThreadsService
{
    private string $appId;
    private string $appSecret;
    private string $redirectUri;
    private string $baseUrl;
    private mixed $scopes;
    private string $platform;
    private mixed $supportedMediaTypes;
    private string $graphVersion;

    public function __construct()
    {
        $platForm = SocialAccount::where('slug', 'threads')->first();
        $this->baseUrl = $platForm?->url ?: 'https://graph.threads.net';
        $this->scopes = $platForm?->scopes ?? ['threads_basic', 'threads_content_publish', 'threads_manage_insights', 'threads_manage_replies'];
        
        // Threads uses Meta infrastructure
        $this->appId = config('services.meta.app_id', '');
        $this->appSecret = config('services.meta.app_secret', '');
        $this->redirectUri = config('services.threads.redirect_uri', '');
        $this->graphVersion = config('services.meta.graph_version', 'v1.0');
        
        $this->platform = 'threads';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['image', 'video', 'text'];
    }

    public function getPlatform(): string
    {
        return 'threads';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Generate Threads OAuth authorization URL (uses Meta OAuth)
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        $scopes = empty($scopes) ? Helper::parseScopes($this->scopes) : $scopes;
        
        $params = [
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'state' => csrf_token(),
        ];

        return "https://threads.net/oauth/authorize?" . http_build_query($params);
    }

    /**
     * Exchange code for token
     */
    public function exchangeCodeForToken(string $code): array
    {
        $data = [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ];

        $url = "https://graph.threads.net/{$this->graphVersion}/oauth/access_token";
        $response = Helper::makeHttpRequest('POST', $url, $data, ['Content-Type' => 'application/x-www-form-urlencoded'], false, $this->platform);

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
            'grant_type' => 'th_refresh_token',
            'access_token' => $refreshToken,
        ];

        $url = "https://graph.threads.net/{$this->graphVersion}/oauth/access_token";
        return Helper::makeHttpRequest('POST', $url, $data, ['Content-Type' => 'application/x-www-form-urlencoded'], false, $this->platform);
    }

    /**
     * Get user profile
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        $params = [
            'fields' => 'id,username,name,threads_profile_picture_url,threads_biography'
        ];

        $response = Helper::makeHttpRequest('GET', "{$this->baseUrl}/{$this->graphVersion}/me", $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        $user = $response['body'] ?? [];

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['id'] ?? null,
                'username' => $user['username'] ?? null,
                'display_name' => $user['name'] ?? null,
                'profile_picture' => $user['threads_profile_picture_url'] ?? null,
                'bio_description' => $user['threads_biography'] ?? null,
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

            // Threads media limits (similar to Instagram)
            if (str_starts_with($mimeType, 'image/') && $fileSize > 30 * 1024 * 1024) {
                $errors[] = "Image exceeds 30MB limit";
            }

            if (str_starts_with($mimeType, 'video/') && $fileSize > 1 * 1024 * 1024 * 1024) {
                $errors[] = "Video exceeds 1GB limit";
            }
        }

        // Text limit: 500 characters
        $textLength = strlen($metadata['description'] ?? '');
        if ($textLength > 500) {
            $errors[] = "Text exceeds Threads' 500 character limit";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => []
        ];
    }

    /**
     * Create Threads post
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        try {
            $headers = [
                'Authorization' => "Bearer {$account->access_token}",
                'Content-Type' => 'application/json'
            ];

            $userId = $account->identifier;
            $text = Helper::truncateText($content['description'] ?? $post->description ?? '', 500);

            // Step 1: Create media container
            $containerData = [
                'media_type' => 'TEXT',
                'text' => $text,
            ];

            // If media present, requires public URL (like Instagram)
            $postFiles = $post->postFiles;
            if ($postFiles && $postFiles->isNotEmpty()) {
                $firstFile = $postFiles->first();
                $mediaUrl = $content['media_url'] ?? '';
                
                if (empty($mediaUrl)) {
                    return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Threads requires a public media URL. Please provide media_url in content.');
                }

                if (str_starts_with($firstFile->mime_type, 'video/')) {
                    $containerData['media_type'] = 'VIDEO';
                    $containerData['video_url'] = $mediaUrl;
                } else {
                    $containerData['media_type'] = 'IMAGE';
                    $containerData['image_url'] = $mediaUrl;
                }
            }

            $containerResponse = Helper::makeHttpRequest('POST', "{$this->baseUrl}/{$this->graphVersion}/{$userId}/threads", json_encode($containerData), $headers, false, $this->platform);

            if ($containerResponse['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($containerResponse['header_code'], $containerResponse['body'] ?? 'Failed to create Threads container');
            }

            $creationId = $containerResponse['body']['id'] ?? null;
            if (!$creationId) {
                return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'No creation ID received');
            }

            // Step 2: Publish container
            $publishData = [
                'creation_id' => $creationId,
            ];

            $publishResponse = Helper::makeHttpRequest('POST', "{$this->baseUrl}/{$this->graphVersion}/{$userId}/threads_publish", json_encode($publishData), $headers, false, $this->platform);

            if ($publishResponse['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($publishResponse['header_code'], $publishResponse['body'] ?? 'Failed to publish Threads post');
            }

            $threadId = $publishResponse['body']['id'] ?? 'unknown';

            return [
                'header_code' => ResponseAlias::HTTP_OK,
                'body' => [
                    'platform_post_id' => $threadId,
                    'platform_url' => "https://www.threads.net/@{$account->username}/post/{$threadId}",
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
            'threads_basic' => 'Read basic profile information',
            'threads_content_publish' => 'Create and publish Threads posts',
            'threads_manage_insights' => 'Read Threads insights and analytics',
            'threads_manage_replies' => 'Read and manage replies to your Threads',
            'threads_read_replies' => 'Read replies to your Threads',
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
            'max_image_size_mb' => 30,
            'max_video_size_mb' => 1000,
            'max_video_duration_seconds' => 300,
            'supported_image_formats' => ['jpg', 'jpeg', 'png'],
            'supported_video_formats' => ['mp4'],
            'requires_public_url' => true,
        ];
    }

    /**
     * Get post insights
     */
    public function getPostAnalytics(CustomerAccount $account, string $platformPostId): array
    {
        $headers = ['Authorization' => "Bearer {$account->access_token}"];
        $params = [
            'metric' => 'views,likes,replies,reposts,quotes'
        ];

        $response = Helper::makeHttpRequest('GET', "{$this->baseUrl}/{$this->graphVersion}/{$platformPostId}/insights", $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get insights');
        }

        return $response;
    }
}
