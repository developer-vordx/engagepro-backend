<?php

namespace App\Services\Api\V1\CustomerBackOffice\TikTok;

use App\Contracts\Api\V1\CustomerBackOffice\TikTok\TikTokRedirectInterface;
use App\Library\SocialManager\TikTokService;
use App\Helper;

class TikTokRedirectService implements TikTokRedirectInterface
{
    private TikTokService $tiktokService;

    public function __construct(TikTokService $tiktokService)
    {
        $this->tiktokService = $tiktokService;
    }

    public function handle($request)
    {
        try {
            // For login, use minimal scopes
            $authUrl = $this->tiktokService->getAuthorizationUrl(['user.info.basic']);
            return redirect($authUrl);
        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}

