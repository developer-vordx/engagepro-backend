<?php

namespace App\Services\Api\V1\CustomerBackOffice\Auth;

use App\Helper;
use App\Utils\BaseService;
use App\Contracts\Api\V1\CustomerBackOffice\Auth\GetAuthUserInterface;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class GetAuthUserService implements GetAuthUserInterface
{
    public function handle($request)
    {
        try {
            $authUser = auth('customer')->user();
            $payload = [
                'name' => $authUser->name,
                'email' => $authUser->email,
                'phone' => $authUser->phone,
                'address' => $authUser->address,
                'avatar' => $authUser->avatar,

            ];
            return Helper::response('User Logged in successfully', $payload, ResponseAlias::HTTP_OK);
        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}
