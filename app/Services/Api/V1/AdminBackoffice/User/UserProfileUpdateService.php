<?php

namespace App\Services\Api\V1\AdminBackoffice\User;

use App\Contracts\Api\V1\AdminBackOffice\User\UserProfileUpdateInterface;
use App\Utils\BaseService;
use function App\Services\Api\V1\User\errors;
use function App\Services\Api\V1\User\success;

class UserProfileUpdateService extends  BaseService  implements UserProfileUpdateInterface
{
    public function handle($request)
    {
        try {
            $data = $request->all();
            $user = auth()->user();
            $user->update($data);

            return success('Profile updated successfully.', $user);

        } catch (\Exception $exception) {

            $this->logException($exception);
            return errors($exception->getMessage(), $exception->getCode(),500);
        }
    }
}
