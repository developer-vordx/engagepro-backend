<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\Facebook\FacebookCallBackInterface;
use App\Contracts\Api\V1\CustomerBackOffice\Facebook\FacebookRedirectInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FacebookAuthController extends Controller
{
    public FacebookRedirectInterface $facebookRedirect;
    public FacebookCallBackInterface $facebookCallBack;

    public function __construct(FacebookRedirectInterface $facebookRedirect, FacebookCallBackInterface $facebookCallBack)
    {
        $this->facebookRedirect = $facebookRedirect;
        $this->facebookCallBack = $facebookCallBack;
    }

    public function redirectToFacebook(Request $request)
    {
        return $this->facebookRedirect->handle($request);
    }

    public function handleFacebookCallback(Request $request)
    {
        return $this->facebookCallBack->handle($request);
    }
}

