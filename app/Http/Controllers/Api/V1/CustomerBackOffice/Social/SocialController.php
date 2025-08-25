<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Social;

use App\Contracts\Api\V1\CustomerBackOffice\Social\GetAuthUrlInterface;
use App\Contracts\Api\V1\CustomerBackOffice\Social\HandleCallbackInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerBackOffice\Social\HandleCallback;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    private GetAuthUrlInterface $getAuthUrl;
    private HandleCallbackInterface $handleCallback;

    /**
     * @param GetAuthUrlInterface $getAuthUrl
     * @param HandleCallbackInterface $handleCallback
     */
    public function __construct(GetAuthUrlInterface $getAuthUrl, HandleCallbackInterface $handleCallback)
    {
        $this->getAuthUrl = $getAuthUrl;
        $this->handleCallback = $handleCallback;
    }

    /**
     * @param Request $request
     * @param $platform
     * @return mixed
     */
    public function getAuthUrl(Request $request, $platform): mixed
    {
        return $this->getAuthUrl->handle($request, $platform);
    }

    /**
     * @param HandleCallback $request
     * @param $platform
     * @return mixed
     */
    public function handleCallback(HandleCallback $request, $platform): mixed
    {
        return $this->handleCallback->handle($request, $platform);
    }
}
