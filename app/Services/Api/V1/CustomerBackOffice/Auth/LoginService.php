<?php

namespace App\Services\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\Auth\LoginInterface;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Utils\BaseService;
use App\Helper;

class LoginService extends BaseService implements LoginInterface
{
    /**
     * @param $request
     * @return JsonResponse
     */
    public function handle($request): JsonResponse
    {
        try {

            if (!$token = Auth::guard('customer')->attempt($request->only('email', 'password'))) {
                return Helper::response('Unauthorized', 'Invalid credentials provided', ResponseAlias::HTTP_UNAUTHORIZED);
            }

            $data = [
                'user' => Auth::guard('customer')->user(),
                'token' => $token,
            ];

            return Helper::response('User logged in successfully', $data, ResponseAlias::HTTP_OK);
        } catch (\Exception $exception) {
            return Helper::errors($exception);
        }
    }
}
