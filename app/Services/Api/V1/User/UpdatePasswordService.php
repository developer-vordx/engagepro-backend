<?php

namespace App\Services\Api\V1\User;

use App\Contracts\Api\V1\User\UpdatePasswordInterface;
use App\Utils\BaseService;

class UpdatePasswordService extends  BaseService  implements UpdatePasswordInterface
{

    public function handle($request)
    {
        try {
            $user = auth()->user();

            // Check if the provided current password matches the user's actual password
            if (!\Hash::check($request->current_password, $user->password)) {
                return errors('Current password is incorrect.', [],422);
            }

            // Update to new password
            $user->password = bcrypt($request->new_password);
            $user->save();

            return success('Password updated successfully.', $user);
        } catch (\Exception $exception) {

            $this->logException($exception);
            return errors($exception->getMessage(), $exception->getCode(),500);
        }
    }
}
