<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Social;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccount;
use App\Library\SocialManager\SocialMediaManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helper;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class SocialAccountController extends Controller
{
    private SocialMediaManager $socialMediaManager;

    public function __construct(SocialMediaManager $socialMediaManager)
    {
        $this->socialMediaManager = $socialMediaManager;
    }

    /**
     * Get all connected social accounts for authenticated customer
     */
    public function index(Request $request)
    {
        try {
            $customer = Auth::guard('customer')->user();

            $accounts = CustomerAccount::with(['socialAccount'])
                ->where('customer_id', $customer->id)
                ->where('is_active', true)
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'social_accounts_id' => $account->social_accounts_id,
                        'customer_id' => $account->customer_id,
                        'identifier' => $account->identifier,
                        'username' => $account->username,
                        'display_name' => $account->display_name,
                        'profile_picture' => $account->profile_picture,
                        'follower_count' => $account->follower_count,
                        'following_count' => $account->following_count,
                        'is_active' => $account->is_active,
                        'last_synced_at' => $account->last_synced_at?->toISOString(),
                        'token_expires_at' => $account->token_expires_at?->toISOString(),
                        'platform_data' => $account->platform_data,
                        'socialAccount' => [
                            'id' => $account->socialAccount->id,
                            'name' => $account->socialAccount->name,
                            'slug' => $account->socialAccount->slug,
                            'url' => $account->socialAccount->url,
                            'status' => $account->socialAccount->status,
                        ],
                    ];
                })
                ->values() // Reset array keys and convert to array
                ->toArray(); // Convert Collection to plain array

            return Helper::response($accounts, ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }

    /**
     * Get a specific connected account
     */
    public function show($id)
    {
        try {
            $customer = Auth::guard('customer')->user();

            $account = CustomerAccount::with(['socialAccount'])
                ->where('customer_id', $customer->id)
                ->findOrFail($id);

            return Helper::response([
                'id' => $account->id,
                'platform' => $account->socialAccount->slug,
                'platform_name' => $account->socialAccount->name,
                'identifier' => $account->identifier,
                'username' => $account->username,
                'display_name' => $account->display_name,
                'profile_picture' => $account->profile_picture,
                'follower_count' => $account->follower_count,
                'following_count' => $account->following_count,
                'is_active' => $account->is_active,
                'last_synced_at' => $account->last_synced_at,
                'token_expires_at' => $account->token_expires_at,
                'platform_data' => $account->platform_data,
            ], ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }

    /**
     * Disconnect (delete) a social account
     */
    public function destroy($id)
    {
        try {
            $customer = Auth::guard('customer')->user();

            $account = CustomerAccount::where('customer_id', $customer->id)->findOrFail($id);
            
            $platformName = $account->socialAccount->name;
            $account->delete();

            return Helper::response([
                'message' => "{$platformName} account disconnected successfully"
            ], ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }

    /**
     * Sync account data with platform
     */
    public function sync($id)
    {
        try {
            $customer = Auth::guard('customer')->user();

            $account = CustomerAccount::with(['socialAccount'])
                ->where('customer_id', $customer->id)
                ->findOrFail($id);

            $service = $this->socialMediaManager->getService($account->socialAccount->slug);

            if (!$service) {
                return Helper::response(
                    "Service not available for {$account->socialAccount->name}",
                    ResponseAlias::HTTP_NOT_ACCEPTABLE
                );
            }

            // Auto-refresh token if needed
            if (method_exists($service, 'autoRefreshTokenIfNeeded')) {
                $refreshResult = $service->autoRefreshTokenIfNeeded($account);
                if ($refreshResult['header_code'] != ResponseAlias::HTTP_OK) {
                    return Helper::response($refreshResult['body'], $refreshResult['header_code']);
                }
                $account->refresh();
            }

            // Get fresh profile data
            $profileData = $service->getUserProfile($account->access_token);

            if ($profileData['header_code'] != ResponseAlias::HTTP_OK) {
                return Helper::response($profileData['body'], $profileData['header_code']);
            }

            // Update account with fresh data
            $account->update([
                'username' => $profileData['body']['username'] ?? $account->username,
                'display_name' => $profileData['body']['display_name'] ?? $account->display_name,
                'profile_picture' => $profileData['body']['profile_picture'] ?? $account->profile_picture,
                'follower_count' => $profileData['body']['follower_count'] ?? $account->follower_count,
                'following_count' => $profileData['body']['following_count'] ?? $account->following_count,
                'last_synced_at' => now(),
            ]);

            return Helper::response([
                'message' => 'Account synced successfully',
                'account' => [
                    'id' => $account->id,
                    'platform' => $account->socialAccount->slug,
                    'username' => $account->username,
                    'display_name' => $account->display_name,
                    'profile_picture' => $account->profile_picture,
                    'follower_count' => $account->follower_count,
                    'following_count' => $account->following_count,
                    'last_synced_at' => $account->last_synced_at,
                ]
            ], ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}

