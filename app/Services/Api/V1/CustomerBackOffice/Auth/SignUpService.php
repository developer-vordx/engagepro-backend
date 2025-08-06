<?php

namespace App\Services\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\Auth\SignUpInterface;
use App\DTO\Api\V1\CustomerBackOffice\Auth\RegisterCustomerDTO;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Jobs\Auth\EmailVerificationJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Customer;
use Carbon\Carbon;
use App\Helper;

class SignUpService implements SignUpInterface
{
    public function handle($request)
    {
        try {
            $customer = Customer::create((new RegisterCustomerDTO($request))->toArray());
            $token = Str::uuid()->toString();
            DB::table('password_reset_token_customers')->updateOrInsert(
                [
                    'email' => $request->email
                ], [
                    'token' => Hash::make($request->password),
                    'created_at' => Carbon::now(),
                ]
            );
            EmailVerificationJob::dispatch($customer, $token);
            return Helper::response('User registered.', 'Please check your inbox and verify your email.', ResponseAlias::HTTP_CREATED);
        } catch (\Exception $e) {
            return Helper::errors($e);
        }

    }
}
