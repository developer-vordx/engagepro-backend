<?php

namespace App\Services\Api\V1\AdminBackoffice\Auth;

use App\Contracts\Api\V1\AdminBackOffice\Auth\PasswordResetInterface;
use App\Models\User;
use App\Utils\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use function App\Services\Api\V1\Auth\errors;
use function App\Services\Api\V1\Auth\success;

class PasswordResetService extends  BaseService  implements PasswordResetInterface
{
    public function handle($request)
    {
        try {

            // Token is valid and matches, now reset password
            $user = User::where('email', $request->email)->first();

            $user->password = Hash::make($request->password);
            $user->save();

            // Optionally delete token
            DB::table('password_reset_token_users')->where('email', $request->email)->delete();
            return success('Password reset successfully',[],200);
        } catch (\Exception $exception) {

            $this->logException($exception);
            return errors($exception->getMessage(), $exception->getCode(),500);
        }
    }
}
