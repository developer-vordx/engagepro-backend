<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Auth;

use App\Http\Requests\Api\V1\CustomerBackOffice\Auth\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\CustomerBackOffice\Auth\UpdateProfileRequest;
use App\Contracts\Api\V1\CustomerBackOffice\Auth\UpdatePasswordInterface;
use App\Contracts\Api\V1\CustomerBackOffice\Auth\UpdateProfileInterface;
use App\Contracts\Api\V1\CustomerBackOffice\Auth\GetAuthUserInterface;
use App\Http\Requests\Api\V1\CustomerBackOffice\Auth\SignUpRequest;
use App\Http\Requests\Api\V1\CustomerBackOffice\Auth\LoginRequest;
use App\Contracts\Api\V1\CustomerBackOffice\Auth\SignUpInterface;
use App\Contracts\Api\V1\CustomerBackOffice\Auth\LogoutInterface;
use App\Contracts\Api\V1\CustomerBackOffice\Auth\LoginInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    protected LoginInterface $login;
    protected SignUpInterface $signup;
    protected UpdateProfileInterface $updateProfile;
    protected LogoutInterface $logout;
    protected UpdatePasswordInterface $updatePasswordInterface;
    protected GetAuthUserInterface $authUser;

    /**
     * @param LoginInterface $login
     * @param SignUpInterface $signup
     * @param UpdateProfileInterface $updateProfile
     * @param LogoutInterface $logout
     * @param UpdatePasswordInterface $updatePasswordInterface
     * @param GetAuthUserInterface $authUser
     */

    public function __construct(
        LoginInterface          $login,
        SignUpInterface         $signup,
        UpdateProfileInterface  $updateProfile,
        LogoutInterface         $logout,
        UpdatePasswordInterface $updatePasswordInterface,
        GetAuthUserInterface    $authUser
    )
    {
        $this->login = $login;
        $this->signup = $signup;
        $this->updateProfile = $updateProfile;
        $this->logout = $logout;
        $this->updatePasswordInterface = $updatePasswordInterface;
        $this->authUser = $authUser;
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

    /**
     * @param UpdateProfileRequest $request
     * @return mixed
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        return $this->updateProfile->handle($request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function logout(Request $request)
    {
        return $this->logout->handle($request);
    }

    /**
     * @param UpdatePasswordRequest $request
     * @return mixed
     */
    public function updatePassword(UpdatePasswordRequest $request)
    {
        return $this->updatePasswordInterface->handle($request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getAuthUser(Request $request)
    {
        return $this->authUser->handle($request);
    }

}
