<?php

namespace App\Library\SocialManager;

use App\Helper;
use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

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
        $this->services['x'] = new XService();
        $metaService = new MetaService();
        $this->services['meta'] = $metaService;
        $this->services['facebook'] = $metaService;
        $this->services['instagram'] = $metaService;
        $this->services['youtube'] = new YouTubeService();
        // Newly supported platforms
        $this->services['linkedin'] = new LinkedInService();
        $this->services['pinterest'] = new PinterestService();
        $this->services['mastodon'] = new MastodonService();
        $this->services['bluesky'] = new BlueskyService();
        $this->services['telegram'] = new TelegramService();
        $this->services['threads'] = new ThreadsService();
        $this->services['snapchat'] = new SnapchatService();
        $this->services['vimeo'] = new VimeoService();
        $this->services['twitch'] = new TwitchService();
        $this->services['dailymotion'] = new DailymotionService();
        $this->services['odysee'] = new OdyseeService();
        $this->services['reddit'] = new RedditService();
        $this->services['tumblr'] = new TumblrService();
        $this->services['truthsocial'] = new TruthSocialService();
        $this->services['minds'] = new MindsService();
        // Add other services as they're implemented
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

    /**
     * Minimal publish scopes required per platform (latest APIs - 2025)
     * Note: Some platforms don't expose OAuth scopes publicly or use app passwords/bots.
     */
    public function getRequiredPublishScopes(string $platform): array
    {
        return match ($platform) {
            // Fully implemented platforms with OAuth 2.0
            'tiktok' => ['video.upload'],
            'x' => ['tweet.write'],
            'facebook' => ['pages_manage_posts'],
            'instagram' => ['instagram_content_publish'],
            'meta' => ['pages_manage_posts', 'instagram_content_publish'],
            'youtube' => ['https://www.googleapis.com/auth/youtube.upload'],
            'linkedin' => ['w_member_social'],
            'pinterest' => ['pins:write'],
            'reddit' => ['submit'],
            'tumblr' => ['write'],
            'mastodon' => ['write:statuses'],
            'vimeo' => ['upload'],
            'dailymotion' => ['manage_videos'],
            
            // Limited/special auth flows
            'twitch' => ['channel:manage:broadcast'], // Streaming platform
            'telegram' => [], // Bot API with token (no OAuth)
            'threads' => ['threads_content_publish'], // Meta infrastructure
            'bluesky' => [], // App password authentication (no OAuth scopes)
            'snapchat' => [], // Login Kit - auth only (no content posting)
            'odysee' => [], // LBRY API key (no OAuth)
            'truthsocial' => ['write:statuses'], // Mastodon-compatible
            'minds' => [], // Cookie-based (no OAuth)
            default => [],
        };
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
                    'platform_post_id' => $publishResult['body']['platform_post_id'],
                    'platform_url' => $publishResult['body']['platform_url'] ?? null,
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

    public function refreshAllTokens(SocialAccount $account): bool|JsonResponse|array
    {
        try {
            $service = $this->getService($account->slug);

            if (!$account->refresh_token) {
                return [
                    'header_code' => ResponseAlias::HTTP_EXPECTATION_FAILED,
                    'body' => 'No refresh token found for the account.',
                ];
            }
            $tokenData = $service->refreshToken($account->refresh_token);

            if ($tokenData['header_code'] != ResponseAlias::HTTP_OK) {
                return $tokenData;
            }
            $account->update([
                'access_token' => $tokenData['body']['access_token'],
                'refresh_token' => $tokenData['body']['refresh_token'] ?? $account->refresh_token,
                'expires_at' => now()->addSeconds($tokenData['body']['expires_in']),
            ]);

            return [
                'header_code' => ResponseAlias::HTTP_OK,
                'body' => true,
            ];
        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}
