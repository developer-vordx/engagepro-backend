<?php

namespace App\Http\Controllers\Api\V1\AdminBackOffice\Auth;


use App\Contracts\Api\V1\AdminBackOffice\Auth\ForgotPasswordInterface;
use App\Contracts\Api\V1\AdminBackOffice\Auth\PasswordResetInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminBackOffice\Auth\ForgetPasswordRequest;
use App\Http\Requests\Api\V1\AdminBackOffice\Auth\ResetPasswordRequest;

class PasswordResetController extends Controller
{

    protected ForgotPasswordInterface $forgotPassword;
    protected  PasswordResetInterface $passwordReset;

    public function __construct(PasswordResetInterface $passwordReset, ForgotPasswordInterface $forgotPassword)
    {
        $this->passwordReset = $passwordReset;
        $this->forgotPassword = $forgotPassword;
    }

    public function forgotPassword(ForgetPasswordRequest $request)
    {
        return  $this->forgotPassword->handle($request);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        return  $this->passwordReset->handle($request);
    }
}
