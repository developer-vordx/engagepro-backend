<?php

namespace App\Services\Api\V1\CustomerBackOffice\Post;

use App\Models\Post;
use App\Models\SocialPost;
use App\Models\CustomerPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helper;
use Symfony\Component\HttpFoundation\Response;

class PostStatsService
{
    /**
     * Get post statistics
     *
     * @param int|null $postId
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(?int $postId = null)
    {
        try {
            $customer = Auth::guard('customer')->user();
            $subscription = $customer->subscriptionPlan;

            // Check analytics access
            if ($subscription && !$subscription->subscriptionPlan->analytics_access) {
                return Helper::response('Analytics access not available in your current plan.', Response::HTTP_FORBIDDEN);
            }

            if ($postId) {
                return $this->getSinglePostStats($postId, $customer);
            } else {
                return $this->getOverallStats($customer, $subscription);
            }

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }

    /**
     * Get statistics for a single post
     *
     * @param int $postId
     * @param $customer
     * @return \Illuminate\Http\JsonResponse
     */
    private function getSinglePostStats(int $postId, $customer)
    {
        $post = Post::where('customer_id', $customer->id)
            ->with(['postFiles', 'socialPosts.socialAccount'])
            ->findOrFail($postId);

        $socialPosts = $post->socialPosts;
        $platformStats = [];

        foreach ($socialPosts as $socialPost) {
            $platform = $socialPost->socialAccount->slug;
            $platformStats[$platform] = [
                'platform' => $platform,
                'post_id' => $socialPost->platform_post_id,
                'post_url' => $socialPost->post_url,
                'status' => $socialPost->status,
                'published_at' => $socialPost->published_at,
                'engagement' => $this->getEngagementStats($socialPost),
            ];
        }

        return Helper::response([
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'status' => $post->status,
                'created_at' => $post->created_at,
                'published_at' => $post->published_at,
                'files_count' => $post->postFiles->count(),
                'platforms' => $platformStats
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Get overall statistics for customer
     *
     * @param $customer
     * @param $subscription
     * @return \Illuminate\Http\JsonResponse
     */
    private function getOverallStats($customer, $subscription)
    {
        $stats = [
            'subscription' => [
                'plan_name' => $subscription ? $subscription->subscriptionPlan->name : 'No Plan',
                'posts_this_month' => $subscription ? $subscription->posts_this_month : 0,
                'max_posts_per_month' => $subscription ? $subscription->subscriptionPlan->max_posts_per_month : 0,
                'posts_remaining' => $subscription ? max(0, $subscription->subscriptionPlan->max_posts_per_month - $subscription->posts_this_month) : 0,
            ],
            'posts' => [
                'total' => Post::where('customer_id', $customer->id)->count(),
                'published' => Post::where('customer_id', $customer->id)->where('status', 'published')->count(),
                'draft' => Post::where('customer_id', $customer->id)->where('status', 'draft')->count(),
                'failed' => Post::where('customer_id', $customer->id)->where('status', 'failed')->count(),
            ],
            'platforms' => $this->getPlatformStats($customer->id),
            'recent_activity' => $this->getRecentActivity($customer->id),
        ];

        return Helper::response($stats, Response::HTTP_OK);
    }

    /**
     * Get platform-specific statistics
     *
     * @param int $customerId
     * @return array
     */
    private function getPlatformStats(int $customerId): array
    {
        $platformStats = DB::table('social_posts')
            ->join('customer_accounts', 'social_posts.customer_account_id', '=', 'customer_accounts.id')
            ->join('social_accounts', 'customer_accounts.social_accounts_id', '=', 'social_accounts.id')
            ->where('customer_accounts.customer_id', $customerId)
            ->select(
                'social_accounts.slug as platform',
                DB::raw('COUNT(*) as total_posts'),
                DB::raw('SUM(CASE WHEN social_posts.status = "published" THEN 1 ELSE 0 END) as successful_posts'),
                DB::raw('SUM(CASE WHEN social_posts.status = "failed" THEN 1 ELSE 0 END) as failed_posts')
            )
            ->groupBy('social_accounts.slug')
            ->get()
            ->keyBy('platform')
            ->toArray();

        return $platformStats;
    }

    /**
     * Get recent activity
     *
     * @param int $customerId
     * @return array
     */
    private function getRecentActivity(int $customerId): array
    {
        $recentPosts = Post::where('customer_id', $customerId)
            ->with(['socialPosts.socialAccount'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'status' => $post->status,
                    'created_at' => $post->created_at,
                    'published_at' => $post->published_at,
                    'platforms' => $post->socialPosts->pluck('socialAccount.slug')->toArray(),
                ];
            });

        return $recentPosts->toArray();
    }

    /**
     * Get engagement statistics for a social post
     *
     * @param SocialPost $socialPost
     * @return array
     */
    private function getEngagementStats(SocialPost $socialPost): array
    {
        // This would typically fetch real engagement data from the platform APIs
        // For now, we'll return placeholder data
        return [
            'views' => 0,
            'likes' => 0,
            'shares' => 0,
            'comments' => 0,
            'last_updated' => $socialPost->updated_at,
        ];
    }
}



