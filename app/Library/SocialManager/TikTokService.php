<?php
namespace App\Library\SocialManager;

use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Support\Facades\Http;
use App\Models\CustomerAccount;
use Random\RandomException;
use App\Models\Post;
use Carbon\Carbon;
use Exception;

class TikTokService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $baseUrl = 'https://open.tiktokapis.com'; // Updated to new API base URL

    public function __construct()
    {
        $this->clientId = config('services.tiktok.client_id');
        $this->clientSecret = config('services.tiktok.client_secret');
        $this->redirectUri = config('services.tiktok.redirect_uri');
    }

    /**
     * @return string
     */
    public function getPlatform(): string
    {
        return 'tiktok';
    }

    /**
     * Fetch App-level Client Access Token (for Research API calls)
     */
    public function getClientAccessToken(): ?string
    {
        try {
            $response = Http::asForm()
                ->withHeaders([
                    'Cache-Control' => 'no-cache',
                ])
                ->post("{$this->baseUrl}/v2/oauth/token/", [
                    'client_key' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'client_credentials',
                ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            return $data['access_token'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Generate authorization URL with PKCE
     * @param array $scopes
     * @return string
     * @throws RandomException
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        $defaultScopes = [
            'user.info.basic',
            'user.info.profile',
            'user.info.stats',
            'video.list',
            'video.upload'
        ];

        $scopes = empty($scopes) ? $defaultScopes : $scopes;
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
     */
    public function exchangeCodeForToken(string $code): array
    {
        try {
            $codeVerifier = session('tiktok_code_verifier');

            $response = Http::asForm()
                ->withHeaders([
                    'Cache-Control' => 'no-cache',
                ])
                ->post("{$this->baseUrl}/v2/oauth/token/", [
                    'client_key' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $this->redirectUri,
                    'code_verifier' => $codeVerifier, // Add PKCE code verifier
                ]);

            $data = $response->json();

            if (isset($data['error'])) {
                return [
                    'header_code' => ResponseAlias::HTTP_BAD_REQUEST,
                    'body' => $data['error_description'] ?? $data['error']
                ];
            }

            // Clear the code verifier from session
            session()->forget('tiktok_code_verifier');

            return $data;
        } catch (Exception $e) {
            return [
                'header_code' => ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                'body' => 'Token exchange failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Refresh access token using refresh token
     * @param string $refreshToken
     * @return array
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $response = Http::asForm()
                ->withHeaders([
                    'Cache-Control' => 'no-cache',
                ])
                ->post("{$this->baseUrl}/v2/oauth/token/", [
                    'client_key' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ]);

            if (!$response->successful()) {
                return [
                    'header_code' => ResponseAlias::HTTP_EXPECTATION_FAILED,
                    'body' => 'Failed to refresh token: ' . $response->body()
                ];
            }

            return $response->json();
        } catch (Exception $e) {
            return [
                'header_code' => ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                'body' => 'Token refresh failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Auto-refresh token if needed
     * @param CustomerAccount $account
     * @return bool
     */
    public function autoRefreshTokenIfNeeded(CustomerAccount $account): bool
    {
        try {
            // Check if token expires within next 1 hour
            if ($account->token_expires_at &&
                Carbon::parse($account->token_expires_at)->subHour()->isPast()) {

                if (!$account->refresh_token) {
                    return false;
                }

                $refreshResult = $this->refreshToken($account->refresh_token);

                if (isset($refreshResult['header_code'])) {
                    return false;
                }

                // Update account with new tokens
                $account->update([
                    'access_token' => $refreshResult['access_token'],
                    'refresh_token' => $refreshResult['refresh_token'] ?? $account->refresh_token,
                    'token_expires_at' => now()->addSeconds($refreshResult['expires_in']),
                    'last_synced_at' => now()
                ]);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validate access token
     * @param string $accessToken
     * @return bool
     */
    public function validateToken(string $accessToken): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->get("{$this->baseUrl}/v2/user/info/", [
                'fields' => 'open_id,display_name'
            ]);

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get comprehensive user profile with all available fields
     * @param string $userAccessToken
     * @return array
     */
    public function getUserProfile(string $userAccessToken): array
    {
        try {
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

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$userAccessToken}",
            ])->get("{$this->baseUrl}/v2/user/info/", [
                'fields' => implode(',', $fields)
            ]);

            if (!$response->successful()) {
                return [
                    'header_code' => ResponseAlias::HTTP_BAD_REQUEST,
                    'body' => $response->json()
                ];
            }

            $user = $response->json()['data']['user'] ?? [];

            return [
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
            ];
        } catch (Exception $e) {
            return [
                'header_code' => ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                'body' => 'Failed to get user profile: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's video list with comprehensive metadata
     * @param string $userAccessToken
     * @param int $maxCount
     * @param int|null $cursor
     * @return array
     */
    public function getUserVideos(string $userAccessToken, int $maxCount = 20, ?int $cursor = null): array
    {
        try {
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

            $params = [
                'fields' => implode(',', $fields),
                'max_count' => min($maxCount, 20) // API limit is 20
            ];

            if ($cursor) {
                $params['cursor'] = $cursor;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$userAccessToken}",
            ])->get("{$this->baseUrl}/v2/video/list/", $params);

            if (!$response->successful()) {
                return [
                    'videos' => [],
                    'cursor' => null,
                    'has_more' => false,
                    'error' => $response->json()
                ];
            }

            $data = $response->json()['data'] ?? [];

            return [
                'videos' => $data['videos'] ?? [],
                'cursor' => $data['cursor'] ?? null,
                'has_more' => $data['has_more'] ?? false,
                'total' => count($data['videos'] ?? [])
            ];
        } catch (Exception $e) {
            return [
                'videos' => [],
                'cursor' => null,
                'has_more' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Query specific videos by IDs
     * @param string $userAccessToken
     * @param array $videoIds
     * @return array
     */
    public function queryVideos(string $userAccessToken, array $videoIds): array
    {
        try {
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

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$userAccessToken}",
            ])->post("{$this->baseUrl}/v2/video/query/", [
                'fields' => implode(',', $fields),
                'video_ids' => $videoIds
            ]);

            if (!$response->successful()) {
                return [
                    'videos' => [],
                    'error' => $response->json()
                ];
            }

            $data = $response->json()['data'] ?? [];

            return [
                'videos' => $data['videos'] ?? [],
                'total' => count($data['videos'] ?? [])
            ];
        } catch (Exception $e) {
            return [
                'videos' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload video to TikTok
     * @param string $accessToken
     * @param string $filePath
     * @param array $metadata
     * @return array
     * @throws Exception
     */
    public function uploadMedia(string $accessToken, string $filePath, array $metadata = []): array
    {
        try {
            // Step 1: Initialize upload
            $fileSize = filesize($filePath);
            $chunkSize = min(10 * 1024 * 1024, $fileSize); // 10MB or file size if smaller
            $totalChunks = ceil($fileSize / $chunkSize);

            $initResponse = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/v2/post/publish/video/init/", [
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
            ]);

            if (!$initResponse->successful()) {
                return [
                    'header_code' => ResponseAlias::HTTP_EXPECTATION_FAILED,
                    'body' => 'Failed to initialize upload: ' . $initResponse->body()
                ];
            }

            $uploadData = $initResponse->json()['data'];
            $publishId = $uploadData['publish_id'];
            $uploadUrl = $uploadData['upload_url'];

            // Step 2: Upload video file
            $uploadResponse = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->attach(
                'video',
                file_get_contents($filePath),
                basename($filePath)
            )->post($uploadUrl);

            if (!$uploadResponse->successful()) {
                throw new Exception('Failed to upload video: ' . $uploadResponse->body());
            }

            return [
                'publish_id' => $publishId,
                'upload_url' => $uploadUrl,
                'status' => 'uploaded'
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Publish post to TikTok
     * @param CustomerAccount $account
     * @param Post $post
     * @return array
     * @throws Exception
     */
    public function publishPost(CustomerAccount $account, Post $post): array
    {
        try {
            // Auto-refresh token if needed
            if (!$this->autoRefreshTokenIfNeeded($account)) {
                return [
                    'header_code' => ResponseAlias::HTTP_UNAUTHORIZED,
                    'body' => 'Token expired and refresh failed'
                ];
            }

            // TikTok only supports single video uploads
            if (count($post->files) === 0) {
                return [
                    'header_code' => ResponseAlias::HTTP_BAD_REQUEST,
                    'body' => 'No video file provided'
                ];
            }

            $videoFile = $post->files->first();
            $filePath = storage_path('app/' . $videoFile->file_path);

            if (!file_exists($filePath)) {
                return [
                    'header_code' => ResponseAlias::HTTP_BAD_REQUEST,
                    'body' => 'Video file not found'
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

            if (isset($uploadResult['header_code'])) {
                return $uploadResult;
            }

            // Check publish status
            $statusResponse = Http::withHeaders([
                'Authorization' => "Bearer {$account->access_token}",
            ])->post("{$this->baseUrl}/v2/post/publish/status/fetch/", [
                'publish_id' => $uploadResult['publish_id']
            ]);

            $statusData = $statusResponse->successful() ? $statusResponse->json()['data'] : [];

            return [
                'platform_post_id' => $uploadResult['publish_id'],
                'status' => $statusData['status'] ?? 'processing',
                'platform_url' => $statusData['share_url'] ?? null,
                'publish_id' => $uploadResult['publish_id']
            ];
        } catch (Exception $e) {
            return [
                'header_code' => ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                'body' => 'Failed to publish post: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get comprehensive post analytics
     * @param CustomerAccount $account
     * @param string $platformPostId
     * @return array
     */
    public function getPostAnalytics(CustomerAccount $account, string $platformPostId): array
    {
        try {
            // Auto-refresh token if needed
            $this->autoRefreshTokenIfNeeded($account);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$account->access_token}",
            ])->post("{$this->baseUrl}/v2/video/query/", [
                'fields' => 'id,like_count,comment_count,share_count,view_count,create_time,video_description,cover_image_url,share_url,duration',
                'video_ids' => [$platformPostId]
            ]);

            if (!$response->successful()) {
                return [
                    'header_code' => ResponseAlias::HTTP_EXPECTATION_FAILED,
                    'body' => 'Failed to get analytics: ' . $response->body()
                ];
            }

            $videos = $response->json()['data']['videos'] ?? [];
            $video = $videos[0] ?? [];

            if (empty($video)) {
                return [
                    'header_code' => ResponseAlias::HTTP_NOT_FOUND,
                    'body' => 'Video not found'
                ];
            }

            // Calculate engagement rate
            $totalEngagement = ($video['like_count'] ?? 0) +
                ($video['comment_count'] ?? 0) +
                ($video['share_count'] ?? 0);
            $views = $video['view_count'] ?? 1;
            $engagementRate = $views > 0 ? round(($totalEngagement / $views) * 100, 2) : 0;

            return [
                'views' => $video['view_count'] ?? 0,
                'likes' => $video['like_count'] ?? 0,
                'shares' => $video['share_count'] ?? 0,
                'comments' => $video['comment_count'] ?? 0,
                'saves' => 0, // TikTok API doesn't provide saves count
                'engagement_rate' => $engagementRate,
                'duration' => $video['duration'] ?? 0,
                'create_time' => $video['create_time'] ?? null,
                'cover_image_url' => $video['cover_image_url'] ?? null,
                'share_url' => $video['share_url'] ?? null,
                'additional_metrics' => [
                    'total_engagement' => $totalEngagement,
                    'video_id' => $video['id'] ?? null,
                    'description' => $video['video_description'] ?? null
                ]
            ];
        } catch (Exception $e) {
            return [
                'header_code' => ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                'body' => 'Failed to get analytics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Revoke user access token
     * @param string $accessToken
     * @return bool
     */
    public function revokeAccess(string $accessToken): bool
    {
        try {
            $response = Http::asForm()
                ->withHeaders([
                    'Cache-Control' => 'no-cache',
                ])
                ->post("{$this->baseUrl}/v2/oauth/revoke/", [
                    'client_key' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'token' => $accessToken,
                ]);

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return string[]
     */
    public function getSupportedMediaTypes(): array
    {
        return ['video']; // TikTok only supports videos
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
