<?php

namespace App\Services\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\Auth\ForgotPasswordInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Jobs\Auth\ForgotPasswordJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Customer;
use Carbon\Carbon;
use App\Helper;

class ForgotPasswordService implements ForgotPasswordInterface
{
    public function handle($request)
    {
        try {

            $token = Str::uuid()->toString();
            $customer = Customer::where('email', $request->email)->first();
            if ($customer) {
                DB::table('password_reset_token_customers')->updateOrInsert(
                    ['email' => $request->email],
                    [
                        'token' => $token,
                        'created_at' => Carbon::now(),
                    ]
                );
                ForgotPasswordJob::dispatch($customer, $token);
            }
            return Helper::response('Forget Password', 'If an account exists we will send you an email to reset your password', ResponseAlias::HTTP_OK);
        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}
