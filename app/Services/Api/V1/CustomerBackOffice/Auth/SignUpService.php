<?php

namespace App\Services\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\Auth\SignUpInterface;
use App\DTO\Api\V1\CustomerBackOffice\Auth\RegisterCustomerDTO;
use App\Helper;
use App\Jobs\Auth\EmailVerificationJob;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class SignUpService implements SignUpInterface
{
    public function handle($request)
    {
        try {
            DB::beginTransaction();
            $customer = Customer::create((new RegisterCustomerDTO($request))->toArray());
            $token = Str::uuid()->toString();
            DB::table('password_reset_token_customers')->updateOrInsert(
                [
                    'email' => $request->email
                ], [
                    'token' => $token,
                    'created_at' => Carbon::now(),
                ]
            );
            EmailVerificationJob::dispatch($customer, $token);
            DB::commit();
            return Helper::response(
                'User registered successfully.',
                'Please check your inbox and verify your email.',
                ResponseAlias::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return Helper::errors($e);
        }
    }
}
