<?php

namespace App\Services\Api\V1\CustomerBackOffice\Facebook;

use App\Contracts\Api\V1\CustomerBackOffice\Facebook\FacebookRedirectInterface;
use Laravel\Socialite\Facades\Socialite;

class FacebookRedirectService implements FacebookRedirectInterface
{
    public function handle($request)
    {
        return Socialite::driver('facebook')->stateless()->redirect();
    }
}

