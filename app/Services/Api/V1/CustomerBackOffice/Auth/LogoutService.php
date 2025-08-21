<?php

namespace App\Services\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\Auth\LogoutInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Helper;

class LogoutService implements LogoutInterface
{
    public function handle($request)
    {
        try {
            auth('customer')->logout();
            return Helper::response(
                'Logout',
                'You are successfully logged out',
                ResponseAlias::HTTP_OK
            );
        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}
