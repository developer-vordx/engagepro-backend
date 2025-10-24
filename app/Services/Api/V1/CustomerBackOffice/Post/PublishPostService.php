<?php

namespace App\Services\Api\V1\CustomerBackOffice\Post;

use App\Models\Post;
use App\Models\CustomerAccount;
use App\Models\SocialPost;
use App\Library\SocialManager\SocialMediaManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Helper;
use Symfony\Component\HttpFoundation\Response;

class PublishPostService
{
    protected SocialMediaManager $socialMediaManager;

    public function __construct(SocialMediaManager $socialMediaManager)
    {
        $this->socialMediaManager = $socialMediaManager;
    }

    /**
     * Handle post publishing
     *
     * @param int $postId
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(int $postId, array $data)
    {
        try {
            DB::beginTransaction();

            $customer = Auth::guard('customer')->user();
            $subscription = $data['subscription'] ?? null;

            // Get the post
            $post = Post::where('customer_id', $customer->id)
                ->with(['postFiles', 'socialPosts'])
                ->findOrFail($postId);

            // Check if post has files
            if ($post->postFiles->count() === 0) {
                return Helper::response('Post must have at least one file to publish', Response::HTTP_BAD_REQUEST);
            }

            // Determine target platforms
            $platforms = $data['platforms'] ?? $post->target_platforms ?? [];
            if (empty($platforms)) {
                return Helper::response('No target platforms specified', Response::HTTP_BAD_REQUEST);
            }

            $results = [];
            $hasSuccessfulPublishes = false;

            foreach ($platforms as $platform) {
                $publishResult = $this->publishToPlatform($post, $platform, $customer, $data);
                $results[$platform] = $publishResult;
                
                if ($publishResult['success']) {
                    $hasSuccessfulPublishes = true;
                }
            }

            // Update post status
            $post->update([
                'status' => $hasSuccessfulPublishes ? 'published' : 'failed',
                'published_at' => $hasSuccessfulPublishes ? now() : null
            ]);

            // Update subscription post count
            if ($hasSuccessfulPublishes && $subscription) {
                $subscription->incrementPostUsage();
            }

            DB::commit();

            return Helper::response([
                'message' => 'Post publishing completed',
                'results' => $results,
                'post' => $post->fresh(['postFiles', 'socialPosts'])
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            return Helper::errors($e);
        }
    }

    /**
     * Publish post to a specific platform
     *
     * @param Post $post
     * @param string $platform
     * @param $customer
     * @param array $data
     * @return array
     */
    private function publishToPlatform(Post $post, string $platform, $customer, array $data): array
    {
        try {
            // Get customer's account for this platform
            $customerAccount = CustomerAccount::where('customer_id', $customer->id)
                ->whereHas('socialAccount', function($q) use ($platform) {
                    $q->where('slug', $platform);
                })
                ->where('is_active', true)
                ->with('socialAccount')
                ->first();

            if (!$customerAccount) {
                return [
                    'success' => false,
                    'error' => "No active {$platform} account found. Please connect your account first."
                ];
            }

            // Get the service for this platform
            $service = $this->socialMediaManager->getService($platform);
            if (!$service) {
                return [
                    'success' => false,
                    'error' => 'Platform not supported'
                ];
            }

            // Verify required scopes before publishing (if provider uses OAuth scopes)
            $requiredScopes = $this->socialMediaManager->getRequiredPublishScopes($platform);
            if (!empty($requiredScopes)) {
                $grantedScopes = [];
                $platformData = json_decode($customerAccount->platform_data ?? '{}', true);
                if (!empty($platformData['scopes'])) {
                    $grantedScopes = is_array($platformData['scopes']) ? $platformData['scopes'] : explode(',', (string)$platformData['scopes']);
                }

                $missing = array_values(array_diff($requiredScopes, $grantedScopes));
                if (!empty($missing)) {
                    return [
                        'success' => false,
                        'error' => 'Insufficient permissions (scopes) to publish to this platform',
                        'missing_scopes' => $missing
                    ];
                }
            }

            // Prepare content for this platform
            $content = $this->prepareContentForPlatform($post, $platform, $data);

            // Validate content for this platform
            $validation = $service->validateContent(
                $post->postFiles->pluck('file_path')->map(function($path) {
                    return Storage::disk('private')->path($path);
                })->toArray(),
                $content
            );

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'error' => 'Content validation failed',
                    'details' => $validation['errors']
                ];
            }

            // Publish to platform
            $publishResult = $service->publishPost($customerAccount, $post, $content);

            if ($publishResult['header_code'] == Response::HTTP_OK) {
                // Create social post record
                SocialPost::create([
                    'customer_account_id' => $customerAccount->id,
                    'post_id' => $post->id,
                    'social_account_id' => $customerAccount->social_accounts_id,
                    'platform_post_id' => $publishResult['body']['platform_post_id'] ?? null,
                    'post_url' => $publishResult['body']['platform_url'] ?? null,
                    'status' => $publishResult['body']['status'] ?? 'published',
                    'platform_response' => $publishResult['body'],
                    'published_at' => now()
                ]);

                return [
                    'success' => true,
                    'platform_post_id' => $publishResult['body']['platform_post_id'] ?? null,
                    'platform_url' => $publishResult['body']['platform_url'] ?? null,
                    'status' => $publishResult['body']['status'] ?? 'published'
                ];
            } else {
                // Track errors for security middleware
                $this->trackAccountError($customerAccount->id);
                
                return [
                    'success' => false,
                    'error' => $publishResult['body'] ?? 'Unknown error',
                    'status_code' => $publishResult['header_code']
                ];
            }

        } catch (\Exception $e) {
            \Log::error("Publishing to {$platform} failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Publishing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Prepare content for specific platform
     *
     * @param Post $post
     * @param string $platform
     * @param array $data
     * @return array
     */
    private function prepareContentForPlatform(Post $post, string $platform, array $data): array
    {
        $content = [
            'title' => $post->title,
            'description' => $post->description,
            'tags' => $post->tags
        ];

        // Platform-specific content adjustments
        switch ($platform) {
            case 'x':
                // X has character limits
                $content['description'] = $this->truncateForX($content['description']);
                break;
                
            case 'instagram':
                // Instagram prefers shorter descriptions
                if (strlen($content['description']) > 500) {
                    $content['description'] = substr($content['description'], 0, 497) . '...';
                }
                break;
                
            case 'tiktok':
                // TikTok has specific requirements
                $content['description'] = $this->formatForTikTok($content['description']);
                break;
        }

        // Use custom captions if provided
        if (isset($data['custom_captions'][$platform])) {
            $content['description'] = $data['custom_captions'][$platform];
        }

        return $content;
    }

    /**
     * Truncate content for X (Twitter)
     *
     * @param string|null $content
     * @return string|null
     */
    private function truncateForX(?string $content): ?string
    {
        if (!$content) return null;
        
        // X character limit is 280, but we'll be conservative
        if (strlen($content) > 250) {
            return substr($content, 0, 247) . '...';
        }
        
        return $content;
    }

    /**
     * Format content for TikTok
     *
     * @param string|null $content
     * @return string|null
     */
    private function formatForTikTok(?string $content): ?string
    {
        if (!$content) return null;
        
        // TikTok allows up to 2200 characters
        if (strlen($content) > 2200) {
            return substr($content, 0, 2197) . '...';
        }
        
        return $content;
    }

    /**
     * Track account errors for security
     *
     * @param int $customerAccountId
     * @return void
     */
    private function trackAccountError(int $customerAccountId): void
    {
        $errorKey = "account_errors_{$customerAccountId}";
        $errorCount = Cache::get($errorKey, 0);
        Cache::put($errorKey, $errorCount + 1, 3600); // 1 hour
    }
}



