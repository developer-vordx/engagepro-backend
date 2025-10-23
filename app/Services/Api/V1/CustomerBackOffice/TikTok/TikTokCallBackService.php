<?php

namespace App\Services\Api\V1\CustomerBackOffice\TikTok;

use App\Contracts\Api\V1\CustomerBackOffice\TikTok\TikTokCallBackInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Helper;

class TikTokCallBackService implements TikTokCallBackInterface
{
    public function handle($request)
    {
        try {
            $tiktokUser = Socialite::driver('tiktok')->stateless()->user();

            // TikTok doesn't provide email, use open_id@tiktok.local
            $email = $tiktokUser->getId() . '@tiktok.engagepro.local';

            // Find or create the customer
            $customer = Customer::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $tiktokUser->getName() ?: $tiktokUser->getNickname(),
                    'tiktok_id' => $tiktokUser->getId(),
                    'avatar' => $tiktokUser->getAvatar(),
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

            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect($frontendUrl . '/auth/callback/tiktok?token=' . $token);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'invalid_grant')) {
                return Helper::response('This TikTok sign-in link has expired. Please try logging in again.', ResponseAlias::HTTP_GONE);
            }
            return Helper::errors($e);
        }
    }
}

