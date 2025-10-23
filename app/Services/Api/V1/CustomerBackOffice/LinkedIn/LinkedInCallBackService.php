<?php

namespace App\Services\Api\V1\CustomerBackOffice\LinkedIn;

use App\Contracts\Api\V1\CustomerBackOffice\LinkedIn\LinkedInCallBackInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Helper;

class LinkedInCallBackService implements LinkedInCallBackInterface
{
    public function handle($request)
    {
        try {
            $linkedInUser = Socialite::driver('linkedin-openid')->stateless()->user();

            // Find or create the customer
            $customer = Customer::firstOrCreate(
                ['email' => $linkedInUser->getEmail()],
                [
                    'name' => $linkedInUser->getName(),
                    'linkedin_id' => $linkedInUser->getId(),
                    'avatar' => $linkedInUser->getAvatar(),
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
            return redirect($frontendUrl . '/auth/callback/linkedin?token=' . $token);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'invalid_grant')) {
                return Helper::response('This LinkedIn sign-in link has expired. Please try logging in again.', ResponseAlias::HTTP_GONE);
            }
            return Helper::errors($e);
        }
    }
}

