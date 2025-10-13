<?php

namespace App\Library\SocialManager;

use App\Models\SocialAccount;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\CustomerAccount;
use App\Helper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class OdyseeService
{
    private string $baseUrl;
    private string $apiKey;
    private string $platform;
    private mixed $supportedMediaTypes;

    public function __construct()
    {
        $platForm = SocialAccount::where('slug', 'odysee')->first();
        $this->baseUrl = $platForm?->url ?: 'https://api.lbry.com';
        $this->apiKey = config('services.odysee.api_key', '');
        $this->platform = 'odysee';
        $this->supportedMediaTypes = $platForm?->supported_media_types ?? ['video'];
    }

    public function getPlatform(): string
    {
        return 'odysee';
    }

    public function errorResponse(int $statusCode, string $message): array
    {
        return [
            'header_code' => $statusCode,
            'body' => $message
        ];
    }

    /**
     * Odysee/LBRY uses API key authentication, not OAuth
     */
    public function getAuthorizationUrl(array $scopes = []): string
    {
        return 'https://odysee.com/@lbry:3f/api-keys:9';
    }

    /**
     * Not applicable - uses API key
     */
    public function exchangeCodeForToken(string $code): array
    {
        return $this->errorResponse(ResponseAlias::HTTP_NOT_IMPLEMENTED, 'Odysee uses LBRY API key authentication, not OAuth');
    }

    /**
     * Get channel info
     */
    public function getUserProfile(string $userAccessToken): array
    {
        // LBRY API uses api_key parameter
        $params = ['api_key' => $userAccessToken];
        
        $response = Helper::makeHttpRequest('GET', 'https://api.lbry.com/user/me', $params, []);

        if ($response['header_code'] != ResponseAlias::HTTP_OK) {
            return $response;
        }

        $user = $response['body']['data'] ?? [];

        return [
            'header_code' => $response['header_code'],
            'body' => [
                'identifier' => $user['id'] ?? null,
                'username' => $user['name'] ?? null,
                'display_name' => $user['name'] ?? null,
                'profile_picture' => null,
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
        $errors = [];

        foreach ($mediaFiles as $filePath) {
            if (!file_exists($filePath)) {
                $errors[] = "File does not exist: {$filePath}";
                continue;
            }

            $mimeType = mime_content_type($filePath);
            if (!str_starts_with($mimeType, 'video/')) {
                $errors[] = "Odysee primarily supports video content";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => ['Odysee uses LBRY protocol - implementation requires LBRY SDK for full features']
        ];
    }

    /**
     * Publish to LBRY/Odysee
     * Note: Full implementation requires LBRY SDK
     */
    public function publishPost(CustomerAccount $account, Post $post, array $content = []): array
    {
        return $this->errorResponse(
            501,
            'Odysee publishing requires LBRY SDK integration. Basic API key authentication available but limited.'
        );
    }

    public function getAvailableScopes(): array
    {
        return [
            'api_key' => 'LBRY API key authentication (not OAuth)',
        ];
    }

    public function getSupportedMediaTypes(): array
    {
        return is_array($this->supportedMediaTypes) ? $this->supportedMediaTypes : json_decode($this->supportedMediaTypes, true) ?? ['video'];
    }

    public function getPostingLimits(): array
    {
        return [
            'note' => 'Odysee uses LBRY protocol - requires SDK for full features',
            'api_key_based' => true,
            'max_video_size' => 'Variable based on account',
        ];
    }
}
