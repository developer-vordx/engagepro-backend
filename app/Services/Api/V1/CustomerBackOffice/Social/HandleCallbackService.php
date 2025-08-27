<?php

namespace App\Services\Api\V1\CustomerBackOffice\Social;

use App\Contracts\Api\V1\CustomerBackOffice\Social\HandleCallbackInterface;
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
                    'Invalid state parameter',
                    'Security validation failed',
                    ResponseAlias::HTTP_BAD_REQUEST
                );
            }

            // Check for error in callback
            if ($request->error) {
                return Helper::response(
                    'Authorization failed',
                    $request->error_description ?? $request->error,
                    ResponseAlias::HTTP_BAD_REQUEST
                );
            }

            // Validate authorization code
            if (!$request->code) {
                return Helper::response(
                    'Missing authorization code',
                    'No authorization code received from TikTok',
                    ResponseAlias::HTTP_BAD_REQUEST
                );
            }

            // Exchange code for token
            $tokenData = $service->exchangeCodeForToken($request->code);

            if (isset($tokenData['header_code'])) {
                return Helper::response(
                    ResponseAlias::$statusTexts[$tokenData['header_code']],
                    $tokenData['body'],
                    $tokenData['header_code']
                );
            }

            // Get comprehensive user profile from TikTok
            $profileData = $service->getUserProfile($tokenData['access_token']);

            if (isset($profileData['header_code'])) {
                return Helper::response(
                    ResponseAlias::$statusTexts[$profileData['header_code']],
                    $profileData['body'],
                    $profileData['header_code']
                );
            }

            // Check if this TikTok account is already linked to another user
            $existingAccount = CustomerAccount::whereHas('socialAccount', function ($q) use ($platform) {
                $q->where('slug', $platform);
            })->where('identifier', $profileData['identifier'])
                ->where('customer_id', '!=', $user->id)
                ->first();

            if ($existingAccount) {
                return Helper::response(
                    Response::$statusTexts[ResponseAlias::HTTP_CONFLICT],
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
                $tokenExpiresAt = isset($tokenData['expires_in'])
                    ? Carbon::now()->addSeconds($tokenData['expires_in'])
                    : null;

                // Create or update customer account with comprehensive data
                $customerAccount = CustomerAccount::updateOrCreate(
                    [
                        'social_accounts_id' => $socialAccount->id,
                        'customer_id' => $user->id,
                        'identifier' => $profileData['identifier'],
                    ],
                    [
                        'username' => $profileData['username'] ?? $profileData['display_name'] ?? 'Unknown',
                        'display_name' => $profileData['display_name'] ?? $profileData['username'] ?? 'Unknown',
                        'profile_picture' => $profileData['profile_picture'],
                        'follower_count' => $profileData['follower_count'] ?? 0,
                        'following_count' => $profileData['following_count'] ?? 0,
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? null,
                        'token_expires_at' => $tokenExpiresAt,
                        'platform_data' => json_encode([
                            'union_id' => $profileData['union_id'] ?? null,
                            'bio_description' => $profileData['bio_description'] ?? null,
                            'profile_deep_link' => $profileData['profile_deep_link'] ?? null,
                            'is_verified' => $profileData['is_verified'] ?? false,
                            'likes_count' => $profileData['likes_count'] ?? 0,
                            'video_count' => $profileData['video_count'] ?? 0,
                            'profile_picture_100' => $profileData['profile_picture_100'] ?? null,
                            'scopes' => $tokenData['scope'] ?? '',
                            'token_type' => $tokenData['token_type'] ?? 'Bearer',
                            'refresh_expires_in' => $tokenData['refresh_expires_in'] ?? null,
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
                    'is_verified' => $profileData['is_verified'] ?? false,
                    'bio_description' => $profileData['bio_description'] ?? null,
                    'likes_count' => $profileData['likes_count'] ?? 0,
                    'video_count' => $profileData['video_count'] ?? 0,
                    'profile_deep_link' => $profileData['profile_deep_link'] ?? null,
                    'last_synced_at' => $customerAccount->last_synced_at->toISOString(),
                    'token_expires_at' => $tokenExpiresAt?->toISOString(),
                    'scopes' => explode(',', $tokenData['scope'] ?? ''),
                ];

                return Helper::response(
                    ResponseAlias::$statusTexts[ResponseAlias::HTTP_OK],
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
     * @return array
     */
    public function refreshAccountToken(CustomerAccount $account): array
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

            if (isset($refreshResult['header_code'])) {
                return [
                    'success' => false,
                    'message' => $refreshResult['body'],
                    'code' => $refreshResult['header_code']
                ];
            }

            // Update account with new token data
            $account->update([
                'access_token' => $refreshResult['access_token'],
                'refresh_token' => $refreshResult['refresh_token'] ?? $account->refresh_token,
                'token_expires_at' => isset($refreshResult['expires_in'])
                    ? Carbon::now()->addSeconds($refreshResult['expires_in'])
                    : null,
                'last_synced_at' => now(),
                'platform_data' => array_merge(
                    json_decode($account->platform_data, true) ?? [],
                    [
                        'last_token_refresh' => now()->toISOString(),
                        'refresh_expires_in' => $refreshResult['refresh_expires_in'] ?? null,
                    ]
                )
            ]);

            return [
                'success' => true,
                'message' => 'Token refreshed successfully',
                'expires_at' => $account->token_expires_at?->toISOString()
            ];

        } catch (\Exception $e) {

            return [
                'success' => false,
                'message' => 'Token refresh failed: ' . $e->getMessage()
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

            if (isset($profileData['header_code'])) {
                return [
                    'success' => false,
                    'message' => $profileData['body'],
                    'code' => $profileData['header_code']
                ];
            }

            // Update account with fresh data
            $account->update([
                'username' => $profileData['username'] ?? $account->username,
                'display_name' => $profileData['display_name'] ?? $account->display_name,
                'profile_picture' => $profileData['profile_picture'] ?? $account->profile_picture,
                'follower_count' => $profileData['follower_count'] ?? $account->follower_count,
                'following_count' => $profileData['following_count'] ?? $account->following_count,
                'platform_data' => json_encode(array_merge(
                    json_decode($account->platform_data, true) ?? [],
                    [
                        'bio_description' => $profileData['bio_description'] ?? null,
                        'is_verified' => $profileData['is_verified'] ?? false,
                        'likes_count' => $profileData['likes_count'] ?? 0,
                        'video_count' => $profileData['video_count'] ?? 0,
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
