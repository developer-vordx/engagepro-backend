<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Exception;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class YouTubeService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $baseUrl;
    private mixed $scopes;
    private string $platform;
    private mixed $supportedMediaTypes;
    private string $accessToken;

    public function __construct()
    {
        $platForm = SocialAccount::where('slug', 'youtube')->first();
        $this->baseUrl = $platForm->url;
        $this->scopes = $platForm->scopes;
        
        // YouTube OAuth 2.0 credentials
        $this->clientId = config('services.youtube.client_id', '');
        $this->clientSecret = config('services.youtube.client_secret', '');
        $this->redirectUri = config('services.youtube.redirect_uri', '');
        $this->accessToken = config('services.youtube.access_token', '');
        
        $this->platform = $platForm->slug;
        $this->supportedMediaTypes = $platForm->supported_media_types;
    }

    /**
     * @return string
     */
    public function getPlatform(): string
    {
        return 'youtube';
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
     * Generate authorization URL for YouTube OAuth 2.0
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
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $scopes),
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => csrf_token(),
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     * @param string $code
     * @return array
     */
    public function exchangeCodeForToken(string $code): array
    {
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];

        $url = 'https://oauth2.googleapis.com/token';
        
        $response = Helper::makeHttpRequest('POST', $url, $data, [], false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to exchange code for token');
        }

        return $response;
    }

    /**
     * Refresh access token using refresh token
     * @param string $refreshToken
     * @return array
     */
    public function refreshToken(string $refreshToken): array
    {
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        $url = 'https://oauth2.googleapis.com/token';
        
        $response = Helper::makeHttpRequest('POST', $url, $data, [], false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to refresh token');
        }

        return $response;
    }

    /**
     * Get user's YouTube channels
     * @param string $accessToken
     * @return array
     */
    public function getUserChannels(string $accessToken): array
    {
        $url = 'https://www.googleapis.com/youtube/v3/channels';
        $params = [
            'part' => 'snippet,contentDetails,statistics',
            'mine' => 'true',
            'access_token' => $accessToken
        ];

        $response = Helper::makeHttpRequest('GET', $url, $params, [], false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get user channels');
        }

        return $response;
    }

    /**
     * Upload video to YouTube
     * @param PostFile $videoFile
     * @param Post $post
     * @param string $accessToken
     * @return array
     */
    public function uploadVideo(PostFile $videoFile, Post $post, string $accessToken): array
    {
        try {
            // Validate video file
            if (!$this->isValidVideoType($videoFile->mime_type)) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Invalid video type for YouTube');
            }

            // Get file path
            $filePath = Storage::disk('private')->path($videoFile->file_path);
            
            if (!file_exists($filePath)) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Video file not found');
            }

            // Prepare video metadata
            $videoMetadata = [
                'snippet' => [
                    'title' => $post->title ?: 'Untitled Video',
                    'description' => $post->description ?: '',
                    'tags' => $post->tags ?: [],
                    'categoryId' => '22', // People & Blogs (default)
                    'defaultLanguage' => 'en',
                    'defaultAudioLanguage' => 'en'
                ],
                'status' => [
                    'privacyStatus' => 'private', // Start as private for safety
                    'selfDeclaredMadeForKids' => false
                ]
            ];

            // Upload video using YouTube Data API v3 with direct cURL for multipart upload
            $url = 'https://www.googleapis.com/upload/youtube/v3/videos';
            $params = [
                'part' => 'snippet,status',
                'uploadType' => 'multipart',
                'access_token' => $accessToken
            ];

            // Prepare multipart data
            $boundary = '----WebKitFormBoundary' . uniqid();
            $postData = $this->buildMultipartData($videoMetadata, $filePath, $boundary);
            
            $headers = [
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'multipart/related; boundary=' . $boundary,
                'Content-Length' => strlen($postData)
            ];

            // Use direct cURL for multipart video upload
            $ch = curl_init();
            
            // Convert headers array to proper format for cURL
            $curlHeaders = [];
            foreach ($headers as $key => $value) {
                $curlHeaders[] = "{$key}: {$value}";
            }
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url . '?' . http_build_query($params),
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => $curlHeaders,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 300, // 5 minutes for video upload
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            if ($error) {
                return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'cURL error: ' . $error);
            }
            
            if ($httpCode != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($httpCode, 'Video upload failed: ' . $response);
            }
            
            $responseData = json_decode($response, true);
            if (!$responseData) {
                return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Invalid response from YouTube API');
            }

            return [
                'header_code' => $httpCode,
                'body' => [
                    'video_id' => $responseData['id'] ?? 'unknown',
                    'platform_url' => "https://www.youtube.com/watch?v=" . ($responseData['id'] ?? 'unknown'),
                    'status' => 'uploaded',
                    'platform' => 'youtube'
                ]
            ];

        } catch (Exception $e) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Video upload error: ' . $e->getMessage());
        }
    }

    /**
     * Update video metadata
     * @param string $videoId
     * @param Post $post
     * @param string $accessToken
     * @return array
     */
    public function updateVideoMetadata(string $videoId, Post $post, string $accessToken): array
    {
        $url = "https://www.googleapis.com/youtube/v3/videos";
        
        $data = [
            'id' => $videoId,
            'part' => 'snippet,status',
            'snippet' => [
                'title' => $post->title ?: 'Untitled Video',
                'description' => $post->description ?: '',
                'tags' => $post->tags ?: [],
                'categoryId' => '22'
            ],
            'status' => [
                'privacyStatus' => 'public' // Make public after metadata update
            ]
        ];

        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json'
        ];

        $response = Helper::makeHttpRequest('PUT', $url, json_encode($data), $headers, false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to update video metadata');
        }

        return $response;
    }

    /**
     * Create playlist
     * @param string $title
     * @param string $description
     * @param string $accessToken
     * @return array
     */
    public function createPlaylist(string $title, string $description, string $accessToken): array
    {
        $url = 'https://www.googleapis.com/youtube/v3/playlists';
        
        $data = [
            'part' => 'snippet,status',
            'snippet' => [
                'title' => $title,
                'description' => $description,
                'tags' => ['auto-generated']
            ],
            'status' => [
                'privacyStatus' => 'private'
            ]
        ];

        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json'
        ];

        $response = Helper::makeHttpRequest('POST', $url, json_encode($data), $headers, false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to create playlist');
        }

        return $response;
    }

    /**
     * Add video to playlist
     * @param string $playlistId
     * @param string $videoId
     * @param string $accessToken
     * @return array
     */
    public function addVideoToPlaylist(string $playlistId, string $videoId, string $accessToken): array
    {
        $url = 'https://www.googleapis.com/youtube/v3/playlistItems';
        
        $data = [
            'part' => 'snippet',
            'snippet' => [
                'playlistId' => $playlistId,
                'resourceId' => [
                    'kind' => 'youtube#video',
                    'videoId' => $videoId
                ]
            ]
        ];

        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json'
        ];

        $response = Helper::makeHttpRequest('POST', $url, json_encode($data), $headers, false, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to add video to playlist');
        }

        return $response;
    }

    /**
     * Get video analytics
     * @param string $videoId
     * @param string $accessToken
     * @return array
     */
    public function getVideoAnalytics(string $videoId, string $accessToken): array
    {
        $url = 'https://www.googleapis.com/youtube/v3/videos';
        $params = [
            'part' => 'statistics,snippet,contentDetails',
            'id' => $videoId
        ];

        $headers = ['Authorization' => "Bearer {$accessToken}"];
        
        $response = Helper::makeHttpRequest('GET', $url, $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get video analytics');
        }

        // Enhanced analytics processing
        if (isset($response['body']['items'][0])) {
            $video = $response['body']['items'][0];
            $statistics = $video['statistics'] ?? [];
            $snippet = $video['snippet'] ?? [];
            $contentDetails = $video['contentDetails'] ?? [];

            $analytics = [
                'video_id' => $videoId,
                'title' => $snippet['title'] ?? '',
                'description' => $snippet['description'] ?? '',
                'published_at' => $snippet['publishedAt'] ?? '',
                'duration' => $contentDetails['duration'] ?? '',
                'dimension' => $contentDetails['dimension'] ?? '',
                'definition' => $contentDetails['definition'] ?? '',
                'caption' => $contentDetails['caption'] ?? '',
                'licensed_content' => $contentDetails['licensedContent'] ?? false,
                'content_rating' => $contentDetails['contentRating'] ?? [],
                'projection' => $contentDetails['projection'] ?? '',
                'statistics' => [
                    'view_count' => (int)($statistics['viewCount'] ?? 0),
                    'like_count' => (int)($statistics['likeCount'] ?? 0),
                    'dislike_count' => (int)($statistics['dislikeCount'] ?? 0),
                    'favorite_count' => (int)($statistics['favoriteCount'] ?? 0),
                    'comment_count' => (int)($statistics['commentCount'] ?? 0)
                ]
            ];

            return [
                'header_code' => $response['header_code'],
                'body' => $analytics
            ];
        }

        return $this->errorResponse(ResponseAlias::HTTP_NOT_FOUND, 'Video not found');
    }

    /**
     * Validate video type for YouTube
     * @param string $mimeType
     * @return bool
     */
    public function isValidVideoType(string $mimeType): bool
    {
        $supportedTypes = [
            'video/mp4',
            'video/mov',
            'video/avi',
            'video/wmv',
            'video/flv',
            'video/webm',
            'video/mkv'
        ];

        return in_array($mimeType, $supportedTypes);
    }

    /**
     * Build multipart data for video upload
     * @param array $metadata
     * @param string $filePath
     * @param string $boundary
     * @return string
     */
    private function buildMultipartData(array $metadata, string $filePath, string $boundary): string
    {
        $data = '';
        
        // Add metadata part
        $data .= "--{$boundary}\r\n";
        $data .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $data .= json_encode($metadata) . "\r\n";
        
        // Add file part
        $data .= "--{$boundary}\r\n";
        $data .= "Content-Type: video/mp4\r\n\r\n";
        $data .= file_get_contents($filePath) . "\r\n";
        
        $data .= "--{$boundary}--\r\n";
        
        return $data;
    }

    /**
     * Get posting limits
     * @return array
     */
    public function getPostingLimits(): array
    {
        return [
            'max_video_size' => '128GB',
            'max_video_duration' => '12 hours',
            'supported_formats' => ['MP4', 'MOV', 'AVI', 'WMV', 'FLV', 'WebM', 'MKV'],
            'daily_upload_limit' => '100 videos',
            'channel_creation_limit' => '50 channels'
        ];
    }

    /**
     * Get available scopes (Latest YouTube Data API v3)
     * @return array
     */
    public function getAvailableScopes(): array
    {
        return [
            'https://www.googleapis.com/auth/youtube' => 'Manage your YouTube account (upload, update, delete videos)',
            'https://www.googleapis.com/auth/youtube.upload' => 'Upload videos and manage uploads',
            'https://www.googleapis.com/auth/youtube.readonly' => 'View your YouTube account data (read-only)',
            'https://www.googleapis.com/auth/youtube.force-ssl' => 'Manage your YouTube data (HTTPS)',
            'https://www.googleapis.com/auth/youtubepartner' => 'Manage YouTube partnership features',
            'https://www.googleapis.com/auth/youtubepartner-channel-audit' => 'View private information of your YouTube channel',
            'https://www.googleapis.com/auth/youtube.channel-memberships.creator' => 'View your YouTube memberships',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['video'];
    }

    /**
     * Get user profile from YouTube
     * @param string $userAccessToken
     * @return array
     * @throws ConnectionException
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        $params = [
            'part' => 'snippet,statistics,contentDetails',
            'mine' => 'true'
        ];

        $response = Helper::makeHttpRequest('GET', 'https://www.googleapis.com/youtube/v3/channels', $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        $channel = $response['body']['items'][0] ?? [];
        $snippet = $channel['snippet'] ?? [];
        $statistics = $channel['statistics'] ?? [];
        $contentDetails = $channel['contentDetails'] ?? [];

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $channel['id'] ?? null,
                'username' => $snippet['customUrl'] ?? $snippet['title'] ?? null,
                'display_name' => $snippet['title'] ?? null,
                'profile_picture' => $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['default']['url'] ?? null,
                'bio_description' => $snippet['description'] ?? null,
                'is_verified' => $snippet['verified'] ?? false,
                'follower_count' => (int)($statistics['subscriberCount'] ?? 0),
                'following_count' => 0, // YouTube doesn't have following count
                'video_count' => (int)($statistics['videoCount'] ?? 0),
                'view_count' => (int)($statistics['viewCount'] ?? 0),
                'created_at' => $snippet['publishedAt'] ?? null,
                'country' => $snippet['country'] ?? null,
                'language' => $snippet['defaultLanguage'] ?? null,
                'privacy_status' => $contentDetails['privacyStatus'] ?? null,
                'upload_playlist_id' => $contentDetails['relatedPlaylists']['uploads'] ?? null,
            ]
        ];
    }

    /**
     * Search videos on YouTube
     * @param string $query
     * @param string $accessToken
     * @param array $options
     * @return array
     */
    public function searchVideos(string $query, string $accessToken, array $options = []): array
    {
        $params = [
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'maxResults' => $options['maxResults'] ?? 25,
            'order' => $options['order'] ?? 'relevance', // relevance, date, rating, viewCount, title, videoCount
            'publishedAfter' => $options['publishedAfter'] ?? null,
            'publishedBefore' => $options['publishedBefore'] ?? null,
            'videoDuration' => $options['videoDuration'] ?? null, // short, medium, long
            'videoDefinition' => $options['videoDefinition'] ?? null, // high, standard
            'videoEmbeddable' => $options['videoEmbeddable'] ?? null, // true, any
            'videoLicense' => $options['videoLicense'] ?? null, // youtube, creativeCommon
            'videoSyndicated' => $options['videoSyndicated'] ?? null, // true, any
            'videoType' => $options['videoType'] ?? null, // any, episode, movie
        ];

        // Remove null values
        $params = array_filter($params, function($value) {
            return $value !== null;
        });

        $headers = ['Authorization' => "Bearer {$accessToken}"];
        
        $response = Helper::makeHttpRequest('GET', 'https://www.googleapis.com/youtube/v3/search', $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Video search failed');
        }

        return $response;
    }

    /**
     * Get video details
     * @param string $videoId
     * @param string $accessToken
     * @param array $parts
     * @return array
     */
    public function getVideoDetails(string $videoId, string $accessToken, array $parts = ['snippet', 'statistics', 'contentDetails']): array
    {
        $params = [
            'part' => implode(',', $parts),
            'id' => $videoId
        ];

        $headers = ['Authorization' => "Bearer {$accessToken}"];
        
        $response = Helper::makeHttpRequest('GET', 'https://www.googleapis.com/youtube/v3/videos', $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get video details');
        }

        return $response;
    }

    /**
     * Rate a video (like/dislike)
     * @param string $videoId
     * @param string $rating (like, dislike, none)
     * @param string $accessToken
     * @return array
     */
    public function rateVideo(string $videoId, string $rating, string $accessToken): array
    {
        $url = "https://www.googleapis.com/youtube/v3/videos/rate";
        $params = [
            'id' => $videoId,
            'rating' => $rating
        ];

        $headers = ['Authorization' => "Bearer {$accessToken}"];
        
        $response = Helper::makeHttpRequest('POST', $url, $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to rate video');
        }

        return $response;
    }

    /**
     * Get video rating
     * @param string $videoId
     * @param string $accessToken
     * @return array
     */
    public function getVideoRating(string $videoId, string $accessToken): array
    {
        $url = "https://www.googleapis.com/youtube/v3/videos/getRating";
        $params = ['id' => $videoId];

        $headers = ['Authorization' => "Bearer {$accessToken}"];
        
        $response = Helper::makeHttpRequest('GET', $url, $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get video rating');
        }

        return $response;
    }

    /**
     * Report video abuse
     * @param string $videoId
     * @param string $reasonId
     * @param string $accessToken
     * @param string $comments
     * @return array
     */
    public function reportVideoAbuse(string $videoId, string $reasonId, string $accessToken, string $comments = ''): array
    {
        $url = "https://www.googleapis.com/youtube/v3/videos/reportAbuse";
        $data = [
            'videoId' => $videoId,
            'reasonId' => $reasonId
        ];

        if (!empty($comments)) {
            $data['comments'] = $comments;
        }

        $headers = ['Authorization' => "Bearer {$accessToken}"];
        
        $response = Helper::makeHttpRequest('POST', $url, json_encode($data), $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to report video abuse');
        }

        return $response;
    }

    /**
     * Get video abuse report reasons
     * @param string $accessToken
     * @param string $hl
     * @return array
     */
    public function getVideoAbuseReportReasons(string $accessToken, string $hl = 'en_US'): array
    {
        $url = "https://www.googleapis.com/youtube/v3/videoAbuseReportReasons";
        $params = ['hl' => $hl];

        $headers = ['Authorization' => "Bearer {$accessToken}"];
        
        $response = Helper::makeHttpRequest('GET', $url, $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get abuse report reasons');
        }

        return $response;
    }

    /**
     * Set custom video thumbnail
     * @param string $videoId
     * @param string $imagePath
     * @param string $accessToken
     * @return array
     */
    public function setVideoThumbnail(string $videoId, string $imagePath, string $accessToken): array
    {
        if (!file_exists($imagePath)) {
            return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'Thumbnail image not found');
        }

        $url = "https://www.googleapis.com/upload/youtube/v3/thumbnails/set";
        $params = [
            'videoId' => $videoId,
            'uploadType' => 'media'
        ];

        $imageData = file_get_contents($imagePath);
        $mimeType = mime_content_type($imagePath);

        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => $mimeType,
            'Content-Length' => strlen($imageData)
        ];

        // Use direct cURL for media upload
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url . '?' . http_build_query($params),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $imageData,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'cURL error: ' . $error);
        }
        
        if ($httpCode != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($httpCode, 'Failed to set thumbnail: ' . $response);
        }
        
        $responseData = json_decode($response, true);
        if (!$responseData) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Invalid response from YouTube API');
        }

        return [
            'header_code' => $httpCode,
            'body' => $responseData
        ];
    }

    /**
     * Get video categories
     * @param string $accessToken
     * @param string $regionCode
     * @param string $hl
     * @return array
     */
    public function getVideoCategories(string $accessToken, string $regionCode = 'US', string $hl = 'en_US'): array
    {
        $url = "https://www.googleapis.com/youtube/v3/videoCategories";
        $params = [
            'part' => 'snippet',
            'regionCode' => $regionCode,
            'hl' => $hl
        ];

        $headers = ['Authorization' => "Bearer {$accessToken}"];
        
        $response = Helper::makeHttpRequest('GET', $url, $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get video categories');
        }

        return $response;
    }

    /**
     * Get channel activities
     * @param string $channelId
     * @param string $accessToken
     * @param int $maxResults
     * @return array
     */
    public function getChannelActivities(string $channelId, string $accessToken, int $maxResults = 25): array
    {
        $url = "https://www.googleapis.com/youtube/v3/activities";
        $params = [
            'part' => 'snippet,contentDetails',
            'channelId' => $channelId,
            'maxResults' => min($maxResults, 50)
        ];

        $headers = ['Authorization' => "Bearer {$accessToken}"];
        
        $response = Helper::makeHttpRequest('GET', $url, $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get channel activities');
        }

        return $response;
    }

    /**
     * Get supported languages
     * @param string $accessToken
     * @return array
     */
    public function getSupportedLanguages(string $accessToken): array
    {
        $url = "https://www.googleapis.com/youtube/v3/i18nLanguages";
        $params = ['part' => 'snippet'];

        $headers = ['Authorization' => "Bearer {$accessToken}"];
        
        $response = Helper::makeHttpRequest('GET', $url, $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get supported languages');
        }

        return $response;
    }

    /**
     * Get supported regions
     * @param string $accessToken
     * @return array
     */
    public function getSupportedRegions(string $accessToken): array
    {
        $url = "https://www.googleapis.com/youtube/v3/i18nRegions";
        $params = ['part' => 'snippet'];

        $headers = ['Authorization' => "Bearer {$accessToken}"];
        
        $response = Helper::makeHttpRequest('GET', $url, $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get supported regions');
        }

        return $response;
    }

    /**
     * Get channel statistics
     * @param string $channelId
     * @param string $accessToken
     * @return array
     */
    public function getChannelStatistics(string $channelId, string $accessToken): array
    {
        $url = "https://www.googleapis.com/youtube/v3/channels";
        $params = [
            'part' => 'statistics',
            'id' => $channelId
        ];

        $headers = ['Authorization' => "Bearer {$accessToken}"];
        
        $response = Helper::makeHttpRequest('GET', $url, $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to get channel statistics');
        }

        return $response;
    }

    /**
     * Validate content for YouTube (wrapper for consistency)
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

            $fileSize = filesize($filePath);
            $mimeType = mime_content_type($filePath);

            // Validate video type
            if (!$this->isValidVideoType($mimeType)) {
                $errors[] = "Invalid video type: {$mimeType}. YouTube accepts MP4, MOV, AVI, WMV, FLV, WebM, MKV";
            }

            // Validate file size (128GB max, 256GB for verified)
            if ($fileSize > 128 * 1024 * 1024 * 1024) {
                $errors[] = "Video exceeds 128GB limit";
            }

            // Check unverified account limit (15 minutes)
            if ($fileSize > 1 * 1024 * 1024 * 1024) {
                $warnings[] = "Videos over 1GB may be limited to 15 minutes for unverified accounts";
            }
        }

        // Validate metadata
        if (isset($metadata['title']) && strlen($metadata['title']) > 100) {
            $errors[] = "Title exceeds 100 character limit";
        }

        if (isset($metadata['description']) && strlen($metadata['description']) > 5000) {
            $errors[] = "Description exceeds 5000 character limit";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Publish post (wrapper that calls uploadVideo for consistency)
     * @param CustomerAccount $account
     * @param Post $post
     * @param array $content
     * @return array
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        try {
            // Get first video file
            $videoFile = $post->postFiles->first();
            
            if (!$videoFile) {
                return $this->errorResponse(ResponseAlias::HTTP_BAD_REQUEST, 'No video file found for YouTube upload');
            }

            // Upload video using existing uploadVideo method
            return $this->uploadVideo($videoFile, $post, $account->access_token);

        } catch (\Exception $e) {
            return $this->errorResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, 'Publishing failed: ' . $e->getMessage());
        }
    }
}
