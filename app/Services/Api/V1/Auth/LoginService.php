<?php

namespace App\Services\Api\V1\Auth;

use App\Contracts\Api\V1\Auth\LoginInterface;
use App\Exceptions\ApiException;
use App\Utils\BaseService;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginService extends  BaseService  implements LoginInterface
{

    public function handle($request)
    {
        try {

            if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            $data = [
                'user' => auth()->user(),
                'token' => $token,
            ];

            return success('success', $data, 200);
        } catch (ApiException $exception) {

            $this->logException($exception);
            return errors($exception->getMessage(), $exception->getCode(),500);
        }
    }
}
