<?php

namespace App\Library\SocialManager;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use App\Models\SocialAccount;
use App\Models\Post;
use Exception;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class TikTokService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $baseUrl = 'https://open-api.tiktok.com';

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
     * @param array $scopes
     * @return string
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        $defaultScopes = [
            'user.info.basic', 'video.upload', 'video.publish'
        ];

        $scopes = empty($scopes) ? $defaultScopes : $scopes;

        $params = [
            'client_key'    => $this->clientId,
            'scope'         => implode(',', $scopes),
            'response_type' => 'code',
            'redirect_uri'  => $this->redirectUri,
            'state'         => csrf_token(),
        ];

        return 'https://www.tiktok.com/v2/auth/authorize?' . http_build_query($params);
    }

    /**
     * @param string $code
     * @return array
     * @throws Exception
     */
    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()->post("https://open.tiktokapis.com/v2/oauth/token/", [
            'client_key'    => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->redirectUri,
        ]);

        $data = $response->json();
        if (isset($data['error'])) {
            return [
                'header_code' => ResponseAlias::HTTP_BAD_REQUEST,
                'body' => $data['error_description']
            ];
        }
        return $data;
    }

    /**
     * @param string $refreshToken
     * @return array
     * @throws Exception
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = Http::post("{$this->baseUrl}/oauth/token/", [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if (!$response->successful()) {
            return [
                'header_code' => ResponseAlias::HTTP_EXPECTATION_FAILED,
                'body' => 'Failed to exchange code for token: ' . $response->body()

            ];
        }

        return $response->json()['data'];
    }

    /**
     * @param string $accessToken
     * @return bool
     */
    public function validateToken(string $accessToken): bool
    {
        try {
            $response = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/v2/user/info/", [
                    'fields' => 'open_id,display_name'
                ]);

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $accessToken
     * @return array
     * @throws ConnectionException
     * @throws Exception
     */
    public function getUserProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/v2/user/info/", [
                'fields' => 'open_id,display_name,avatar_url'
            ]);

        $data = $response->json();

        if (isset($data['error']) && !isset($data['data'])) {
            return [
                'header_code' => ResponseAlias::HTTP_EXPECTATION_FAILED,
                'body' => $data['error']['message']
            ];
        }

        $user = $data['data']['user'] ?? [];

        return [
            'identifier' => $user['open_id'] ?? null,
            'username' => $user['display_name'] ?? null,
            'profile_picture' => $user['avatar_url'] ?? null,
            'follower_count' => $user['follower_count'] ?? 0,
            'following_count' => $user['following_count'] ?? 0,
        ];
    }

    /**
     * @param string $accessToken
     * @param string $filePath
     * @param array $metadata
     * @return array
     * @throws ConnectionException
     * @throws Exception
     */
    public function uploadMedia(string $accessToken, string $filePath, array $metadata = []): array
    {
        // Step 1: Initialize upload
        $initResponse = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/v2/upload/video/init/", [
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => filesize($filePath),
                    'chunk_size' => 10000000, // 10MB chunks
                    'total_chunk_count' => 1,
                ]
            ]);

        if (!$initResponse->successful()) {
            return [
                'header_code' => ResponseAlias::HTTP_EXPECTATION_FAILED,
                'body' => 'Failed to initialize upload: ' . $initResponse->body()
            ];
        }

        $uploadData = $initResponse->json()['data'];

        // Step 2: Upload video file
        $uploadResponse = Http::withToken($accessToken)
            ->attach('video', file_get_contents($filePath), basename($filePath))
            ->post($uploadData['upload_url']);

        if (!$uploadResponse->successful()) {
            throw new Exception('Failed to upload video: ' . $uploadResponse->body());
        }

        return [
            'media_id' => $uploadData['publish_id'],
            'upload_id' => $uploadData['upload_id'],
        ];
    }

    /**
     * @param SocialAccount $account
     * @param Post $post
     * @return array
     * @throws ConnectionException
     * @throws Exception
     */
    public function publishPost(SocialAccount $account, Post $post): array
    {
        $mediaIds = [];

        // Upload media files first
        foreach ($post->media_files as $filePath) {
            $uploadResult = $this->uploadMedia($account->access_token, $filePath);
            $mediaIds[] = $uploadResult['media_id'];
        }

        // Publish the post
        $response = Http::withToken($account->access_token)
            ->post("{$this->baseUrl}/v2/post/publish/video/", [
//                'post_info' => [
//                    'title' => $post->title ?? '',
//                    'description' => $post->description ?? '',
//                    'privacy_level' => 'SELF_ONLY', // or PUBLIC_TO_EVERYONE
//                    'disable_duet' => false,
//                    'disable_comment' => false,
//                    'disable_stitch' => false,
//                    'video_cover_timestamp_ms' => 1000,
//                ],
                'post_info' => [
                    'text' => $post->description ?? '',
                    'privacy_level' => 'SELF_ONLY',
                ],
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_id' => $mediaIds[0], // TikTok supports single video
                ]
            ]);

        if (!$response->successful()) {
            return [
                'header_code' => ResponseAlias::HTTP_EXPECTATION_FAILED,
                'body' => 'Failed to initialize upload: ' . $response->body()
            ];
        }

        $result = $response->json()['data'];

        return [
            'platform_post_id' => $result['publish_id'],
            'status' => 'published',
            'platform_url' => $result['share_url'] ?? null,
        ];
    }

    /**
     * @param SocialAccount $account
     * @param string $platformPostId
     * @return array
     * @throws ConnectionException
     */
    public function getPostAnalytics(SocialAccount $account, string $platformPostId): array
    {
        $response = Http::withToken($account->access_token)
            ->get("{$this->baseUrl}/v2/video/query/", [
                'video_ids' => $platformPostId
            ]);

        if (!$response->successful()) {
            return [
                'header_code' => ResponseAlias::HTTP_EXPECTATION_FAILED,
                'body' => 'Failed to get analytics: ' . $response->body()
            ];
        }

        $video = $response->json()['data']['videos'][0] ?? [];

        return [
            'views' => $video['view_count'] ?? 0,
            'likes' => $video['like_count'] ?? 0,
            'shares' => $video['share_count'] ?? 0,
            'comments' => $video['comment_count'] ?? 0,
            'saves' => 0, // TikTok doesn't provide saves in basic API
        ];
    }

    /**
     * @return string[]
     */
    public function getSupportedMediaTypes(): array
    {
        return ['video']; // TikTok only supports videos
    }

    /**
     * @param array $mediaFiles
     * @param array $metadata
     * @return array
     */
    public function validateContent(array $mediaFiles, array $metadata = []): array
    {
        $errors = [];
        $warnings = [];

        foreach ($mediaFiles as $filePath) {
            // Check file type
            $mimeType = mime_content_type($filePath);
            if (!str_starts_with($mimeType, 'video/')) {
                $errors[] = "TikTok only supports video files. {$filePath} is not a video.";
                continue;
            }

            // Check file size (max 287MB for TikTok)
            $fileSize = filesize($filePath);
            if ($fileSize > 287 * 1024 * 1024) {
                $errors[] = "Video file {$filePath} exceeds TikTok's 287MB limit.";
            }

            // Check duration (15 seconds to 10 minutes)
            // This would require FFmpeg or similar to get video duration
            // For now, we'll add a placeholder
            $warnings[] = "Please ensure video duration is between 15 seconds and 10 minutes.";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return int[]
     */
    public function getPostingLimits(): array
    {
        return [
            'max_posts_per_day' => 10,
            'max_posts_per_hour' => 3,
            'max_file_size_mb' => 287,
            'supported_formats' => ['mp4', 'mov', 'avi'],
            'min_duration_seconds' => 15,
            'max_duration_seconds' => 600,
        ];
    }
}
