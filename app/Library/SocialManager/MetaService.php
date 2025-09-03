<?php

namespace App\Library\SocialManager;

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
                return $this->errorResponse(400, "Invalid media type for {$platform}");
            }

            // Get file path
            $filePath = Storage::disk('private')->path($mediaFile->file_path);
            
            if (!file_exists($filePath)) {
                return $this->errorResponse(400, 'Media file not found');
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
            return $this->errorResponse(500, 'Media upload error: ' . $e->getMessage());
        }
    }

    /**
     * Publish post to Facebook/Instagram
     * @param Post $post
     * @param string $accessToken
     * @param string $platform (facebook or instagram)
     * @param array $mediaIds
     * @return array
     */
    public function publishPost(Post $post, string $accessToken, string $platform = 'facebook', array $mediaIds = []): array
    {
        try {
            $postData = [
                'access_token' => $accessToken,
                'message' => $post->description
            ];

            // Different endpoints and parameters for each platform
            if ($platform === 'instagram') {
                // Instagram requires media to be uploaded first
                if (empty($mediaIds)) {
                    return $this->errorResponse(400, 'Instagram posts require media');
                }

                $url = "https://graph.facebook.com/{$this->graphVersion}/me/media_publish";
                $postData['creation_id'] = $mediaIds[0]; // Instagram uses creation_id
                
            } else {
                // Facebook page post
                $url = "https://graph.facebook.com/{$this->graphVersion}/me/feed";
                
                // Add media if available
                if (!empty($mediaIds)) {
                    $postData['attached_media'] = json_encode($mediaIds);
                }
            }

            $response = Helper::makeHttpRequest('POST', $url, $postData, [], true, $this->platform);

            if ($response['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($response['header_code'], 'Post publishing failed');
            }

            return [
                'header_code' => $response['header_code'],
                'body' => [
                    'platform_post_id' => $response['body']['id'] ?? 'unknown',
                    'platform_url' => $this->getPlatformUrl($response['body']['id'] ?? '', $platform),
                    'status' => 'published',
                    'platform' => $platform
                ]
            ];

        } catch (Exception $e) {
            return $this->errorResponse(500, 'Post publishing error: ' . $e->getMessage());
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
                'bio_description' => null, // Facebook doesn't provide bio in basic profile
                'is_verified' => $user['verified'] ?? false,
                'follower_count' => 0, // Facebook doesn't provide follower count in basic profile
                'following_count' => 0, // Facebook doesn't provide following count in basic profile
                'email' => $user['email'] ?? null,
                'gender' => $user['gender'] ?? null,
                'locale' => $user['locale'] ?? null,
                'timezone' => $user['timezone'] ?? null,
                'updated_at' => $user['updated_time'] ?? null,
                'profile_url' => $user['link'] ?? null,
            ]
        ];
    }
}
