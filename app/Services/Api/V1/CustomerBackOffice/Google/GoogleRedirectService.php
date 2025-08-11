<?php

namespace App\Services\Api\V1\CustomerBackOffice\Google;

use App\Utils\BaseService;
use App\Contracts\Api\V1\CustomerBackOffice\Google\GoogleRedirectInterface;
use Laravel\Socialite\Facades\Socialite;

class GoogleRedirectService implements GoogleRedirectInterface
{
    public function handle($request)
    {
        return Socialite::driver('google')->stateless()->redirect();
    }
}
