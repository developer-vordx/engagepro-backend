<?php

namespace App\Services\Api\V1\CustomerBackOffice\Social;

use App\Contracts\Api\V1\CustomerBackOffice\Social\HandleCallbackInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Library\SocialManager\SocialMediaManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\CustomerAccount;
use App\Models\SocialAccount;
use Illuminate\Http\Response;
use Carbon\Carbon;
use App\Helper;

class HandleCallbackService implements HandleCallbackInterface
{
    private SocialMediaManager $socialMediaManager;

    public function __construct(SocialMediaManager $socialMediaManager)
    {
        $this->socialMediaManager = $socialMediaManager;
    }

    public function handle($request, $platform)
    {
        try {
            $user = Auth::guard('customer')->user();
            $service = $this->socialMediaManager->getService($platform);

            // Validate state parameter (CSRF protection)
            if ($request->state !== csrf_token()) {
                return Helper::response(
                    'Security validation failed',
                    ResponseAlias::HTTP_BAD_REQUEST
                );
            }

            // Check for error in callback
            if ($request->error) {
                return Helper::response(
                    $request->error_description ?? $request->error,
                    ResponseAlias::HTTP_BAD_REQUEST
                );
            }

            // Validate authorization code
            if (!$request->code) {
                return Helper::response(
                    'No authorization code received from provider',
                    ResponseAlias::HTTP_BAD_REQUEST
                );
            }

            // Exchange code for token
            $tokenData = $service->exchangeCodeForToken($request->code);
            if ($tokenData['header_code'] != ResponseAlias::HTTP_OK) {
                return Helper::response(
                    $tokenData['body'],
                    $tokenData['header_code']
                );
            }

            // Get comprehensive user profile from TikTok
            $profileData = $service->getUserProfile($tokenData['body']['access_token']);

            if ($profileData['header_code'] != ResponseAlias::HTTP_OK) {
                return $profileData;
            }

            // Check if this TikTok account is already linked to another user
            $existingAccount = CustomerAccount::whereHas('socialAccount', function ($q) use ($platform) {
                $q->where('slug', $platform);
            })->where('identifier', $profileData['body']['identifier'])
                ->where('customer_id', '!=', $user->id)
                ->first();

            if ($existingAccount) {
                return Helper::response(
                    'This TikTok account is already linked to another user.',
                    ResponseAlias::HTTP_CONFLICT
                );
            }

            DB::beginTransaction();

            try {
                // Get or create social account record
                $socialAccount = SocialAccount::where('slug', $platform)->first();

                if (!$socialAccount) {
                    throw new \Exception("Social account configuration for {$platform} not found");
                }

                // Calculate token expiration
                $tokenExpiresAt = isset($tokenData['body']['expires_in'])
                    ? Carbon::now()->addSeconds($tokenData['body']['expires_in'])
                    : null;

                // Create or update customer account with comprehensive data
                $customerAccount = CustomerAccount::updateOrCreate(
                    [
                        'social_accounts_id' => $socialAccount->id,
                        'customer_id' => $user->id,
                        'identifier' => $profileData['body']['identifier'],
                    ],
                    [
                        'username' => $profileData['body']['username'] ?? $profileData['body']['display_name'] ?? 'Unknown',
                        'display_name' => $profileData['body']['display_name'] ?? $profileData['body']['username'] ?? 'Unknown',
                        'profile_picture' => $profileData['body']['profile_picture'],
                        'follower_count' => $profileData['body']['follower_count'] ?? 0,
                        'following_count' => $profileData['body']['following_count'] ?? 0,
                        'access_token' => $tokenData['body']['access_token'],
                        'refresh_token' => $tokenData['body']['refresh_token'] ?? null,
                        'token_expires_at' => $tokenExpiresAt,
                        'platform_data' => json_encode([
                            'union_id' => $profileData['body']['union_id'] ?? null,
                            'bio_description' => $profileData['body']['bio_description'] ?? null,
                            'profile_deep_link' => $profileData['body']['profile_deep_link'] ?? null,
                            'is_verified' => $profileData['body']['is_verified'] ?? false,
                            'likes_count' => $profileData['body']['likes_count'] ?? 0,
                            'video_count' => $profileData['body']['video_count'] ?? 0,
                            'profile_picture_100' => $profileData['body']['profile_picture_100'] ?? null,
                            'scopes' => $tokenData['body']['scope'] ?? '',
                            'token_type' => $tokenData['body']['token_type'] ?? 'Bearer',
                            'refresh_expires_in' => $tokenData['body']['refresh_expires_in'] ?? null,
                            'last_token_refresh' => now()->toISOString(),
                        ]),
                        'is_active' => true,
                        'last_synced_at' => now(),
                    ]
                );

                DB::commit();

                // Prepare response data
                $responseData = [
                    'id' => $customerAccount->id,
                    'platform' => $platform,
                    'username' => $customerAccount->username,
                    'display_name' => $customerAccount->display_name,
                    'profile_picture' => $customerAccount->profile_picture,
                    'follower_count' => $customerAccount->follower_count,
                    'following_count' => $customerAccount->following_count,
                    'is_active' => $customerAccount->is_active,
                    'is_verified' => $profileData['body']['is_verified'] ?? false,
                    'bio_description' => $profileData['body']['bio_description'] ?? null,
                    'likes_count' => $profileData['body']['likes_count'] ?? 0,
                    'video_count' => $profileData['body']['video_count'] ?? 0,
                    'profile_deep_link' => $profileData['body']['profile_deep_link'] ?? null,
                    'last_synced_at' => $customerAccount->last_synced_at->toISOString(),
                    'token_expires_at' => $tokenExpiresAt?->toISOString(),
                    'scopes' => explode(',', $tokenData['body']['scope'] ?? ''),
                ];

                return Helper::response(
                    $responseData,
                    ResponseAlias::HTTP_OK
                );

            } catch (\Exception $dbException) {
                DB::rollBack();
                return Helper::errors($dbException);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            return Helper::errors($e);
        }
    }

    /**
     * Refresh expired tokens for a customer account
     * @param CustomerAccount $account
     * @return array|JsonResponse
     */
    public function refreshAccountToken(CustomerAccount $account): array|JsonResponse
    {
        try {
            $service = $this->socialMediaManager->getService($account->socialAccount->slug);

            if (!method_exists($service, 'refreshToken')) {
                return [
                    'success' => false,
                    'message' => 'Token refresh not supported for this platform'
                ];
            }

            $refreshResult = $service->refreshToken($account->refresh_token);

            if ($refreshResult['header_code'] != ResponseAlias::HTTP_OK) {
                return $refreshResult;
            }

            // Update account with new token data
            $account->update([
                'access_token' => $refreshResult['body']['access_token'],
                'refresh_token' => $refreshResult['body']['refresh_token'] ?? $account->refresh_token,
                'token_expires_at' => isset($refreshResult['body']['expires_in'])
                    ? Carbon::now()->addSeconds($refreshResult['body']['expires_in'])
                    : null,
                'last_synced_at' => now(),
                'platform_data' => array_merge(
                    json_decode($account->platform_data, true) ?? [],
                    [
                        'last_token_refresh' => now()->toISOString(),
                        'refresh_expires_in' => $refreshResult['body']['refresh_expires_in'] ?? null,
                    ]
                )
            ]);

            return [
                'header_code' => ResponseAlias::HTTP_OK,
                'body' => 'Token refreshed successfully',
            ];

        } catch (\Exception $e) {
            return [
                'header_code' => ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
                'body' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync account data with platform
     * @param CustomerAccount $account
     * @return array
     */
    public function syncAccountData(CustomerAccount $account): array
    {
        try {
            $service = $this->socialMediaManager->getService($account->socialAccount->slug);

            // Auto-refresh token if needed
            if (method_exists($service, 'autoRefreshTokenIfNeeded')) {
                $service->autoRefreshTokenIfNeeded($account);
                $account->refresh(); // Reload account data
            }

            // Get updated profile data
            $profileData = $service->getUserProfile($account->access_token);

            if ($profileData['header_code'] != ResponseAlias::HTTP_OK) {
                return $profileData;
            }

            // Update account with fresh data
            $account->update([
                'username' => $profileData['body']['username'] ?? $account->username,
                'display_name' => $profileData['body']['display_name'] ?? $account->display_name,
                'profile_picture' => $profileData['body']['profile_picture'] ?? $account->profile_picture,
                'follower_count' => $profileData['body']['follower_count'] ?? $account->follower_count,
                'following_count' => $profileData['body']['following_count'] ?? $account->following_count,
                'platform_data' => json_encode(array_merge(
                    json_decode($account->platform_data, true) ?? [],
                    [
                        'bio_description' => $profileData['body']['bio_description'] ?? null,
                        'is_verified' => $profileData['body']['is_verified'] ?? false,
                        'likes_count' => $profileData['body']['likes_count'] ?? 0,
                        'video_count' => $profileData['body']['video_count'] ?? 0,
                        'last_sync' => now()->toISOString(),
                    ]
                )),
                'last_synced_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Account data synchronized successfully',
                'data' => $account->fresh()
            ];

        } catch (\Exception $e) {

            return [
                'success' => false,
                'message' => 'Account sync failed: ' . $e->getMessage()
            ];
        }
    }
}
