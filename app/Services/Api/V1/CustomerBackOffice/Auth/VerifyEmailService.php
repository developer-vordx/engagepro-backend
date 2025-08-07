<?php

namespace App\Services\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\Auth\VerifyEmailInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Support\Facades\DB;
use App\Helper;

class VerifyEmailService implements VerifyEmailInterface
{
    public function handle($request)
    {
        try {
            $queryCheck = DB::table('password_reset_token_customers')
                ->where('email', $request->email)
                ->where('token', $request->token);
            $checkToken = $queryCheck->first();
            if ($checkToken) {
                $queryCheck->delete();
                return Helper::response('Email verified.', 'Email verified successfully', ResponseAlias::HTTP_OK);
            }

            return Helper::response('Link expired.', 'Request url is not available or expired', ResponseAlias::HTTP_GONE);

        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}
