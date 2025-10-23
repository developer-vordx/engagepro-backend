<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Auth;

use App\Contracts\Api\V1\CustomerBackOffice\TikTok\TikTokCallBackInterface;
use App\Contracts\Api\V1\CustomerBackOffice\TikTok\TikTokRedirectInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TikTokAuthController extends Controller
{
    public TikTokRedirectInterface $tiktokRedirect;
    public TikTokCallBackInterface $tiktokCallBack;

    public function __construct(TikTokRedirectInterface $tiktokRedirect, TikTokCallBackInterface $tiktokCallBack)
    {
        $this->tiktokRedirect = $tiktokRedirect;
        $this->tiktokCallBack = $tiktokCallBack;
    }

    public function redirectToTikTok(Request $request)
    {
        return $this->tiktokRedirect->handle($request);
    }

    public function handleTikTokCallback(Request $request)
    {
        return $this->tiktokCallBack->handle($request);
    }
}

