<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\CustomerAccount;
use App\Helper;

class SocialMediaSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $platform
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $platform = '')
    {
        $customer = Auth::guard('customer')->user();
        
        if (!$customer) {
            return Helper::response('Unauthorized', 401);
        }

        // Rate limiting per customer per platform
        $rateLimitKey = "social_posts_{$customer->id}_{$platform}";
        $rateLimit = Cache::get($rateLimitKey, 0);
        
        // Platform-specific rate limits (posts per hour)
        $platformLimits = [
            'tiktok' => 5,    // Conservative limit
            'x' => 3,         // Very conservative for X
            'instagram' => 4, // Conservative for Instagram
            'facebook' => 6,  // Slightly more lenient for Facebook
            'youtube' => 2,   // Very conservative for YouTube
        ];
        
        $limit = $platformLimits[$platform] ?? 3;
        
        if ($rateLimit >= $limit) {
            return Helper::response("Rate limit exceeded for {$platform}. Please wait before posting again.", 429);
        }

        // Check if customer account is active and not suspended
        if ($platform) {
            $customerAccount = CustomerAccount::where('customer_id', $customer->id)
                ->whereHas('socialAccount', function($q) use ($platform) {
                    $q->where('slug', $platform);
                })
                ->where('is_active', true)
                ->first();

            if (!$customerAccount) {
                return Helper::response("No active {$platform} account found. Please connect your account first.", 400);
            }

            // Check for recent errors that might indicate account issues
            $recentErrors = Cache::get("account_errors_{$customerAccount->id}", 0);
            if ($recentErrors >= 3) {
                return Helper::response("Account temporarily suspended due to recent errors. Please contact support.", 423);
            }
        }

        // Increment rate limit counter
        Cache::put($rateLimitKey, $rateLimit + 1, 3600); // 1 hour

        return $next($request);
    }
}



