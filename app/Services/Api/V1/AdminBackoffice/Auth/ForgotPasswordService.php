<?php

namespace App\Services\Api\V1\AdminBackoffice\Auth;

use App\Contracts\Api\V1\AdminBackOffice\Auth\ForgotPasswordInterface;
use App\Mail\Auth\PasswordResetMail;
use App\Utils\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use function App\Services\Api\V1\Auth\errors;
use function App\Services\Api\V1\Auth\success;

class ForgotPasswordService extends BaseService implements ForgotPasswordInterface
{
    public function handle($request)
    {
        try {

            $token = Str::random(64);

            DB::table('password_reset_token_users')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => Carbon::now(),
                ]
            );

            Mail::to($request->email)->send(new PasswordResetMail($token, $request->email));

            return success('Reset link sent', ['token' => $token], 200);
        } catch (\Exception $exception) {

            $this->logException($exception);
            return errors($exception->getMessage(), $exception->getCode(), 500);
        }
    }
}
