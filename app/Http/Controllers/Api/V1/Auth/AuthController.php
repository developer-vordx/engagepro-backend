<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Contracts\Api\V1\Auth\LoginInterface;
use App\Contracts\Api\V1\Auth\SignUpInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\SignUpRequest;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends Controller
{

    protected LoginInterface $login;
    protected SignUpInterface $signup;


    public function __construct(LoginInterface $login, SignUpInterface $signup)
    {
        $this->login = $login;
        $this->signup = $signup;
    }

    public function signup(SignUpRequest $request)
    {
        return $this->signup->handle($request);
    }

    public function login(LoginRequest $request)
    {
        return $this->login->handle($request);
    }

    public function logout()
    {
        try {
            // Invalidate the token
            JWTAuth::invalidate(JWTAuth::getToken());
            return success('User successfully logged out.',[],200);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return errors('Failed to logout, please try again.',[],401);
        }
    }

}
