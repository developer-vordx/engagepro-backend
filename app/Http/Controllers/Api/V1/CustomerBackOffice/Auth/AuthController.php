<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Auth;

use App\Http\Requests\Api\V1\CustomerBackOffice\Auth\SignUpRequest;
use App\Http\Requests\Api\V1\CustomerBackOffice\Auth\LoginRequest;
use App\Contracts\Api\V1\CustomerBackOffice\Auth\SignUpInterface;
use App\Contracts\Api\V1\CustomerBackOffice\Auth\LoginInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    protected LoginInterface $login;
    protected SignUpInterface $signup;

    /**
     * @param LoginInterface $login
     * @param SignUpInterface $signup
     */
    public function __construct(LoginInterface $login, SignUpInterface $signup)
    {
        $this->login = $login;
        $this->signup = $signup;
    }

    /**
     * @param SignUpRequest $request
     * @return mixed
     */
    public function signup(SignUpRequest $request)
    {
        return $this->signup->handle($request);
    }

    /**
     * @param LoginRequest $request
     * @return mixed
     */
    public function login(LoginRequest $request): mixed
    {
        return $this->login->handle($request);
    }

    public function logout()
    {
        try {
            Auth::guard('customer')->logout();
            return success('User successfully logged out.', [], 200);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return errors('Failed to logout, please try again.', [], 401);
        }
    }

}
