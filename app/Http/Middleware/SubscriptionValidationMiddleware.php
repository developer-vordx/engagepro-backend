<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\CustomerPlan;
use App\Helper;

class SubscriptionValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $feature
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $feature = 'post')
    {
        $customer = Auth::guard('customer')->user();
        
        if (!$customer) {
            return Helper::response('Unauthorized', 401);
        }

        // Get active subscription
        $subscription = CustomerPlan::where('customer_id', $customer->id)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with('subscriptionPlan')
            ->first();

        if (!$subscription) {
            return Helper::response('No active subscription found. Please subscribe to a plan.', 403);
        }

        // Check feature-specific limits
        switch ($feature) {
            case 'post':
                if (!$subscription->canCreatePost()) {
                    return Helper::response('Monthly post limit reached. Upgrade your subscription.', 403);
                }
                break;
                
            case 'analytics':
                if (!$subscription->subscriptionPlan->analytics_access) {
                    return Helper::response('Analytics access not available in your current plan.', 403);
                }
                break;
                
            case 'api':
                if (!$subscription->subscriptionPlan->api_access) {
                    return Helper::response('API access not available in your current plan.', 403);
                }
                break;
                
            case 'priority_support':
                if (!$subscription->subscriptionPlan->priority_support) {
                    return Helper::response('Priority support not available in your current plan.', 403);
                }
                break;
        }

        // Add subscription info to request for use in controllers
        $request->merge(['subscription' => $subscription]);

        return $next($request);
    }
}



