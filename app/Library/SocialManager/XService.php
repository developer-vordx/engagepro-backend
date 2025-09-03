<?php

namespace App\Library\SocialManager;

use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Models\CustomerAccount;
use App\Models\PostFile;
use App\Models\SocialAccount;
use Random\RandomException;
use App\Models\Post;
use Carbon\Carbon;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Exception;

class XService
{
    private string $apiKey;
    private string $apiSecret;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $baseUrl;
    private mixed $scopes;
    private string $platform;
    private mixed $supportedMediaTypes;
    private string $bearerToken;
    private string $accessToken;
    private string $accessTokenSecret;

    public function __construct()
    {
        $platForm = SocialAccount::where('slug', 'x')->first();
        $this->baseUrl = $platForm->url;
        $this->scopes = $platForm->scopes;
        // Use API Key and API Secret for OAuth 1.0a User Context
        $this->apiKey = config('services.twitter.api_key', 'xg9OiSiOdTWu826i7hszUYdqt');
        $this->apiSecret = config('services.twitter.api_secret', 'INJP9PyCzmQgOPHN6pWQ18PT23mWwldAJ0PDJvZFPUbvYJUDF0');
        // Use Client ID and Client Secret for OAuth 2.0 authorization flow
        $this->clientId = config('services.twitter.client_id', 'RDNFclNJZjZKT3ZabTd2S0NpZWE6MTpjaQ');
        $this->clientSecret = config('services.twitter.client_secret', '2HHHzBU1m5ekvBYAYAvlMGNUHe8BcGkK5Q-nn9qd1AgsYCuWyj');
        $this->redirectUri = config('services.twitter.redirect_uri');
        $this->bearerToken = config('services.twitter.bearer_token');
        $this->accessToken = config('services.twitter.access_token');
        $this->accessTokenSecret = config('services.twitter.access_token_secret');
        $this->platform = $platForm->slug;
        $this->supportedMediaTypes = $platForm->supported_media_types;
    }

    /**
     * @return string
     */
    public function getPlatform(): string
    {
        return 'x';
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
     * Generate authorization URL with PKCE
     * @param array $scopes
     * @return string
     * @throws RandomException
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        $scopes = empty($scopes) ? $this->scopes : $scopes;
        $codeVerifier = bin2hex(random_bytes(64));
        session(['x_code_verifier' => $codeVerifier]);

        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => csrf_token(),
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ];

        return 'https://twitter.com/i/oauth2/authorize?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     * @param string $code
     * @return array
     * @throws ConnectionException
     */
    public function exchangeCodeForToken(string $code): array
    {
        $codeVerifier = session('x_code_verifier');

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ];

        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'code_verifier' => $codeVerifier,
        ];

        $response = Helper::makeHttpRequest('POST', 'https://api.twitter.com/2/oauth2/token', $data, $headers, false, $this->platform);

        // Clear the code verifier from session
        session()->forget('x_code_verifier');

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
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ];

        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        return Helper::makeHttpRequest('POST', 'https://api.twitter.com/2/oauth2/token', $data, $headers, false, $this->platform);
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

        $response = Helper::makeHttpRequest('GET', 'https://api.twitter.com/2/users/me', [], $headers);

        return $response && $response['success'];
    }

    /**
     * Get comprehensive user profile
     * @param string $userAccessToken
     * @return array
     * @throws ConnectionException
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        $params = [
            'user.fields' => 'id,name,username,profile_image_url,public_metrics,verified,description,created_at,location,url,entities'
        ];

        $response = Helper::makeHttpRequest('GET', 'https://api.twitter.com/2/users/me', $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        $user = $response['body']['data'] ?? [];

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['id'] ?? null,
                'username' => $user['username'] ?? null,
                'display_name' => $user['name'] ?? null,
                'profile_picture' => $user['profile_image_url'] ?? null,
                'bio_description' => $user['description'] ?? null,
                'is_verified' => $user['verified'] ?? false,
                'follower_count' => $user['public_metrics']['followers_count'] ?? 0,
                'following_count' => $user['public_metrics']['following_count'] ?? 0,
                'tweet_count' => $user['public_metrics']['tweet_count'] ?? 0,
                'listed_count' => $user['public_metrics']['listed_count'] ?? 0,
                'created_at' => $user['created_at'] ?? null,
                'location' => $user['location'] ?? null,
                'url' => $user['url'] ?? null,
            ]
        ];
    }

    /**
     * Upload media to X (Twitter)
     * @param string $accessToken
     * @param string $filePath
     * @param array $metadata
     * @return array
     * @throws ConnectionException
     */
    public function uploadMedia(string $accessToken, string $filePath, array $metadata = []): array
    {
        if (!file_exists($filePath)) {
            return [
                'header_code' => ResponseAlias::HTTP_BAD_REQUEST,
                'body' => 'File does not exist'
            ];
        }

        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);

        // Check file size limits
        if ($this->isImage($mimeType) && $fileSize > 5 * 1024 * 1024) { // 5MB for images
            return [
                'header_code' => ResponseAlias::HTTP_BAD_REQUEST,
                'body' => 'Image file size exceeds 5MB limit'
            ];
        }

        if ($this->isVideo($mimeType) && $fileSize > 512 * 1024 * 1024) { // 512MB for videos
            return [
                'header_code' => ResponseAlias::HTTP_BAD_REQUEST,
                'body' => 'Video file size exceeds 512MB limit'
            ];
        }

        // For videos, we need to use chunked upload
        if ($this->isVideo($mimeType)) {
            return $this->uploadVideoChunked($accessToken, $filePath, $metadata);
        }

        // For images, use simple upload
        return $this->uploadImage($accessToken, $filePath, $metadata);
    }

    /**
     * Generate OAuth 1.0a signature for Twitter API v1.1
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return array
     */
    private function generateOAuth1Signature(string $method, string $url, array $params = [], array $headers = []): array
    {
        $timestamp = time();
        $nonce = uniqid();
        
        $oauthParams = [
            'oauth_consumer_key' => $this->apiKey,
            'oauth_nonce' => $nonce,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => $timestamp,
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0'
        ];
        
        // Filter out CURLFile objects and other non-string values for signature
        $signatureParams = [];
        foreach ($params as $key => $value) {
            if (!is_object($value) && !is_array($value)) {
                $signatureParams[$key] = (string)$value;
            }
        }
        
        // For POST requests, include Content-Type in signature if present
        if ($method === 'POST' && isset($headers['Content-Type'])) {
            $signatureParams['oauth_content_type'] = $headers['Content-Type'];
        }
        
        // Merge all parameters
        $allParams = array_merge($signatureParams, $oauthParams);
        ksort($allParams);
        
        // Create parameter string
        $paramString = '';
        foreach ($allParams as $key => $value) {
            $paramString .= "{$key}={$value}&";
        }
        $paramString = rtrim($paramString, '&');
        
        // Create signature base string
        $signatureBase = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode($paramString);
        
        // Create signing key
        $signingKey = rawurlencode($this->apiSecret) . '&' . rawurlencode($this->accessTokenSecret);
        
        // Generate signature
        $signature = base64_encode(hash_hmac('sha1', $signatureBase, $signingKey, true));
        
        // Add signature to OAuth params
        $oauthParams['oauth_signature'] = $signature;
        
        // Create Authorization header
        $authHeader = 'OAuth ';
        $authParts = [];
        foreach ($oauthParams as $key => $value) {
            $authParts[] = "{$key}=\"" . rawurlencode($value) . "\"";
        }
        $authHeader .= implode(', ', $authParts);
        
        $result = [
            'Authorization' => $authHeader
        ];
        
        // Add Content-Type header if provided
        if (isset($headers['Content-Type'])) {
            $result['Content-Type'] = $headers['Content-Type'];
        }
        
        return $result;
    }

    /**
     * Upload image to X
     * @param string $accessToken
     * @param string $filePath
     * @param array $metadata
     * @return array
     * @throws ConnectionException
     */
    private function uploadImage(string $accessToken, string $filePath, array $metadata = []): array
    {
        // For Twitter API v1.1, we need to use OAuth1 authentication
        $url = 'https://upload.twitter.com/1.1/media/upload.json';
        
        // For Twitter API v1.1, we need to send as form data, not multipart
        $data = [
            'media_category' => 'tweet_image',
            'media_data' => base64_encode(file_get_contents($filePath))
        ];
        
        $headers = $this->generateOAuth1Signature('POST', $url, $data);
        
        // Add Content-Type for form data
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        $response = Helper::makeHttpRequest('POST', $url, $data, $headers, true, $this->platform);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'media_id' => $response['body']['media_id_string'] ?? null,
                'size' => $response['body']['size'] ?? null,
                'type' => 'image'
            ]
        ];
    }

    /**
     * Upload video to X using chunked upload
     * @param string $accessToken
     * @param string $filePath
     * @param array $metadata
     * @return array
     * @throws ConnectionException
     */
    private function uploadVideoChunked(string $accessToken, string $filePath, array $metadata = []): array
    {
        $fileSize = filesize($filePath);
        $chunkSize = 1024 * 1024; // 1MB chunks
        $totalChunks = ceil($fileSize / $chunkSize);

        // Step 1: Initialize upload
        $headers = [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json'
        ];

        $initData = [
            'command' => 'INIT',
            'total_bytes' => $fileSize,
            'media_type' => mime_content_type($filePath),
            'media_category' => 'tweet_video'
        ];

        $initResponse = Helper::makeHttpRequest('POST', 'https://upload.twitter.com/1.1/media/upload.json', $initData, $headers, false, $this->platform);

        if ($initResponse['header_code'] != ResponseAlias::HTTP_OK) {
            return $initResponse;
        }

        $mediaId = $initResponse['body']['media_id_string'];

        // Step 2: Upload chunks
        $fileHandle = fopen($filePath, 'rb');
        $segmentIndex = 0;

        while (!feof($fileHandle)) {
            $chunk = fread($fileHandle, $chunkSize);
            
            $chunkData = [
                'command' => 'APPEND',
                'media_id' => $mediaId,
                'segment_index' => $segmentIndex,
                'media_data' => base64_encode($chunk)
            ];

            $chunkResponse = Helper::makeHttpRequest('POST', 'https://upload.twitter.com/1.1/media/upload.json', $chunkData, $headers, false, $this->platform);

            if ($chunkResponse['header_code'] != ResponseAlias::HTTP_OK) {
                fclose($fileHandle);
                return $chunkResponse;
            }

            $segmentIndex++;
        }

        fclose($fileHandle);

        // Step 3: Finalize upload
        $finalizeData = [
            'command' => 'FINALIZE',
            'media_id' => $mediaId
        ];

        $finalizeResponse = Helper::makeHttpRequest('POST', 'https://upload.twitter.com/1.1/media/upload.json', $finalizeData, $headers, false, $this->platform);

        if ($finalizeResponse['header_code'] != ResponseAlias::HTTP_OK) {
            return $finalizeResponse;
        }

        return [
            'header_code' => $finalizeResponse['header_code'],
            'body' => [
                'media_id' => $mediaId,
                'size' => $fileSize,
                'type' => 'video'
            ]
        ];
    }

    /**
     * Publish post to X (Twitter)
     * @param CustomerAccount $account
     * @param Post $post
     * @return array
     * @throws ConnectionException
     */
    public function publishPost($customerAccount, $post): array
    {
        try {
            // Get post files
            $postFiles = PostFile::where('post_id', $post->id)->get();
            
            if ($postFiles->isEmpty()) {
                return [
                    'header_code' => ResponseAlias::HTTP_BAD_REQUEST,
                    'body' => 'No media files found for this post'
                ];
            }

            $mediaIds = [];
            
            // Upload media files
            foreach ($postFiles as $file) {
                $filePath = Storage::disk('private')->path($file->file_path);
                
                if (!file_exists($filePath)) {
                    return [
                        'header_code' => ResponseAlias::HTTP_BAD_REQUEST,
                        'body' => "File not found: {$file->file_path}"
                    ];
                }

                $uploadResult = $this->uploadMedia($customerAccount->access_token, $filePath);
                
                if ($uploadResult['header_code'] != ResponseAlias::HTTP_OK) {
                    return $uploadResult;
                }

                $mediaIds[] = $uploadResult['body']['media_id'];
            }

            // Post tweet with media using Twitter API v1.1
            $tweetData = [
                'status' => $post->description,
                'media_ids' => implode(',', $mediaIds)
            ];

            // Use OAuth 1.0a for posting tweets with Twitter API v1.1
            $url = 'https://api.twitter.com/1.1/statuses/update.json';
            $headers = $this->generateOAuth1Signature('POST', $url, $tweetData);
            
            // Add Content-Type for form data
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';

            $response = Helper::makeHttpRequest('POST', $url, $tweetData, $headers, true, $this->platform);

            if ($response['header_code'] != ResponseAlias::HTTP_CREATED && $response['header_code'] != ResponseAlias::HTTP_OK) {
                return $response;
            }

            return [
                'header_code' => $response['header_code'],
                'body' => [
                    'platform_post_id' => $response['body']['id_str'] ?? 'unknown',
                    'platform_url' => "https://twitter.com/user/status/" . ($response['body']['id_str'] ?? 'unknown'),
                    'status' => 'published',
                    'media_ids' => $mediaIds
                ]
            ];

        } catch (Exception $e) {
            return [
                'header_code' => ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                'body' => 'Error publishing post: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get post analytics
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
        $params = [
            'tweet.fields' => 'public_metrics,created_at,text,entities',
            'media.fields' => 'public_metrics,preview_image_url'
        ];

        $response = Helper::makeHttpRequest('GET', "https://api.twitter.com/2/tweets/{$platformPostId}", $params, $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        $tweet = $response['body']['data'] ?? [];
        $metrics = $tweet['public_metrics'] ?? [];

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'views' => $metrics['impression_count'] ?? 0,
                'likes' => $metrics['like_count'] ?? 0,
                'retweets' => $metrics['retweet_count'] ?? 0,
                'replies' => $metrics['reply_count'] ?? 0,
                'quotes' => $metrics['quote_count'] ?? 0,
                'engagement_rate' => $this->calculateEngagementRate($metrics),
                'created_at' => $tweet['created_at'] ?? null,
                'text' => $tweet['text'] ?? null,
                'tweet_id' => $tweet['id'] ?? null
            ]
        ];
    }

    /**
     * Calculate engagement rate
     * @param array $metrics
     * @return float
     */
    private function calculateEngagementRate(array $metrics): float
    {
        $totalEngagement = ($metrics['like_count'] ?? 0) + 
                          ($metrics['retweet_count'] ?? 0) + 
                          ($metrics['reply_count'] ?? 0) + 
                          ($metrics['quote_count'] ?? 0);
        
        $impressions = $metrics['impression_count'] ?? 1;
        
        return $impressions > 0 ? round(($totalEngagement / $impressions) * 100, 2) : 0;
    }

    /**
     * Check if file is image
     * @param string $mimeType
     * @return bool
     */
    private function isImage(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    /**
     * Check if file is video
     * @param string $mimeType
     * @return bool
     */
    private function isVideo(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/');
    }

    /**
     * Get supported media types
     * @return array
     */
    public function getSupportedMediaTypes(): array
    {
        return $this->supportedMediaTypes;
    }

    /**
     * Validate content for X
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

            $mimeType = mime_content_type($filePath);
            $fileSize = filesize($filePath);

            if ($this->isImage($mimeType)) {
                if ($fileSize > 5 * 1024 * 1024) {
                    $errors[] = "Image file {$filePath} exceeds 5MB limit";
                }
            } elseif ($this->isVideo($mimeType)) {
                if ($fileSize > 512 * 1024 * 1024) {
                    $errors[] = "Video file {$filePath} exceeds 512MB limit";
                }
            } else {
                $errors[] = "Unsupported file type: {$mimeType}";
            }
        }

        // Validate text content
        $textLength = strlen(($metadata['title'] ?? '') . "\n\n" . ($metadata['description'] ?? ''));
        if ($textLength > 280) {
            $errors[] = "Text content exceeds 280 character limit";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get posting limits
     * @return array
     */
    public function getPostingLimits(): array
    {
        return [
            'max_posts_per_day' => 300,
            'max_posts_per_hour' => 25,
            'max_file_size_mb' => 512,
            'supported_formats' => ['mp4', 'mov', 'jpg', 'png', 'gif'],
            'max_video_duration_seconds' => 140,
            'max_text_length' => 280,
            'max_media_per_tweet' => 4,
        ];
    }

    /**
     * Get available scopes
     * @return array
     */
    public function getAvailableScopes(): array
    {
        return [
            'tweet.read' => 'Read Tweets and profiles',
            'tweet.write' => 'Create, edit, and delete Tweets',
            'users.read' => 'Read profile information',
            'offline.access' => 'Access to refresh tokens',
        ];
    }
}
