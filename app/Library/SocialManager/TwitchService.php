<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\CustomerAccount;
use App\Helper;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class TwitchService
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
        $platForm = SocialAccount::where('slug', 'twitch')->first();
        $this->baseUrl = $platForm?->url ?: 'https://api.twitch.tv/helix';
        $this->scopes = $platForm?->scopes ?? ['user:read:email', 'channel:manage:broadcast'];
        $this->clientId = config('services.twitch.client_id', '');
        $this->clientSecret = config('services.twitch.client_secret', '');
        $this->redirectUri = config('services.twitch.redirect_uri', '');
        $this->platform = 'twitch';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['text'];
    }

    public function getPlatform(): string
    {
        return 'twitch';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Generate Twitch OAuth authorization URL
     * Scopes: user:read:email, channel:manage:broadcast, etc.
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
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'state' => csrf_token(),
            'force_verify' => 'false',
        ];
        
        return 'https://id.twitch.tv/oauth2/authorize?' . http_build_query($params);
    }

    /**
     * Exchange code for token
     */
    public function exchangeCodeForToken(string $code): array
    {
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
        ];

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        
        $response = Helper::makeHttpRequest('POST', 'https://id.twitch.tv/oauth2/token', $data, $headers, false, $this->platform);
        
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
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        
        return Helper::makeHttpRequest('POST', 'https://id.twitch.tv/oauth2/token', $data, $headers, false, $this->platform);
    }

    /**
     * Get user profile
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = [
            'Authorization' => "Bearer {$userAccessToken}",
            'Client-Id' => $this->clientId
        ];
        
        $response = Helper::makeHttpRequest('GET', 'https://api.twitch.tv/helix/users', [], $headers);
        
        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }
        
        $user = $response['body']['data'][0] ?? [];
        
        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['id'] ?? null,
                'username' => $user['login'] ?? null,
                'display_name' => $user['display_name'] ?? null,
                'profile_picture' => $user['profile_image_url'] ?? null,
                'bio_description' => $user['description'] ?? null,
                'email' => $user['email'] ?? null,
                'broadcaster_type' => $user['broadcaster_type'] ?? null,
                'view_count' => $user['view_count'] ?? 0,
                'follower_count' => 0,
                'following_count' => 0,
            ]
        ];
    }

    /**
     * Validate content
     */
    public function validateContent(array $mediaFiles, array $metadata = []): array
    {
        // Twitch primarily for live streaming, not direct video uploads via API
        return [
            'valid' => true,
            'errors' => [],
            'warnings' => ['Twitch API is primarily for live streaming, not post publishing']
        ];
    }

    /**
     * Publish post (Twitch doesn't have traditional posting, this is a placeholder)
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        // Twitch doesn't support traditional post publishing
        // This could update stream title/category or create a clip
        return $this->errorResponse(ResponseAlias::HTTP_NOT_IMPLEMENTED, 'Twitch does not support traditional post publishing. Use for stream management instead.');
    }

    /**
     * Get available scopes
     */
    public function getAvailableScopes(): array
    {
        return [
            'analytics:read:extensions' => 'View analytics for extensions',
            'analytics:read:games' => 'View analytics for games',
            'bits:read' => 'View Bits information',
            'channel:edit:commercial' => 'Run commercials',
            'channel:manage:broadcast' => 'Manage broadcast configuration',
            'channel:manage:extensions' => 'Manage extensions',
            'channel:manage:polls' => 'Manage polls',
            'channel:manage:predictions' => 'Manage predictions',
            'channel:manage:raids' => 'Manage raids',
            'channel:manage:redemptions' => 'Manage channel points redemptions',
            'channel:manage:schedule' => 'Manage stream schedule',
            'channel:manage:videos' => 'Manage videos',
            'channel:read:editors' => 'View channel editors',
            'channel:read:goals' => 'View channel goals',
            'channel:read:hype_train' => 'View hype train events',
            'channel:read:polls' => 'View polls',
            'channel:read:predictions' => 'View predictions',
            'channel:read:redemptions' => 'View channel points redemptions',
            'channel:read:stream_key' => 'View stream key',
            'channel:read:subscriptions' => 'View subscribers',
            'clips:edit' => 'Manage clips',
            'moderation:read' => 'View moderation data',
            'moderator:manage:announcements' => 'Manage announcements',
            'moderator:manage:automod' => 'Manage AutoMod',
            'moderator:manage:banned_users' => 'Manage banned users',
            'moderator:manage:chat_messages' => 'Manage chat messages',
            'moderator:manage:chat_settings' => 'Manage chat settings',
            'user:edit' => 'Edit user information',
            'user:edit:follows' => 'Manage follows',
            'user:manage:blocked_users' => 'Manage blocked users',
            'user:read:blocked_users' => 'View blocked users',
            'user:read:broadcast' => 'View broadcast information',
            'user:read:email' => 'View email address',
            'user:read:follows' => 'View followed channels',
            'user:read:subscriptions' => 'View subscriptions',
            'whispers:read' => 'View whisper messages',
            'whispers:edit' => 'Send whisper messages',
            'channel:moderate' => 'Perform moderation actions',
            'chat:edit' => 'Send chat messages',
            'chat:read' => 'View chat messages',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['text'];
    }

    public function getPostingLimits(): array
    {
        return [
            'note' => 'Twitch is primarily for live streaming, not traditional post uploads',
            'clip_duration_max_seconds' => 60,
        ];
    }
}
