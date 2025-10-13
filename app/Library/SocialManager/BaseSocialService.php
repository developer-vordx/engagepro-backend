<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

abstract class BaseSocialService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;
    protected string $baseUrl;
    protected mixed $scopes;
    protected string $platform;
    protected mixed $supportedMediaTypes;

    /**
     * Get platform identifier
     */
    abstract public function getPlatform(): string;

    /**
     * Generate authorization URL
     */
    abstract public function getAuthorizationUrl(array $scopes = []): string;

    /**
     * Exchange authorization code for access token
     */
    abstract public function exchangeCodeForToken(string $code): array;

    /**
     * Get user profile
     */
    abstract public function getUserProfile(string $userAccessToken): array;

    /**
     * Validate content before publishing
     */
    abstract public function validateContent(array $mediaFiles, array $metadata = []): array;

    /**
     * Publish post to platform
     */
    abstract public function publishPost($customerAccount, $post, array $content = []): array;

    /**
     * Helper function to return error response
     */
    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Get supported media types
     */
    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) 
            ? $this->supportedMediaTypes 
            : json_decode($this->supportedMediaTypes ?? '[]', true) ?? [];
    }

    /**
     * Get scopes array from mixed type
     */
    protected function getScopesArray(array $defaultScopes = []): array
    {
        if (empty($this->scopes)) {
            return $defaultScopes;
        }

        return is_array($this->scopes) 
            ? $this->scopes 
            : json_decode($this->scopes, true) ?? $defaultScopes;
    }

    /**
     * Load platform configuration from database
     */
    protected function loadPlatformConfig(string $slug): ?SocialAccount
    {
        return SocialAccount::where('slug', $slug)->first();
    }

    /**
     * Check if file is image
     */
    protected function isImage(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    /**
     * Check if file is video
     */
    protected function isVideo(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'video/');
    }

    /**
     * Validate file exists and get info
     */
    protected function validateFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'valid' => false,
                'error' => "File does not exist: {$filePath}"
            ];
        }

        return [
            'valid' => true,
            'size' => filesize($filePath),
            'mime_type' => mime_content_type($filePath),
            'path' => $filePath
        ];
    }

    /**
     * Refresh access token (override if platform supports it)
     */
    public function refreshToken(string $refreshToken): array
    {
        return $this->errorResponse(
            ResponseAlias::HTTP_NOT_IMPLEMENTED,
            'Token refresh not supported for this platform'
        );
    }

    /**
     * Get available scopes (override in child classes)
     */
    public function getAvailableScopes(): array
    {
        return [];
    }

    /**
     * Get posting limits (override in child classes)
     */
    public function getPostingLimits(): array
    {
        return [];
    }

    /**
     * Get post analytics (override if platform supports it)
     */
    public function getPostAnalytics($customerAccount, string $platformPostId): array
    {
        return $this->errorResponse(
            ResponseAlias::HTTP_NOT_IMPLEMENTED,
            'Analytics not supported for this platform'
        );
    }
}

