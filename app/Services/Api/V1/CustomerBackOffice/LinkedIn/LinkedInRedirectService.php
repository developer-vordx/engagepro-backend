<?php

namespace App\Services\Api\V1\CustomerBackOffice\LinkedIn;

use App\Contracts\Api\V1\CustomerBackOffice\LinkedIn\LinkedInRedirectInterface;
use Laravel\Socialite\Facades\Socialite;

class LinkedInRedirectService implements LinkedInRedirectInterface
{
    public function handle($request)
    {
        return Socialite::driver('linkedin-openid')->stateless()->redirect();
    }
}

