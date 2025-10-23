<?php

namespace App\Library\SocialManager;

use App\Models\CustomerAccount;
use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Exception;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class MetaService
{
    private string $appId;
    private string $appSecret;
    private string $redirectUri;
    private string $baseUrl;
    private mixed $scopes;
    private string $platform;
    private mixed $supportedMediaTypes;
    private string $accessToken;
    private string $graphVersion;

    public function __construct()
    {
        $platForm = SocialAccount::where('slug', 'meta')->first();
        $this->baseUrl = $platForm->url;
        $this->scopes = $platForm->scopes;

        // Meta App credentials
        $this->appId = config('services.meta.app_id', '');
        $this->appSecret = config('services.meta.app_secret', '');
        $this->redirectUri = config('services.meta.redirect_uri', '');
        $this->accessToken = config('services.meta.access_token', '');
        $this->graphVersion = config('services.meta.graph_version', 'v18.0');

        $this->platform = $platForm->slug;
        $this->supportedMediaTypes = $platForm->supported_media_types;
    }

    /**
     * @return string
     */
    public function getPlatform(): string
    {
        return 'meta';
    }

    /**
     * Helper function to return error response
     * @param int $statusCode
     * @param string $message
     * @return array
     */
    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Generate authorization URL for Meta OAuth 2.0
     * @param array $scopes
     * @return string
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        $scopes = empty($scopes) ? $this->scopes : $scopes;

        
        // Ensure scopes is an array
        if (is_string($scopes)) {
            $scopes = Helper::parseScopes($scopes);
        }
        $params = [
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'state' => csrf_token(),
        ];

        return "https://www.facebook.com/{$this->graphVersion}/dialog/oauth?" . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     * @param string $code
     * @return array
     */
    public function exchangeCodeForToken(string $code): array
    {
        $data = [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ];

        $url = "https://graph.facebook.com/{$this->graphVersion}/oauth/access_token";

        $response = Helper::makeHttpRequest('GET', $url, $data, [], false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to exchange code for token');
        }

        return $response;
    }

    /**
     * Get user's Facebook pages
     * @param string $accessToken
     * @return array
     */
    public function getUserPages(string $accessToken): array
    {
        $url = "https://graph.facebook.com/{$this->graphVersion}/me/accounts";
        $params = ['access_token' => $accessToken];

        $response = Helper::makeHttpRequest('GET', $url, $params, [], false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get user pages');
        }

        return $response;
    }

    /**
     * Get user's Instagram business accounts
     * @param string $accessToken
     * @return array
     */
    public function getInstagramAccounts(string $accessToken): array
    {
        $url = "https://graph.facebook.com/{$this->graphVersion}/me/accounts";
        $params = [
            'access_token' => $accessToken,
            'fields' => 'instagram_business_account'
        ];

        $response = Helper::makeHttpRequest('GET', $url, $params, [], false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get Instagram accounts');
        }

        return $response;
    }

    /**
     * Upload media to Facebook/Instagram
     * @param PostFile $mediaFile
     * @param string $accessToken
     * @param string $platform (facebook or instagram)
     * @return array
     */
    public function uploadMedia(PostFile $mediaFile, string $accessToken, string $platform = 'facebook'): array
    {
        try {
            // Validate file type
            if (!$this->isValidMediaType($mediaFile->mime_type, $platform)) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, "Invalid media type for {$platform}");
            }

            // Get file path
            $filePath = Storage::disk('private')->path($mediaFile->file_path);

            if (!file_exists($filePath)) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Media file not found');
            }

            // Prepare upload data
            $uploadData = [
                'access_token' => $accessToken,
                'source' => new \CURLFile($filePath, $mediaFile->mime_type, basename($filePath))
            ];

            // Different endpoints for Facebook vs Instagram
            if ($platform === 'instagram') {
                $url = "https://graph.facebook.com/{$this->graphVersion}/me/media";
                $uploadData['media_type'] = $this->getInstagramMediaType($mediaFile->mime_type);
            } else {
                $url = "https://graph.facebook.com/{$this->graphVersion}/me/photos";
            }

            $response = Helper::makeHttpRequest('POST', $url, $uploadData, [], true, $this->platform);

            if ($response['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($response['header_code'], 'Media upload failed');
            }

            return [
                'header_code' => $response['header_code'],
                'body' => [
                    'media_id' => $response['body']['id'] ?? 'unknown',
                    'platform' => $platform,
                    'status' => 'uploaded'
                ]
            ];

        } catch (Exception $e) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Media upload error: ' . $e->getMessage());
        }
    }


    /**
     * Validate media type for platform
     * @param string $mimeType
     * @param string $platform
     * @return bool
     */
    public function isValidMediaType(string $mimeType, string $platform): bool
    {
        $supportedTypes = [
            'facebook' => ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/avi', 'video/mov'],
            'instagram' => ['image/jpeg', 'image/png', 'video/mp4', 'video/mov']
        ];

        return in_array($mimeType, $supportedTypes[$platform] ?? []);
    }

    /**
     * Get Instagram media type
     * @param string $mimeType
     * @return string
     */
    public function getInstagramMediaType(string $mimeType): string
    {
        if (strpos($mimeType, 'image/') === 0) {
            return 'IMAGE';
        } elseif (strpos($mimeType, 'video/') === 0) {
            return 'VIDEO';
        }

        return 'IMAGE'; // Default fallback
    }

    /**
     * Get platform URL for published post
     * @param string $postId
     * @param string $platform
     * @return string
     */
    public function getPlatformUrl(string $postId, string $platform): string
    {
        if ($platform === 'instagram') {
            return "https://www.instagram.com/p/{$postId}/";
        } else {
            return "https://www.facebook.com/{$postId}";
        }
    }

    /**
     * Get post insights
     * @param string $postId
     * @param string $accessToken
     * @return array
     */
    public function getPostInsights(string $postId, string $accessToken): array
    {
        $url = "https://graph.facebook.com/{$this->graphVersion}/{$postId}/insights";
        $params = [
            'access_token' => $accessToken,
            'metric' => 'impressions,reach,engagement'
        ];

        $response = Helper::makeHttpRequest('GET', $url, $params, [], false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get post insights');
        }

        return $response;
    }

    /**
     * Get user profile from Meta (Facebook/Instagram)
     * @param string $userAccessToken
     * @return array
     * @throws ConnectionException
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        $params = [
            'fields' => 'id,name,email,picture,verified,link,gender,locale,timezone,updated_time'
        ];

        $response = Helper::makeHttpRequest('GET', "https://graph.facebook.com/{$this->graphVersion}/me", $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        $user = $response['body'] ?? [];

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['id'] ?? null,
                'username' => $user['email'] ?? $user['name'] ?? null,
                'display_name' => $user['name'] ?? null,
                'profile_picture' => $user['picture']['data']['url'] ?? null,
                'bio_description' => null,
                'is_verified' => $user['verified'] ?? false,
                'follower_count' => 0,
                'following_count' => 0,
                'email' => $user['email'] ?? null,
                'gender' => $user['gender'] ?? null,
                'locale' => $user['locale'] ?? null,
                'timezone' => $user['timezone'] ?? null,
                'updated_at' => $user['updated_time'] ?? null,
                'profile_url' => $user['link'] ?? null,
            ]
        ];
    }

    /**
     * Validate content for Meta platforms
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

            if (str_starts_with($mimeType, 'image/') && $fileSize > 30 * 1024 * 1024) {
                $errors[] = "Image exceeds 30MB limit";
            }

            if (str_starts_with($mimeType, 'video/') && $fileSize > 10 * 1024 * 1024 * 1024) {
                $errors[] = "Video exceeds 10GB limit";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => []
        ];
    }

    /**
     * Publish post to Facebook or Instagram
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        try {
            // Determine if this is Facebook or Instagram based on account data
            $platformData = json_decode($account->platform_data ?? '{}', true);
            $targetPlatform = $platformData['target_platform'] ?? 'facebook';

            if ($targetPlatform === 'instagram') {
                return $this->publishToInstagram($account, $post, $content);
            } else {
                return $this->publishToFacebook($account, $post, $content);
            }

        } catch (\Exception $e) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Publishing failed: ' . $e->getMessage());
        }
    }

    /**
     * Publish to Facebook Page
     */
    private function publishToFacebook(CustomerAccount $account, Post $post, array $content = []): array
    {
        $headers = ['Authorization' => "Bearer {$account->access_token}"];

        $message = $content['description'] ?? $post->description ?? '';
        $payload = [
            'message' => $message,
        ];

        // Upload media if present
        $postFiles = $post->postFiles;
        if ($postFiles && $postFiles->isNotEmpty()) {
            $firstFile = $postFiles->first();
            $filePath = Storage::disk('private')->path($firstFile->file_path);

            if (file_exists($filePath) && str_starts_with($firstFile->mime_type, 'image/')) {
                // Upload photo
                $photoData = [
                    'access_token' => $account->access_token,
                    'message' => $message,
                    'source' => new \CURLFile($filePath, $firstFile->mime_type, basename($filePath))
                ];

                $pageId = $account->identifier; // Page ID stored as identifier
                $response = Helper::makeHttpRequest('POST', "https://graph.facebook.com/{$this->graphVersion}/{$pageId}/photos", $photoData, [], true, $this->platform);
            } else {
                // Text post
                $pageId = $account->identifier;
                $response = Helper::makeHttpRequest('POST', "https://graph.facebook.com/{$this->graphVersion}/{$pageId}/feed", $payload, $headers, false, $this->platform);
            }
        } else {
            // Text-only post
            $pageId = $account->identifier;
            $response = Helper::makeHttpRequest('POST', "https://graph.facebook.com/{$this->graphVersion}/{$pageId}/feed", $payload, $headers, false, $this->platform);
        }

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], $response['body'] ?? 'Failed to publish to Facebook');
        }

        return [
            'header_code' => ResponseAlias::HTTP_OK,
            'body' => [
                'platform_post_id' => $response['body']['id'] ?? 'unknown',
                'platform_url' => "https://www.facebook.com/" . ($response['body']['id'] ?? ''),
                'status' => 'published'
            ]
        ];
    }

    /**
     * Publish to Instagram Business Account
     */
    private function publishToInstagram(CustomerAccount $account, Post $post, array $content = []): array
    {
        $postFile = $post->postFiles->first();
        if (!$postFile) {
            return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Instagram requires at least one media file');
        }

        $filePath = Storage::disk('private')->path($postFile->file_path);
        if (!file_exists($filePath)) {
            return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Media file not found');
        }

        $headers = ['Authorization' => "Bearer {$account->access_token}"];
        $igUserId = $account->identifier; // Instagram Business Account ID

        // Step 1: Create media container
        $caption = $content['description'] ?? $post->description ?? '';
        $isVideo = str_starts_with($postFile->mime_type, 'video/');

        // Upload image to public URL first (Instagram requires public URL)
        // For production, use CDN or public storage
        $mediaUrl = $content['media_url'] ?? '';
        if (empty($mediaUrl)) {
            return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Instagram requires a public media URL. Please provide media_url in content.');
        }

        $containerData = [
            'caption' => $caption,
        ];

        if ($isVideo) {
            $containerData['media_type'] = 'VIDEO';
            $containerData['video_url'] = $mediaUrl;
        } else {
            $containerData['image_url'] = $mediaUrl;
        }

        $containerResponse = Helper::makeHttpRequest('POST', "https://graph.facebook.com/{$this->graphVersion}/{$igUserId}/media", $containerData, $headers, false, $this->platform);

        if ($containerResponse['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($containerResponse['header_code'], $containerResponse['body'] ?? 'Failed to create Instagram media container');
        }

        $creationId = $containerResponse['body']['id'] ?? null;
        if (!$creationId) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'No creation ID received from Instagram');
        }

        // Step 2: Publish media container
        $publishData = [
            'creation_id' => $creationId,
        ];

        $publishResponse = Helper::makeHttpRequest('POST', "https://graph.facebook.com/{$this->graphVersion}/{$igUserId}/media_publish", $publishData, $headers, false, $this->platform);

        if ($publishResponse['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($publishResponse['header_code'], $publishResponse['body'] ?? 'Failed to publish to Instagram');
        }

        $mediaId = $publishResponse['body']['id'] ?? 'unknown';

        return [
            'header_code' => ResponseAlias::HTTP_OK,
            'body' => [
                'platform_post_id' => $mediaId,
                'platform_url' => "https://www.instagram.com/p/{$mediaId}/",
                'status' => 'published'
            ]
        ];
    }

    /**
     * Get comprehensive available scopes for Meta platforms
     */
    public function getAvailableScopes(): array
    {
        return [
            // Facebook scopes
            'email' => 'Access user email',
            'public_profile' => 'Access public profile',
            'pages_show_list' => 'Access list of Pages',
            'pages_read_engagement' => 'Read Page engagement data',
            'pages_manage_posts' => 'Create, edit and delete Page posts',
            'pages_manage_engagement' => 'Manage Page interactions',
            'pages_read_user_content' => 'Read user-generated content on Pages',
            'pages_messaging' => 'Send messages from Pages',
            'publish_to_groups' => 'Post to groups',
            'groups_access_member_info' => 'Access group member info',

            // Instagram scopes
            'instagram_basic' => 'Read Instagram profile info and media',
            'instagram_content_publish' => 'Publish content to Instagram',
            'instagram_manage_comments' => 'Manage Instagram comments',
            'instagram_manage_insights' => 'Read Instagram insights',
            'instagram_manage_messages' => 'Manage Instagram Direct messages',
            'instagram_shopping_tag_products' => 'Tag products in Instagram posts',
            'instagram_branded_content_brand' => 'Manage branded content as brand',
            'instagram_branded_content_creator' => 'Manage branded content as creator',

            // Advanced permissions
            'business_management' => 'Manage business assets',
            'ads_management' => 'Manage ads',
            'leads_retrieval' => 'Retrieve leads',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['image', 'video', 'text'];
    }

    public function getPostingLimits(): array
    {
        return [
            'facebook' => [
                'max_posts_per_hour' => 60,
                'max_image_size_mb' => 30,
                'max_video_size_gb' => 10,
                'max_video_duration_hours' => 2,
            ],
            'instagram' => [
                'max_posts_per_hour' => 25,
                'max_image_size_mb' => 30,
                'max_video_size_gb' => 4,
                'max_video_duration_seconds' => 60,
                'min_video_duration_seconds' => 3,
            ]
        ];
    }
}

