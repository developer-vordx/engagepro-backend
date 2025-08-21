<?php

namespace App\Services\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\Auth\SetPasswordInterface;
use App\Jobs\Auth\PasswordChangedJob;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Helper;

class SetPasswordService implements SetPasswordInterface
{
    public function handle($request)
    {
        try {
            $tokenQuery = DB::table('password_reset_token_customers')
                ->where('email', $request->email)
                ->where('token', $request->token);
            $checkToken = $tokenQuery->first();

            if ($checkToken) {
                $tokenQuery->delete();
                $customer = tap(Customer::where('email', $request->email))
                    ->update(['password' => Hash::make($request->password)])->first();
                PasswordChangedJob::dispatch($customer);

                return Helper::response(
                    'Password updated.',
                    'Password updated successfully',
                    ResponseAlias::HTTP_OK
                );
            }

            return Helper::response(
                'Link expired.',
                'Request url is not available or expired',
                ResponseAlias::HTTP_GONE
            );

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}
