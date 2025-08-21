<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Social;

use App\Contracts\Api\V1\CustomerBackOffice\Social\GetAuthUrlInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    private GetAuthUrlInterface $getAuthUrl;

    public function __construct(GetAuthUrlInterface $getAuthUrl)
    {
        $this->getAuthUrl = $getAuthUrl;
    }

    public function getAuthUrl(Request $request, $platform)
    {
        return $this->getAuthUrl->handle($request, $platform);
    }
}
