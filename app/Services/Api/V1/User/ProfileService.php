<?php

namespace App\Services\Api\V1\User;

use App\Contracts\Api\V1\User\ProfileInterface;
use App\Utils\BaseService;

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
