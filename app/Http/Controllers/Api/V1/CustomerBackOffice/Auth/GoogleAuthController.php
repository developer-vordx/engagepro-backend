<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\Google\GoogleCallBackInterface;
use App\Contracts\Api\V1\CustomerBackOffice\Google\GoogleRedirectInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{

    public GoogleRedirectInterface $googleRedirect;
    public GoogleCallBackInterface $googleCallBack;


    public function __construct(GoogleRedirectInterface $googleRedirect, GoogleCallBackInterface $googleCallBack)
    {
        $this->googleRedirect = $googleRedirect;
        $this->googleCallBack = $googleCallBack;
    }

    public function redirectToGoogle(Request $request)
    {
        return $this->googleRedirect->handle($request);
    }

    public function handleGoogleCallback(Request $request)
    {
        return $this->googleCallBack->handle($request);
    }
}
