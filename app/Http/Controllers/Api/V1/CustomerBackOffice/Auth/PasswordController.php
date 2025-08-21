<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Auth;

use App\Http\Requests\Api\V1\CustomerBackOffice\Auth\ForgotPasswordRequest;
use App\Contracts\Api\V1\CustomerBackOffice\Auth\ForgotPasswordInterface;
use App\Http\Requests\Api\V1\CustomerBackOffice\Auth\SetPasswordRequest;
use App\Contracts\Api\V1\CustomerBackOffice\Auth\SetPasswordInterface;
use App\Contracts\Api\V1\CustomerBackOffice\Auth\VerifyEmailInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    protected ForgotPasswordInterface $forgotPassword;
    protected VerifyEmailInterface $verifyEmail;
    protected SetPasswordInterface $setPassword;

    /**
     * @param ForgotPasswordInterface $forgotPassword
     * @param VerifyEmailInterface $verifyEmail
     * @param SetPasswordInterface $setPassword
     */
    public function __construct(
        ForgotPasswordInterface $forgotPassword,
        VerifyEmailInterface    $verifyEmail,
        SetPasswordInterface    $setPassword
    )
    {
        $this->forgotPassword = $forgotPassword;
        $this->verifyEmail = $verifyEmail;
        $this->setPassword = $setPassword;
    }

    /**
     * @param ForgotPasswordRequest $request
     * @return mixed
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        return $this->forgotPassword->handle($request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function verifyEmail(Request $request)
    {
        return $this->verifyEmail->handle($request);
    }

    public function setPassword(SetPasswordRequest $request)
    {
        return $this->setPassword->handle($request);
    }
}
