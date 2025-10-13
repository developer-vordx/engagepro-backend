<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\CustomerAccount;
use App\Helper;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class SnapchatService
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
        $platForm = SocialAccount::where('slug', 'snapchat')->first();
        $this->baseUrl = 'https://accounts.snapchat.com';
        $this->scopes = $platForm?->scopes ?? [
            'https://auth.snapchat.com/oauth2/api/user.display_name',
            'https://auth.snapchat.com/oauth2/api/user.external_id',
            'https://auth.snapchat.com/oauth2/api/user.bitmoji.avatar'
        ];
        $this->clientId = config('services.snapchat.client_id', '');
        $this->clientSecret = config('services.snapchat.client_secret', '');
        $this->redirectUri = config('services.snapchat.redirect_uri', '');
        $this->platform = 'snapchat';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['image', 'video'];
    }

    public function getPlatform(): string
    {
        return 'snapchat';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Generate Snapchat Login Kit OAuth 2.0 authorization URL with PKCE
     * Based on: https://developers.snap.com/snap-kit/login-kit/overview
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        $scopes = empty($scopes) ? Helper::parseScopes($this->scopes) : $scopes;
        $pkce = Helper::generatePKCE('snapchat_code_verifier');
        
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => csrf_token(),
            'code_challenge' => $pkce['code_challenge'],
            'code_challenge_method' => $pkce['code_challenge_method'],
        ];

        return 'https://accounts.snapchat.com/accounts/oauth2/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     * Server-side flow with client_secret and code_verifier
     */
    public function exchangeCodeForToken(string $code): array
    {
        $codeVerifier = session('snapchat_code_verifier');

        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code_verifier' => $codeVerifier,
        ];

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        
        $response = Helper::makeHttpRequest('POST', 'https://accounts.snapchat.com/accounts/oauth2/token', $data, $headers, false, $this->platform);
        
        // Clear code verifier from session
        session()->forget('snapchat_code_verifier');

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $this->errorResponse($response['header_code'], 'Failed to exchange code for token');
        }

        return $response;
    }

    /**
     * Refresh access token
     * Access tokens expire after 1 hour
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
        
        return Helper::makeHttpRequest('POST', 'https://accounts.snapchat.com/accounts/oauth2/token', $data, $headers, false, $this->platform);
    }

    /**
     * Get user profile using Snapchat Login Kit
     * Returns display_name, external_id, and bitmoji avatar based on granted scopes
     */
    public function getUserProfile(string $userAccessToken): array
    {
        $headers = ['Authorization' => "Bearer {$userAccessToken}"];
        
        // Snapchat's user info endpoint
        $response = Helper::makeHttpRequest('GET', 'https://kit.snapchat.com/v1/me', [], $headers);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        $user = $response['body']['data']['me'] ?? [];

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['externalId'] ?? $user['id'] ?? null,
                'username' => $user['displayName'] ?? null,
                'display_name' => $user['displayName'] ?? null,
                'profile_picture' => $user['bitmoji']['avatar'] ?? null,
                'bitmoji_avatar' => $user['bitmoji']['avatar'] ?? null,
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
        return [
            'valid' => false,
            'errors' => ['Snapchat Login Kit provides authentication only, not content posting API'],
            'warnings' => ['Use Snapchat Creative Kit for sharing to Snapchat, or Marketing API for ads']
        ];
    }

    /**
     * Publish post - Not available via Login Kit
     * Note: Snapchat Login Kit is for authentication only
     * For content sharing, use Creative Kit (requires Snapchat app)
     * For ads, use Marketing API (different credentials)
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        return $this->errorResponse(
            501,
            'Snapchat Login Kit provides authentication only. Content posting requires Creative Kit (app-based sharing) or Marketing API (ads).'
        );
    }

    /**
     * Get available scopes (from Snapchat Login Kit documentation)
     */
    public function getAvailableScopes(): array
    {
        return [
            'https://auth.snapchat.com/oauth2/api/user.display_name' => 'Access user display name (always available)',
            'https://auth.snapchat.com/oauth2/api/user.external_id' => 'Access unique app-specific user ID (always available)',
            'https://auth.snapchat.com/oauth2/api/user.bitmoji.avatar' => 'Access user Bitmoji avatar (toggleable by user)',
            'https://auth.snapchat.com/oauth2/api/camkit_lens_push_to_device' => 'Enable Lens Push-to-Device (requires Camera Kit)',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        // Login Kit is for authentication only, not content posting
        return [];
    }

    public function getPostingLimits(): array
    {
        return [
            'note' => 'Snapchat Login Kit is for authentication only, not content posting',
            'authentication_only' => true,
            'token_expiry' => '1 hour',
            'supports_refresh' => true,
        ];
    }
}
