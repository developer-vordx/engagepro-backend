<?php

namespace App\Services\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\Auth\UpdatePasswordInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Jobs\Auth\PasswordChangedJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Helper;

class UpdatePasswordService implements UpdatePasswordInterface
{
    public function handle($request)
    {
        try {
            DB::beginTransaction();
            $checkPassword = Hash::check($request->current_password, $request->customer->password);
            if (!$checkPassword) {
                return Helper::response(
                    'Password mismatch',
                    'Provided current password is incorrect',
                    ResponseAlias::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            PasswordChangedJob::dispatch($request->customer);
            $request->customer->update(['password' => Hash::make($request->password)]);
            DB::commit();
            return Helper::response(
                'Password updated',
                'Password updated successfully',
                ResponseAlias::HTTP_OK
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return Helper::errors($e);
        }
    }
}
