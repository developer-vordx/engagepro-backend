<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class RedditService
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
        $platForm = SocialAccount::where('slug', 'reddit')->first();
        $this->baseUrl = $platForm?->url ?: 'https://oauth.reddit.com';
        $this->scopes = $platForm?->scopes ?? ['identity', 'submit', 'read'];
        $this->clientId = config('services.reddit.client_id', '');
        $this->clientSecret = config('services.reddit.client_secret', '');
        $this->redirectUri = config('services.reddit.redirect_uri', '');
        $this->platform = 'reddit';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['image', 'video', 'text'];
    }

    public function getPlatform(): string
    {
        return 'reddit';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Generate Reddit OAuth 2.0 authorization URL
     * Scopes: identity, submit, read, vote, etc.
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
            'response_type' => 'code',
            'state' => csrf_token(),
            'redirect_uri' => $this->redirectUri,
            'duration' => 'permanent',
            'scope' => implode(' ', $scopes),
        ];
        
        return 'https://www.reddit.com/api/v1/authorize?' . http_build_query($params);
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
            'Authorization' => Helper::buildBasicAuthHeader($this->clientId, $this->clientSecret),
            'User-Agent' => 'EngageProBot/1.0'
        ];
        
        $response = Helper::makeHttpRequest('POST', 'https://www.reddit.com/api/v1/access_token', $data, $headers, false, $this->platform);
        
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
            'Authorization' => Helper::buildBasicAuthHeader($this->clientId, $this->clientSecret),
            'User-Agent' => 'EngageProBot/1.0'
        ];
        
        return Helper::makeHttpRequest('POST', 'https://www.reddit.com/api/v1/access_token', $data, $headers, false, $this->platform);
    }

    /**
     * Get user profile
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = [
            'Authorization' => "Bearer {$userAccessToken}",
            'User-Agent' => 'EngageProBot/1.0'
        ];
        
        $response = Helper::makeHttpRequest('GET', $this->baseUrl . '/api/v1/me', [], $headers);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }
        
        $user = $response['body'] ?? [];
        
        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['id'] ?? null,
                'username' => $user['name'] ?? null,
                'display_name' => $user['name'] ?? null,
                'profile_picture' => $user['icon_img'] ?? null,
                'follower_count' => 0,
                'following_count' => 0,
                'karma' => ($user['link_karma'] ?? 0) + ($user['comment_karma'] ?? 0),
                'created_at' => isset($user['created']) ? date('Y-m-d H:i:s', $user['created']) : null,
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
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => []
        ];
    }

    /**
     * Submit post to subreddit
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        try {
            $headers = [
                'Authorization' => "Bearer {$account->access_token}",
                'User-Agent' => 'EngageProBot/1.0',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];

            $subreddit = $content['subreddit'] ?? 'test'; // Must specify subreddit
            $title = $content['title'] ?? $post->title;
            $text = $content['description'] ?? $post->description ?? '';

            $postFiles = $post->postFiles;
            
            // Text post or link post
            if (!$postFiles || $postFiles->isEmpty()) {
                $data = [
                    'sr' => $subreddit,
                    'kind' => 'self',
                    'title' => $title,
                    'text' => $text,
                ];
            } else {
                // Image/video post (requires upload to Reddit's media endpoint first)
                $data = [
                    'sr' => $subreddit,
                    'kind' => 'link',
                    'title' => $title,
                    'url' => $content['url'] ?? '', // External URL or uploaded media URL
                ];
            }

            $response = Helper::makeHttpRequest('POST', $this->baseUrl . '/api/submit', $data, $headers, false, $this->platform);
            
            if ($response['header_code'] != ResponseAlias::HTTP_OK) {
                return $this->errorResponse($response['header_code'], $response['body'] ?? 'Failed to publish post');
            }

            $postData = $response['body']['json']['data'] ?? [];

            return [
                'header_code' => ResponseAlias::HTTP_OK,
                'body' => [
                    'platform_post_id' => $postData['id'] ?? 'unknown',
                    'platform_url' => $postData['url'] ?? "https://www.reddit.com/r/{$subreddit}",
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
            'identity' => 'Access to account information',
            'edit' => 'Edit and delete posts and comments',
            'flair' => 'Manage flair',
            'history' => 'Access browsing history',
            'modconfig' => 'Manage subreddit configuration',
            'modflair' => 'Manage user flair',
            'modlog' => 'Access moderation log',
            'modposts' => 'Manage posts and comments',
            'modwiki' => 'Manage wiki pages',
            'mysubreddits' => 'Access list of subscribed subreddits',
            'privatemessages' => 'Access private messages',
            'read' => 'Read posts and comments',
            'report' => 'Report content',
            'save' => 'Save and unsave posts/comments',
            'submit' => 'Submit links and text posts',
            'subscribe' => 'Manage subreddit subscriptions',
            'vote' => 'Submit votes',
            'wikiedit' => 'Edit wiki pages',
            'wikiread' => 'Read wiki pages',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['image', 'video', 'text'];
    }

    /**
     * Get posting limits
     */
    public function getPostingLimits(): array
    {
        return [
            'max_title_length' => 300,
            'max_text_length' => 40000,
            'rate_limit' => '1 post per 10 minutes per user',
        ];
    }
}
