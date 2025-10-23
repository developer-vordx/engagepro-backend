<?php

namespace App\Services\Api\V1\CustomerBackOffice\X;

use App\Contracts\Api\V1\CustomerBackOffice\X\XRedirectInterface;
use Laravel\Socialite\Facades\Socialite;

class XRedirectService implements XRedirectInterface
{
    public function handle($request)
    {
        return Socialite::driver('twitter')->stateless()->redirect();
    }
}

