<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Social;

use App\Contracts\Api\V1\CustomerBackOffice\Social\GetPlatformsInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
    protected GetPlatformsInterface $getPlatforms;

    public function __construct(GetPlatformsInterface $getPlatforms)
    {
        $this->getPlatforms = $getPlatforms;
    }

    public function index(Request $request)
    {
        return $this->getPlatforms->handle($request);
    }
}

