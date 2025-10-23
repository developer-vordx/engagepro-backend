<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class LinkedInService
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
        $platForm = SocialAccount::where('slug', 'linkedin')->first();
        $this->baseUrl = $platForm?->url ?: 'https://api.linkedin.com';
        $this->scopes = $platForm?->scopes ?? ['openid', 'profile', 'email', 'w_member_social'];
        $this->clientId = config('services.linkedin.client_id', '');
        $this->clientSecret = config('services.linkedin.client_secret', '');
        $this->redirectUri = config('services.linkedin.redirect_uri', '');
        $this->platform = 'linkedin';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['image', 'video', 'text'];
    }

    public function getPlatform(): string
    {
        return 'linkedin';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Generate LinkedIn OAuth 2.0 authorization URL
     * Scopes: openid, profile, email, w_member_social (for posting)
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
        
        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query($params);
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
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $response = Helper::makeHttpRequest('POST', 'https://www.linkedin.com/oauth/v2/accessToken', $data, $headers, false, $this->platform);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to exchange code for token');
        }

        return $response;
    }

    /**
     * Refresh access token (LinkedIn tokens are typically long-lived, refresh not always needed)
     */
    public function refreshToken(string $refreshToken): array
    {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        return Helper::makeHttpRequest('POST', 'https://www.linkedin.com/oauth/v2/accessToken', $data, $headers, false, $this->platform);
    }

    /**
     * Get user profile using LinkedIn API v2
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        
        // Get basic profile (new v2 endpoint)
        $response = Helper::makeHttpRequest('GET', 'https://api.linkedin.com/v2/userinfo', [], $headers);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }
        
        $profile = $response['body'] ?? [];
        
        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $profile['sub'] ?? null,
                'username' => $profile['email'] ?? $profile['name'] ?? null,
                'display_name' => $profile['name'] ?? $profile['given_name'] . ' ' . $profile['family_name'] ?? null,
                'profile_picture' => $profile['picture'] ?? null,
                'email' => $profile['email'] ?? null,
                'locale' => $profile['locale'] ?? null,
                'is_verified' => $profile['email_verified'] ?? false,
                'follower_count' => 0,
                'following_count' => 0,
            ]
        ];
    }

    /**
     * Validate content for LinkedIn
     */
    public function validateContent(array $mediaFiles, array $metadata = []): array
    {
        $errors = [];
        $warnings = [];

        foreach ($mediaFiles as $filePath) {
            if (!file_exists($filePath)) {
                $errors[] = "File does not exist: {$filePath}";
                continue;
            }

            $fileSize = filesize($filePath);
            $mimeType = mime_content_type($filePath);

            // LinkedIn image limits: 20MB
            if (str_starts_with($mimeType, 'image/') && $fileSize > 20 * 1024 * 1024) {
                $errors[] = "Image file exceeds LinkedIn's 20MB limit";
            }

            // LinkedIn video limits: 5GB, max 10 minutes
            if (str_starts_with($mimeType, 'video/') && $fileSize > 5 * 1024 * 1024 * 1024) {
                $errors[] = "Video file exceeds LinkedIn's 5GB limit";
            }
        }

        // Text limit: 3000 characters for posts
        $textLength = strlen(($metadata['title'] ?? '') . "\n\n" . ($metadata['description'] ?? ''));
        if ($textLength > 3000) {
            $errors[] = "Post text exceeds LinkedIn's 3000 character limit";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Publish post to LinkedIn using UGC Posts API
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        try {
            $headers = [
                'Authorization' => "Bearer {$account->access_token}",
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0'
            ];

            $author = 'urn:li:person:' . $account->identifier;
            $text = $content['description'] ?? $post->description ?? '';

            $payload = [
                'author' => $author,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $text
                        ],
                        'shareMediaCategory' => 'NONE'
                    ]
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
                ]
            ];

            // If media exists, upload and attach
            $postFiles = $post->postFiles;
            if ($postFiles && $postFiles->isNotEmpty()) {
                $mediaAssets = [];
                
                foreach ($postFiles as $file) {
                    // Register upload
                    $registerResult = $this->registerUpload($account->access_token, $account->identifier);
                    
                    if ($registerResult['header_code'] != ResponseAlias::HTTP_OK) {
                        continue;
                    }
                    
                    $uploadUrl = $registerResult['body']['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'] ?? null;
                    $asset = $registerResult['body']['value']['asset'] ?? null;
                    
                    if ($uploadUrl && $asset) {
                        // Upload file
                        $filePath = Storage::disk('private')->path($file->file_path);
                        $this->uploadMedia($uploadUrl, $filePath, $account->access_token);
                        $mediaAssets[] = $asset;
                    }
                }
                
                if (!empty($mediaAssets)) {
                    $payload['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'IMAGE';
                    $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = array_map(function($asset) use ($text) {
                        return [
                            'status' => 'READY',
                            'description' => ['text' => $text],
                            'media' => $asset,
                            'title' => ['text' => 'Shared media']
                        ];
                    }, $mediaAssets);
                }
            }

            $response = Helper::makeHttpRequest('POST', 'https://api.linkedin.com/v2/ugcPosts', json_encode($payload), $headers, false, $this->platform);
            
            if ($response['header_code'] != ResponseAlias::HTTP_CREATED && $response['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($response['header_code'], $response['body'] ?? 'Failed to publish post');
            }

            return [
                'header_code' => ResponseAlias::HTTP_OK,
                'body' => [
                    'platform_post_id' => $response['body']['id'] ?? 'unknown',
                    'platform_url' => 'https://www.linkedin.com/feed/',
                    'status' => 'published'
                ]
            ];

        } catch (\Exception $e) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Publishing failed: ' . $e->getMessage());
        }
    }

    /**
     * Register upload for media
     */
    private function registerUpload(string $accessToken, string $personId): array
    {
        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json'
        ];

        $payload = [
            'registerUploadRequest' => [
                'recipes' => ['urn:li:digitalmediaRecipe:feedshare-image'],
                'owner' => 'urn:li:person:' . $personId,
                'serviceRelationships' => [
                    [
                        'relationshipType' => 'OWNER',
                        'identifier' => 'urn:li:userGeneratedContent'
                    ]
                ]
            ]
        ];

        return Helper::makeHttpRequest('POST', 'https://api.linkedin.com/v2/assets?action=registerUpload', json_encode($payload), $headers, false, $this->platform);
    }

    /**
     * Upload media to LinkedIn
     */
    private function uploadMedia(string $uploadUrl, string $filePath, string $accessToken): array
    {
        if (!file_exists($filePath)) {
            return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'File not found');
        }

        $fileContent = file_get_contents($filePath);
        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/octet-stream'
        ];

        return Helper::makeHttpRequest('PUT', $uploadUrl, $fileContent, $headers, false, $this->platform);
    }

    /**
     * Get posting limits
     */
    public function getPostingLimits(): array
    {
        return [
            'max_text_length' => 3000,
            'max_image_size_mb' => 20,
            'max_video_size_gb' => 5,
            'max_video_duration_seconds' => 600,
            'supported_image_formats' => ['jpg', 'jpeg', 'png'],
            'supported_video_formats' => ['mp4'],
        ];
    }

    /**
     * Get available scopes for LinkedIn API
     */
    public function getAvailableScopes(): array
    {
        return [
            'openid' => 'OpenID Connect authentication',
            'profile' => 'Read profile information',
            'email' => 'Read email address',
            'w_member_social' => 'Create, modify and delete posts, comments and reactions',
            'r_liteprofile' => 'Read lite profile (legacy)',
            'r_emailaddress' => 'Read email address (legacy)',
            'w_organization_social' => 'Create posts on behalf of an organization',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['image', 'video', 'text'];
    }

    /**
     * Get post analytics
     */
    public function getPostAnalytics(CustomerAccount $account, string $platformPostId): array
    {
        $headers = ['Authorization' => "Bearer {$account->access_token}"];
        
        $response = Helper::makeHttpRequest('GET', "https://api.linkedin.com/v2/socialActions/{$platformPostId}/likes", [], $headers);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get analytics');
        }

        return $response;
    }
}
