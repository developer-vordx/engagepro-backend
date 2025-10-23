<?php

namespace App\Services\Api\V1\CustomerBackOffice\X;

use App\Contracts\Api\V1\CustomerBackOffice\X\XCallBackInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Helper;

class XCallBackService implements XCallBackInterface
{
    public function handle($request)
    {
        try {
            $xUser = Socialite::driver('twitter')->stateless()->user();

            // X/Twitter may not provide email, use nickname@x.local if email is missing
            $email = $xUser->getEmail() ?: ($xUser->getNickname() . '@x.engagepro.local');

            // Find or create the customer
            $customer = Customer::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $xUser->getName() ?: $xUser->getNickname(),
                    'x_id' => $xUser->getId(),
                    'avatar' => $xUser->getAvatar(),
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
            return redirect($frontendUrl . '/auth/callback/x?token=' . $token);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'invalid_grant')) {
                return Helper::response('This X sign-in link has expired. Please try logging in again.', ResponseAlias::HTTP_GONE);
            }
            return Helper::errors($e);
        }
    }
}

