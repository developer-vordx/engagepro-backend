<?php

namespace App\Library\SocialManager;

use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Models\CustomerAccount;
use App\Models\SocialAccount;
use Random\RandomException;
use App\Models\Post;
use Carbon\Carbon;
use App\Helper;

class TikTokService
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
        $platForm = SocialAccount::where('slug', 'tiktok')->first();
        $this->baseUrl = $platForm->url;
        $this->scopes = $platForm->scopes;
        $this->clientId = config('services.tiktok.client_id');
        $this->clientSecret = config('services.tiktok.client_secret');
        $this->redirectUri = config('services.tiktok.redirect_uri');
        $this->platform = $platForm->slug;
        $this->supportedMediaTypes = $platForm->supported_media_types;
    }

    /**
     * @return string
     */
    public function getPlatform(): string
    {
        return 'tiktok';
    }

    /**
     * Helper function to return error response
     * @param int $statusCode
     * @param string $message
     * @return array
     */
    private function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Fetch App-level Client Access Token (for Research API calls)
     * @return array
     * @throws ConnectionException
     */
    public function getClientAccessToken(): array
    {
        $headers = ['Cache-Control' => 'no-cache'];
        $data = [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ];

        return Helper::makeHttpRequest('POST', "{$this->baseUrl}/v2/oauth/token/", $data, $headers, true);
    }

    /**
     * Generate authorization URL with PKCE
     * @param array $scopes
     * @return string
     * @throws RandomException
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        $scopes = empty($scopes) ? $this->scopes : $scopes;
        $codeVerifier = bin2hex(random_bytes(64));
        session(['tiktok_code_verifier' => $codeVerifier]);

        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $params = [
            'client_key' => $this->clientId,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'state' => csrf_token(),
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

        return 'https://www.tiktok.com/v2/auth/authorize?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     * @param string $code
     * @return array
     * @throws ConnectionException
     */
    public function exchangeCodeForToken(string $code): array
    {
        $codeVerifier = session('tiktok_code_verifier');

        $headers = ['Cache-Control' => 'no-cache'];
        $data = [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code_verifier' => $codeVerifier,
        ];

        $response = Helper::makeHttpRequest('POST', "{$this->baseUrl}/v2/oauth/token/", $data, $headers, true, $this->platform);

        // Clear the code verifier from session
        session()->forget('tiktok_code_verifier');

        return $response;
    }

    /**
     * Refresh access token using refresh token
     * @param string $refreshToken
     * @return array
     * @throws ConnectionException
     */
    public function refreshToken(string $refreshToken): array
    {
        $headers = ['Cache-Control' => 'no-cache'];
        $data = [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        return Helper::makeHttpRequest('POST', "{$this->baseUrl}/v2/oauth/token/", $data, $headers, true, $this->platform);

    }

    /**
     * Auto-refresh token if needed
     * @param CustomerAccount $account
     * @return array|bool
     * @throws ConnectionException
     */
    public function autoRefreshTokenIfNeeded(CustomerAccount $account): array|bool
    {
        // Check if token expires within next 1 hour
        if ($account->token_expires_at && Carbon::parse($account->token_expires_at)->subHour()->isPast()) {

            if (!$account->refresh_token) {
                return [
                    'header_code' => ResponseAlias::HTTP_FORBIDDEN,
                    'body' => 'Access token has expired. Please re-authorize.'
                ];
            }

            $refreshResult = $this->refreshToken($account->refresh_token);

            if ($refreshResult['header_code'] != ResponseAlias::HTTP_OK) {
                return $refreshResult;
            }

            // Update account with new tokens
            $account->update([
                'access_token' => $refreshResult['body']['access_token'],
                'refresh_token' => $refreshResult['body']['refresh_token'] ?? $account->refresh_token,
                'token_expires_at' => now()->addSeconds($refreshResult['body']['expires_in']),
                'last_synced_at' => now()
            ]);
        }
        return [
            'header_code' => ResponseAlias::HTTP_OK,
            'body' => true
        ];
    }

    /**
     * Validate access token
     * @param string $accessToken
     * @return bool
     * @throws ConnectionException
     */
    public function validateToken(string $accessToken): bool
    {
        $headers = ['Authorization' => "Bearer {$accessToken}"];
        $params = ['fields' => 'open_id,display_name'];

        $response = Helper::makeHttpRequest('GET', "{$this->baseUrl}/v2/user/info/", $params, $headers);

        return $response && $response['success'];
    }

    /**
     * Get comprehensive user profile with all available fields
     * @param string $userAccessToken
     * @return array
     * @throws ConnectionException
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $fields = [
            'open_id',
            'union_id',
            'avatar_url',
            'avatar_url_100',
            'avatar_large_url',
            'display_name',
            'bio_description',
            'profile_deep_link',
            'is_verified',
            'username',
            'follower_count',
            'following_count',
            'likes_count',
            'video_count'
        ];

        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        $params = ['fields' => implode(',', $fields)];

        $response = Helper::makeHttpRequest('GET', "{$this->baseUrl}/v2/user/info/", $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        $user = $response['body']['data']['user'] ?? [];

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['open_id'] ?? null,
                'union_id' => $user['union_id'] ?? null,
                'username' => $user['username'] ?? $user['display_name'] ?? null,
                'display_name' => $user['display_name'] ?? null,
                'profile_picture' => $user['avatar_large_url'] ?? $user['avatar_url'] ?? null,
                'profile_picture_100' => $user['avatar_url_100'] ?? null,
                'bio_description' => $user['bio_description'] ?? null,
                'profile_deep_link' => $user['profile_deep_link'] ?? null,
                'is_verified' => $user['is_verified'] ?? false,
                'follower_count' => $user['follower_count'] ?? 0,
                'following_count' => $user['following_count'] ?? 0,
                'likes_count' => $user['likes_count'] ?? 0,
                'video_count' => $user['video_count'] ?? 0,
            ]
        ];
    }

    /**
     * Get user's video list with comprehensive metadata
     * @param string $userAccessToken
     * @param int $maxCount
     * @param int|null $cursor
     * @return array
     * @throws ConnectionException
     */
    public function getUserVideos(string $userAccessToken, int $maxCount = 10, ?int $cursor = null): array
    {
        $fields = [
            'id',
            'create_time',
            'cover_image_url',
            'share_url',
            'video_description',
            'duration',
            'height',
            'width',
            'title',
            'embed_html',
            'embed_link',
            'like_count',
            'comment_count',
            'share_count',
            'view_count'
        ];

        // Prepare request body for v2 API
        $requestBody = [
            'max_count' => min($maxCount, 20) // API limit is 20, default is 10
        ];

        // Add cursor only if provided
        if ($cursor !== null) {
            $requestBody['cursor'] = $cursor;
        }

        $headers = [
            'Authorization' => "Bearer {$userAccessToken}",
            'Content-Type' => 'application/json'
        ];

        // Build URL with fields as query parameters
        $fieldsQuery = 'fields=' . implode(',', $fields);
        $url = "https://open.tiktokapis.com/v2/video/list/?{$fieldsQuery}";

        $response = Helper::makeHttpRequest('POST', $url, $requestBody, $headers, false, $this->platform);
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }
        $data = $response['body']['data'] ?? [];

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'videos' => $data['videos'] ?? [],
                'cursor' => $data['cursor'] ?? null,
                'has_more' => $data['has_more'] ?? false,
                'total' => count($data['videos'] ?? [])
            ],
        ];
    }

    /**
     * Query specific videos by IDs
     * @param string $userAccessToken
     * @param array $videoIds
     * @return array
     * @throws ConnectionException
     */
    public function queryVideos(string $userAccessToken, array $videoIds): array
    {
        $fields = [
            'id',
            'create_time',
            'cover_image_url',
            'share_url',
            'video_description',
            'duration',
            'height',
            'width',
            'title',
            'embed_html',
            'embed_link',
            'like_count',
            'comment_count',
            'share_count',
            'view_count'
        ];

        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        $data = [
            'fields' => implode(',', $fields),
            'video_ids' => $videoIds
        ];

        $response = Helper::makeHttpRequest('POST', "{$this->baseUrl}/v2/video/query/", $data, $headers,false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        $responseData = $response['body']['data'] ?? [];

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'videos' => $responseData['videos'] ?? [],
                'total' => count($responseData['videos'] ?? [])
            ]
        ];
    }

    /**
     * Upload video to TikTok
     * @param string $accessToken
     * @param string $filePath
     * @param array $metadata
     * @return array
     * @throws ConnectionException
     */
    public function uploadMedia(string $accessToken, string $filePath, array $metadata = []): array
    {
        // Step 1: Initialize upload
        $fileSize = filesize($filePath);
        $chunkSize = min(10 * 1024 * 1024, $fileSize); // 10MB or file size if smaller
        $totalChunks = ceil($fileSize / $chunkSize);

        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json'
        ];

        $data = [
            'post_info' => [
                'title' => $metadata['title'] ?? '',
                'description' => $metadata['description'] ?? '',
                'privacy_level' => $metadata['privacy_level'] ?? 'SELF_ONLY',
                'disable_duet' => $metadata['disable_duet'] ?? false,
                'disable_comment' => $metadata['disable_comment'] ?? false,
                'disable_stitch' => $metadata['disable_stitch'] ?? false,
                'video_cover_timestamp_ms' => $metadata['cover_timestamp'] ?? 1000,
            ],
            'source_info' => [
                'source' => 'FILE_UPLOAD',
                'video_size' => $fileSize,
                'chunk_size' => $chunkSize,
                'total_chunk_count' => $totalChunks,
            ]
        ];

        $response = Helper::makeHttpRequest('POST', "{$this->baseUrl}/v2/post/publish/video/init/", $data, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        $uploadData = $response['body']['data'];
        $publishId = $uploadData['publish_id'];
        $uploadUrl = $uploadData['upload_url'];

        $uploadHeaders = [
            'Authorization' => "Bearer {$accessToken}",
        ];

        $uploadData = [
            'video' => new \CURLFile($filePath, mime_content_type($filePath), basename($filePath))
        ];

        $uploadResponse = Helper::makeHttpRequest('POST', $uploadUrl, $uploadData, $uploadHeaders);

        if ($uploadResponse['header_code'] != ResponseAlias::HTTP_OK) {
            return $uploadResponse;
        }

        return [
            'header_code' => $uploadResponse['header_code'],
            'body' => [
                'publish_id' => $publishId,
                'upload_url' => $uploadUrl,
                'status' => 'uploaded'
            ]
        ];
    }

    /**
     * Publish post to TikTok
     * @param CustomerAccount $account
     * @param Post $post
     * @return array
     * @throws ConnectionException
     */
    public function publishPost(CustomerAccount $account, Post $post): array
    {
        // Auto-refresh token if needed
        $autoRefreshToken = $this->autoRefreshTokenIfNeeded($account);
        if ($autoRefreshToken['header_code'] != ResponseAlias::HTTP_OK) {
            return $autoRefreshToken;
        }

        // TikTok only supports single video uploads
        if (count($post->files) === 0) {
            return [
                'header_code' => ResponseAlias::HTTP_BAD_REQUEST,
                'body' => 'No video file provided.'
            ];
        }

        $videoFile = $post->files->first();
        $filePath = storage_path('app/' . $videoFile->file_path);

        if (!file_exists($filePath)) {
            return [
                'header_code' => ResponseAlias::HTTP_BAD_REQUEST,
                'body' => 'No video file provided.'
            ];
        }

        // Upload video
        $uploadResult = $this->uploadMedia($account->access_token, $filePath, [
            'title' => $post->title ?? '',
            'description' => $post->description ?? '',
            'privacy_level' => 'PUBLIC_TO_EVERYONE', // or SELF_ONLY, MUTUAL_FOLLOW_FRIENDS
            'disable_duet' => false,
            'disable_comment' => false,
            'disable_stitch' => false,
        ]);

        if ($uploadResult['header_code'] != ResponseAlias::HTTP_OK) {
            return $uploadResult;
        }

        // Check publish status
        $headers = ['Authorization' => "Bearer {$account->access_token}"];
        $data = ['publish_id' => $uploadResult['body']['publish_id']];

        $statusResponse = Helper::makeHttpRequest('POST', "{$this->baseUrl}/v2/post/publish/status/fetch/", $data, $headers);

        $statusData = $statusResponse && $statusResponse['success'] ? $statusResponse['data']['data'] : [];

        return [
            'header_code' => $statusResponse['header_code'],
            'body' => [
                'platform_post_id' => $uploadResult['body']['publish_id'],
                'status' => $statusData['status'] ?? 'processing',
                'platform_url' => $statusData['share_url'] ?? null,
                'publish_id' => $uploadResult['body']['publish_id']
            ]
        ];
    }

    /**
     * Get comprehensive post analytics
     * @param CustomerAccount $account
     * @param string $platformPostId
     * @return array
     * @throws ConnectionException
     */
    public function getPostAnalytics(CustomerAccount $account, string $platformPostId): array
    {
        // Auto-refresh token if needed
        $autoRefreshToken = $this->autoRefreshTokenIfNeeded($account);
        if ($autoRefreshToken['header_code'] != ResponseAlias::HTTP_OK) {
            return $autoRefreshToken;
        }


        $headers = ['Authorization' => "Bearer {$account->access_token}"];
        $data = [
            'fields' => 'id,like_count,comment_count,share_count,view_count,create_time,video_description,cover_image_url,share_url,duration',
            'video_ids' => [$platformPostId]
        ];

        return Helper::makeHttpRequest('POST', "{$this->baseUrl}/v2/video/query/", $data, $headers);

//        if (!$response || !$response['success']) {
//            return $this->errorResponse(
//                ResponseAlias::HTTP_EXPECTATION_FAILED,
//                'Failed to get analytics: ' . ($response['body'] ?? 'Unknown error')
//            );
//        }
//
//        $videos = $response['data']['data']['videos'] ?? [];
//        $video = $videos[0] ?? [];
//
//        if (empty($video)) {
//            return $this->errorResponse(
//                ResponseAlias::HTTP_NOT_FOUND,
//                'Video not found'
//            );
//        }
//
//        // Calculate engagement rate
//        $totalEngagement = ($video['like_count'] ?? 0) +
//            ($video['comment_count'] ?? 0) +
//            ($video['share_count'] ?? 0);
//        $views = $video['view_count'] ?? 1;
//        $engagementRate = $views > 0 ? round(($totalEngagement / $views) * 100, 2) : 0;
//
//        return [
//            'views' => $video['view_count'] ?? 0,
//            'likes' => $video['like_count'] ?? 0,
//            'shares' => $video['share_count'] ?? 0,
//            'comments' => $video['comment_count'] ?? 0,
//            'saves' => 0, // TikTok API doesn't provide saves count
//            'engagement_rate' => $engagementRate,
//            'duration' => $video['duration'] ?? 0,
//            'create_time' => $video['create_time'] ?? null,
//            'cover_image_url' => $video['cover_image_url'] ?? null,
//            'share_url' => $video['share_url'] ?? null,
//            'additional_metrics' => [
//                'total_engagement' => $totalEngagement,
//                'video_id' => $video['id'] ?? null,
//                'description' => $video['video_description'] ?? null
//            ]
//        ];
    }

    /**
     * Revoke user access token
     * @param string $accessToken
     * @return array
     * @throws ConnectionException
     */
    public function revokeAccess(string $accessToken): array
    {
        $headers = ['Cache-Control' => 'no-cache'];
        $data = [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'token' => $accessToken,
        ];

        $response = Helper::makeHttpRequest('POST', "{$this->baseUrl}/v2/oauth/revoke/", $data, $headers, true);

        return [
            'header_code' => $response['header_code'],
            'body' => $response['body']
        ];
    }

    /**
     * @return string[]
     */
    public function getSupportedMediaTypes(): array
    {
        return $this->supportedMediaTypes; // TikTok only supports videos
    }

    /**
     * Enhanced content validation
     * @param array $mediaFiles
     * @param array $metadata
     * @return array
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

            // Check file type
            $mimeType = mime_content_type($filePath);
            $allowedMimeTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'];

            if (!in_array($mimeType, $allowedMimeTypes)) {
                $errors[] = "TikTok only supports MP4, MOV, AVI, and WebM video files. {$filePath} is {$mimeType}.";
                continue;
            }

            // Check file size (max 287MB for TikTok)
            $fileSize = filesize($filePath);
            if ($fileSize > 287 * 1024 * 1024) {
                $errors[] = "Video file {$filePath} exceeds TikTok's 287MB limit. Current size: " . round($fileSize / (1024 * 1024), 2) . "MB";
            }

            // Check if file is readable
            if (!is_readable($filePath)) {
                $errors[] = "Cannot read file: {$filePath}";
            }
        }

        // Validate metadata
        if (isset($metadata['description']) && strlen($metadata['description']) > 2200) {
            $errors[] = "Description exceeds TikTok's 2200 character limit.";
        }

        if (isset($metadata['title']) && strlen($metadata['title']) > 150) {
            $warnings[] = "Title should be under 150 characters for optimal display.";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array
     */
    public function getPostingLimits(): array
    {
        return [
            'max_posts_per_day' => 10,
            'max_posts_per_hour' => 3,
            'max_file_size_mb' => 287,
            'supported_formats' => ['mp4', 'mov', 'avi', 'webm'],
            'min_duration_seconds' => 3,
            'max_duration_seconds' => 600, // 10 minutes
            'max_description_length' => 2200,
            'max_title_length' => 150,
            'supported_aspect_ratios' => ['9:16', '16:9', '1:1'],
            'min_resolution' => '540x960',
            'max_resolution' => '1080x1920',
        ];
    }

    /**
     * Get available scopes for TikTok API
     * @return array
     */
    public function getAvailableScopes(): array
    {
        return [
            'user.info.basic' => 'Read basic user profile information',
            'user.info.profile' => 'Read extended user profile information',
            'user.info.stats' => 'Read user statistics (followers, following, likes, video count)',
            'video.list' => 'Read user\'s public videos',
            'video.upload' => 'Upload videos on behalf of user'
        ];
    }
}
