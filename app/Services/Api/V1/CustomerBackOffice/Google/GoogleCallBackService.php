<?php

namespace App\Services\Api\V1\CustomerBackOffice\Google;

use App\Contracts\Api\V1\CustomerBackOffice\Google\GoogleCallBackInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Helper;

class GoogleCallBackService implements GoogleCallBackInterface
{
    public function handle($request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find or create the customer
            $customer = Customer::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(16),
                ]
            );

            // Generate token directly from the user
            $token = Auth::guard('customer')->login($customer);

            $response = [
                'user' => $customer,
                'token' => $token,
            ];

            return Helper::response('User logged in successfully via Google', $response, ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            // Check if it's the "link expired" type of error
            if (str_contains($e->getMessage(), 'invalid_grant')) {
                return Helper::response('Link Expired', 'This Google sign-in link has expired. Please try logging in again.', ResponseAlias::HTTP_GONE);
            }
            return Helper::errors($e);
        }
    }
}
