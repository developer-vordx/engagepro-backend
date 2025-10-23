<?php

namespace App\Services\Api\V1\CustomerBackOffice\Facebook;

use App\Contracts\Api\V1\CustomerBackOffice\Facebook\FacebookCallBackInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Helper;

class FacebookCallBackService implements FacebookCallBackInterface
{
    public function handle($request)
    {
        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->user();

            // Find or create the customer
            $customer = Customer::firstOrCreate(
                ['email' => $facebookUser->getEmail()],
                [
                    'name' => $facebookUser->getName(),
                    'facebook_id' => $facebookUser->getId(),
                    'avatar' => $facebookUser->getAvatar(),
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
            return redirect($frontendUrl . '/auth/callback/facebook?token=' . $token);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'invalid_grant')) {
                return Helper::response('This Facebook sign-in link has expired. Please try logging in again.', ResponseAlias::HTTP_GONE);
            }
            return Helper::errors($e);
        }
    }
}

