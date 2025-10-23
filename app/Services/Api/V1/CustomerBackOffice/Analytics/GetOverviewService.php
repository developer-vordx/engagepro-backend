<?php

namespace App\Services\Api\V1\CustomerBackOffice\Analytics;

use App\Contracts\Api\V1\CustomerBackOffice\Analytics\GetOverviewInterface;
use App\Models\Post;
use App\Models\CustomerAccount;
use App\Utils\BaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Helper;
use Carbon\Carbon;

class GetOverviewService extends BaseService implements GetOverviewInterface
{
    public function handle($request)
    {
        try {
            $customer = Auth::guard('customer')->user();
            $days = $request->days ?? 30;
            $startDate = Carbon::now()->subDays($days);
            
            // Get posts stats
            $posts = Post::where('customer_id', $customer->id)
                ->where('created_at', '>=', $startDate)
                ->with(['socialPosts.postInsights' => function($q) {
                    $q->latest()->limit(1);
                }])
                ->get();
            
            // Calculate totals
            $totalPosts = $posts->count();
            $publishedPosts = $posts->where('status', 'published')->count();
            
            $totalReach = 0;
            $totalLikes = 0;
            $totalComments = 0;
            $totalShares = 0;
            
            foreach ($posts as $post) {
                foreach ($post->socialPosts as $socialPost) {
                    $insight = $socialPost->postInsights->first();
                    if ($insight) {
                        $totalReach += $insight->reach ?? 0;
                        $totalLikes += $insight->likes ?? 0;
                        $totalComments += $insight->comments ?? 0;
                        $totalShares += $insight->shares ?? 0;
                    }
                }
            }
            
            $totalEngagement = $totalLikes + $totalComments + $totalShares;
            $engagementRate = $totalReach > 0 ? ($totalEngagement / $totalReach) * 100 : 0;
            
            // Get connected accounts stats
            $connectedAccounts = CustomerAccount::where('customer_id', $customer->id)
                ->where('is_active', true)
                ->count();
            
            $totalFollowers = CustomerAccount::where('customer_id', $customer->id)
                ->where('is_active', true)
                ->sum('follower_count');
            
            $data = [
                'total_posts' => $totalPosts,
                'published_posts' => $publishedPosts,
                'total_reach' => $totalReach,
                'total_followers' => $totalFollowers,
                'total_likes' => $totalLikes,
                'total_comments' => $totalComments,
                'total_shares' => $totalShares,
                'total_engagement' => $totalEngagement,
                'engagement_rate' => round($engagementRate, 2),
                'connected_accounts' => $connectedAccounts,
                'average_engagement_rate' => round($engagementRate, 2),
                'period_days' => $days,
            ];
            
            return Helper::response($data, ResponseAlias::HTTP_OK);
            
        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}

