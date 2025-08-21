<?php

namespace App\Http\Controllers\Api\V1\AdminBackOffice\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class FacebookController extends Controller
{
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->stateless()->redirect();
    }

    public function handleFacebookCallback()
    {
        $facebookUser = Socialite::driver('facebook')->stateless()->user();

        $user = User::firstOrCreate(
            ['email' => $facebookUser->getEmail()],
            [
                'name' => $facebookUser->getName(),
                'password' => bcrypt(str_random(16)),
                'facebook_id' => $facebookUser->getId(),
                'email_verified_at' => now(),
                'remember_token' => Str::random(16)
            ]
        );

        Auth::login($user);

        return redirect()->intended('/dashboard'); // or wherever you want to redirect
    }
}
