<?php

namespace App\Services\Api\V1\CustomerBackOffice\TikTok;

use App\Contracts\Api\V1\CustomerBackOffice\TikTok\TikTokCallBackInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Library\SocialManager\TikTokService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Helper;

class TikTokCallBackService implements TikTokCallBackInterface
{
    private TikTokService $tiktokService;

    public function __construct(TikTokService $tiktokService)
    {
        $this->tiktokService = $tiktokService;
    }

    public function handle($request)
    {
        try {
            // Get authorization code from request
            $code = $request->get('code');
            if (!$code) {
                return Helper::response('Authorization code not provided', ResponseAlias::HTTP_BAD_REQUEST);
            }


            $tokenResponse = $this->tiktokService->exchangeCodeForToken($code);
            if ($tokenResponse['header_code'] !== ResponseAlias::HTTP_OK) {
                return Helper::response('Failed to exchange code for token: ' . ($tokenResponse['body'] ?? 'Unknown error'), ResponseAlias::HTTP_BAD_REQUEST);
            }

            // Get user profile
            $userProfile = $this->tiktokService->getUserProfile($tokenResponse['body']['access_token']);
            if ($userProfile['header_code'] !== ResponseAlias::HTTP_OK) {
                return Helper::response('Failed to get user profile: ' . ($userProfile['body'] ?? 'Unknown error'), ResponseAlias::HTTP_BAD_REQUEST);
            }

            // Try different possible response structures
            if (isset($userProfile['body']['data']['user'])) {
                $userData = $userProfile['body']['data']['user'];
                $openId = $userData['open_id'] ?? null;
            } elseif (isset($userProfile['body']['user'])) {
                $userData = $userProfile['body']['user'];
                $openId = $userData['open_id'] ?? null;
            } elseif (isset($userProfile['body']['data'])) {
                $userData = $userProfile['body']['data'];
                $openId = $userData['open_id'] ?? null;
            } elseif (isset($userProfile['body']['identifier'])) {
                // TikTok API v2 returns user data directly in body with 'identifier' as the user ID
                $userData = $userProfile['body'];
                $openId = $userData['identifier'] ?? null;
            } else {
                return Helper::response('Invalid user profile response structure', ResponseAlias::HTTP_BAD_REQUEST);
            }

            if (!$openId) {
                return Helper::response('User profile missing identifier', ResponseAlias::HTTP_BAD_REQUEST);
            }

            // TikTok doesn't provide email, use open_id@tiktok.local
            $email = $openId . '@tiktok.meedyo.local';

            // Find or create the customer
            $customer = Customer::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $userData['display_name'] ?? $userData['username'] ?? 'TikTok User',
                    'tiktok_id' => $openId,
                    'avatar' => $userData['profile_picture'] ?? $userData['avatar_url'] ?? null,
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(16),
                ]
            );

            // Update status to active and last_login
            $customer->update([
                'status' => true,
                'last_login' => now(),
            ]);

            // Generate token
            $token = Auth::guard('customer')->login($customer);

            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000/callback');
            return redirect($frontendUrl . '?token=' . $token);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'invalid_grant')) {
                return Helper::response('This TikTok sign-in link has expired. Please try logging in again.', ResponseAlias::HTTP_GONE);
            }
            return Helper::errors($e);
        }
    }
}

