<?php

namespace App\Services\Api\V1\AdminBackoffice\Auth;


use App\Contracts\Api\V1\AdminBackOffice\Auth\SignUpInterface;
use App\Helper;
use App\Models\User;
use App\Utils\BaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tymon\JWTAuth\Facades\JWTAuth;


class SignUpService extends BaseService implements SignUpInterface
{
    public function handle($request): JsonResponse
    {
        try {
            $user = User::create([
                'first_name' => $request['first_name'] ?? 'N/A',
                'last_name' => $request['last_name'] ?? 'N/A',
                'email' => $request['email'],
                'password' => Hash::make($request['password']),
                ''
            ]);

            $token = JWTAuth::fromUser($user);
            $response = [
                'user' => $user,
                'token' => $token,
            ];
            return Helper::response('User created successfully', $response, ResponseAlias::HTTP_CREATED);

        } catch (\Exception $exception) {
            return Helper::errors($exception);
        }
    }
}
