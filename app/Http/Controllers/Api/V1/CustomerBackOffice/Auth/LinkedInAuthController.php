<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\LinkedIn\LinkedInCallBackInterface;
use App\Contracts\Api\V1\CustomerBackOffice\LinkedIn\LinkedInRedirectInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LinkedInAuthController extends Controller
{
    public LinkedInRedirectInterface $linkedInRedirect;
    public LinkedInCallBackInterface $linkedInCallBack;

    public function __construct(LinkedInRedirectInterface $linkedInRedirect, LinkedInCallBackInterface $linkedInCallBack)
    {
        $this->linkedInRedirect = $linkedInRedirect;
        $this->linkedInCallBack = $linkedInCallBack;
    }

    public function redirectToLinkedIn(Request $request)
    {
        return $this->linkedInRedirect->handle($request);
    }

    public function handleLinkedInCallback(Request $request)
    {
        return $this->linkedInCallBack->handle($request);
    }
}

