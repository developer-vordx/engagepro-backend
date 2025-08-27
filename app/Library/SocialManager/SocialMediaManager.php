<?php

namespace App\Library\SocialManager;

use App\Helper;
use App\Models\Post;
use App\Models\SocialAccount;

class SocialMediaManager
{
    private array $services = [];

    public function __construct()
    {
        $this->registerServices();
    }

    private function registerServices(): void
    {
        $this->services['tiktok'] = new TikTokService();
        // Add other services as they're implemented
        // $this->services['youtube'] = new YouTubeService();
        // $this->services['instagram'] = new InstagramService();
    }

    public function getService(string $platform)
    {
        if (!isset($this->services[$platform])) {
            return false;
        }

        return $this->services[$platform];
    }

    public function getSupportedPlatforms(): array
    {
        return array_keys($this->services);
    }

    public function publishToMultiplePlatforms(Post $post): array
    {
        $results = [];

        foreach ($post->target_platforms as $platform) {
            try {
                $service = $this->getService($platform);
                $account = SocialAccount::where('user_id', $post->user_id)
                    ->where('slug', $platform)
                    ->where('is_active', true)
                    ->first();

                if (!$account) {
                    $results[$platform] = [
                        'success' => false,
                        'error' => 'No active account found for platform',
                    ];
                    continue;
                }

                // Validate content for this platform
                $validation = $service->validateContent($post->media_files);
                if (!$validation['valid']) {
                    $results[$platform] = [
                        'success' => false,
                        'error' => 'Content validation failed',
                        'details' => $validation['errors'],
                    ];
                    continue;
                }

                // Filter media files based on platform support
                $supportedTypes = $service->getSupportedMediaTypes();
                $filteredFiles = $this->filterMediaFiles($post->media_files, $supportedTypes);

                if (empty($filteredFiles)) {
                    $results[$platform] = [
                        'success' => false,
                        'error' => 'No supported media files for this platform',
                    ];
                    continue;
                }

                // Update post with filtered files for this platform
                $platformPost = clone $post;
                $platformPost->media_files = $filteredFiles;

                $publishResult = $service->publishPost($account, $platformPost);

                $results[$platform] = [
                    'success' => true,
                    'platform_post_id' => $publishResult['platform_post_id'],
                    'platform_url' => $publishResult['platform_url'] ?? null,
                ];

            } catch (\Exception $e) {

                $results[$platform] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    private function filterMediaFiles(array $mediaFiles, array $supportedTypes): array
    {
        $filtered = [];

        foreach ($mediaFiles as $filePath) {
            $mimeType = mime_content_type($filePath);

            foreach ($supportedTypes as $type) {
                if (str_starts_with($mimeType, $type . '/')) {
                    $filtered[] = $filePath;
                    break;
                }
            }
        }

        return $filtered;
    }

    public function refreshAllTokens(SocialAccount $account): bool|\Illuminate\Http\JsonResponse
    {
        try {
            $service = $this->getService($account->platform);

            if (!$account->refresh_token) {
                return false;
            }

            $tokenData = $service->refreshToken($account->refresh_token);

            $account->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $account->refresh_token,
                'expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]);

            return true;
        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}
