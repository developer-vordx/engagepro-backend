<?php

namespace App\Services\Api\V1\CustomerBackOffice\TikTok;

use App\Contracts\Api\V1\CustomerBackOffice\TikTok\TikTokRedirectInterface;
use Laravel\Socialite\Facades\Socialite;

class TikTokRedirectService implements TikTokRedirectInterface
{
    public function handle($request)
    {
        return Socialite::driver('tiktok')->stateless()->redirect();
    }
}

