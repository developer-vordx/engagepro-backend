<?php

namespace App\Services\Api\V1\AdminBackoffice\User;

use App\Contracts\Api\V1\AdminBackOffice\User\ProfileInterface;
use App\Utils\BaseService;
use function App\Services\Api\V1\User\errors;
use function App\Services\Api\V1\User\success;

class ProfileService extends  BaseService  implements ProfileInterface
{
    public function handle($request)
    {
        try {
            $user = auth()->user();
            return success('user profile',$user);
        } catch (\Exception $exception) {

            $this->logException($exception);
            return errors($exception->getMessage(), $exception->getCode(),500);
        }
    }
}
