<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\X\XCallBackInterface;
use App\Contracts\Api\V1\CustomerBackOffice\X\XRedirectInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class XAuthController extends Controller
{
    public XRedirectInterface $xRedirect;
    public XCallBackInterface $xCallBack;

    public function __construct(XRedirectInterface $xRedirect, XCallBackInterface $xCallBack)
    {
        $this->xRedirect = $xRedirect;
        $this->xCallBack = $xCallBack;
    }

    public function redirectToX(Request $request)
    {
        return $this->xRedirect->handle($request);
    }

    public function handleXCallback(Request $request)
    {
        return $this->xCallBack->handle($request);
    }
}

