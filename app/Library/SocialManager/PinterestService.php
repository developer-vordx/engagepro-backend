<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PinterestService
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
        $platForm = SocialAccount::where('slug', 'pinterest')->first();
        $this->baseUrl = $platForm?->url ?: 'https://api.pinterest.com';
        $this->scopes = $platForm?->scopes ?? ['boards:read', 'boards:write', 'pins:read', 'pins:write', 'user_accounts:read'];
        $this->clientId = config('services.pinterest.client_id', '');
        $this->clientSecret = config('services.pinterest.client_secret', '');
        $this->redirectUri = config('services.pinterest.redirect_uri', '');
        $this->platform = 'pinterest';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['image'];
    }

    public function getPlatform(): string
    {
        return 'pinterest';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Generate Pinterest OAuth 2.0 authorization URL
     * Scopes: boards:read, boards:write, pins:read, pins:write, user_accounts:read
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
            'scope' => implode(',', $scopes),
            'state' => csrf_token(),
        ];
        
        return 'https://www.pinterest.com/oauth/?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
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
        
        $response = Helper::makeHttpRequest('POST', 'https://api.pinterest.com/v5/oauth/token', $data, $headers, false, $this->platform);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to exchange code for token');
        }

        return $response;
    }

    /**
     * Refresh access token
     */
    public function refreshToken(string $refreshToken): array
    {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => Helper::buildBasicAuthHeader($this->clientId, $this->clientSecret)
        ];
        
        return Helper::makeHttpRequest('POST', 'https://api.pinterest.com/v5/oauth/token', $data, $headers, false, $this->platform);
    }

    /**
     * Get user profile
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        $params = ['ad_account_id' => null]; // Optional
        
        $response = Helper::makeHttpRequest('GET', 'https://api.pinterest.com/v5/user_account', $params, $headers);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }
        
        $user = $response['body'] ?? [];
        
        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['username'] ?? null,
                'username' => $user['username'] ?? null,
                'display_name' => $user['business_name'] ?? $user['username'] ?? null,
                'profile_picture' => $user['profile_image'] ?? null,
                'follower_count' => $user['follower_count'] ?? 0,
                'following_count' => $user['following_count'] ?? 0,
                'board_count' => $user['board_count'] ?? 0,
                'pin_count' => $user['pin_count'] ?? 0,
                'account_type' => $user['account_type'] ?? null,
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

            // Pinterest only supports images: max 32MB
            if (!str_starts_with($mimeType, 'image/')) {
                $errors[] = "Pinterest only supports image files";
            }

            if ($fileSize > 32 * 1024 * 1024) {
                $errors[] = "Image exceeds Pinterest's 32MB limit";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => []
        ];
    }

    /**
     * Create pin on Pinterest
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        try {
            $headers = [
                'Authorization' => "Bearer {$account->access_token}",
                'Content-Type' => 'application/json'
            ];

            // Get first image file
            $postFile = $post->postFiles->first();
            if (!$postFile) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'No image file found for Pinterest pin');
            }

            $filePath = Storage::disk('private')->path($postFile->file_path);
            if (!file_exists($filePath)) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Image file not found');
            }

            // Pinterest requires a board_id
            $boardId = $content['board_id'] ?? null;
            if (!$boardId) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Pinterest board_id is required');
            }

            // Upload image first (base64 or URL)
            $imageBase64 = base64_encode(file_get_contents($filePath));
            
            $payload = [
                'board_id' => $boardId,
                'title' => $post->title,
                'description' => $post->description,
                'media_source' => [
                    'source_type' => 'image_base64',
                    'data' => $imageBase64
                ],
                'link' => $content['link'] ?? null,
            ];

            $response = Helper::makeHttpRequest('POST', 'https://api.pinterest.com/v5/pins', json_encode($payload), $headers, false, $this->platform);
            
            if ($response['header_code'] != ResponseAlias::HTTP_CREATED && $response['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($response['header_code'], $response['body'] ?? 'Failed to create pin');
            }

            $pinData = $response['body'] ?? [];

            return [
                'header_code' => ResponseAlias::HTTP_OK,
                'body' => [
                    'platform_post_id' => $pinData['id'] ?? 'unknown',
                    'platform_url' => $pinData['link'] ?? 'https://www.pinterest.com/',
                    'status' => 'published'
                ]
            ];

        } catch (\Exception $e) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Publishing failed: ' . $e->getMessage());
        }
    }

    /**
     * Get user's boards
     */
    public function getUserBoards(string $accessToken): array
    {
        $headers = ['Authorization' => "Bearer {$accessToken}"];
        return Helper::makeHttpRequest('GET', 'https://api.pinterest.com/v5/boards', [], $headers);
    }

    /**
     * Get available scopes
     */
    public function getAvailableScopes(): array
    {
        return [
            'boards:read' => 'Read boards',
            'boards:write' => 'Create and update boards',
            'boards:read_secret' => 'Read secret boards',
            'pins:read' => 'Read pins',
            'pins:write' => 'Create, update and delete pins',
            'user_accounts:read' => 'Read user account information',
            'ads:read' => 'Read ads data',
            'ads:write' => 'Create and update ads',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['image'];
    }

    public function getPostingLimits(): array
    {
        return [
            'max_image_size_mb' => 32,
            'supported_formats' => ['jpg', 'jpeg', 'png'],
            'max_title_length' => 100,
            'max_description_length' => 500,
        ];
    }
}
