<?php

namespace App\Services\Api\V1\AdminBackoffice\Auth;

use App\Contracts\Api\V1\AdminBackOffice\Auth\LoginInterface;
use App\Helper;
use App\Utils\BaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class LoginService extends BaseService implements LoginInterface
{

    /**
     * @param $request
     * @return JsonResponse
     */
    public function handle($request): JsonResponse
    {
        try {

            if (!$token = Auth::guard('user')->attempt($request->only('email', 'password'))) {
                return Helper::response('Unauthorized', 'Invalid credentials provided', ResponseAlias::HTTP_UNAUTHORIZED);
            }

            $data = [
                'user' => Auth::guard('user')->user(),
                'token' => $token,
            ];

            return Helper::response('User logged in successfully', $data, ResponseAlias::HTTP_OK);
        } catch (\Exception $exception) {
            return Helper::errors($exception);
        }
    }
}
