<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\Auth\Auth;
use App\Http\Controllers\Api\Auth\User;
use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;

class TwitterController extends Controller
{

    public function redirectToTwitter()
    {

        return Socialite::driver('x')->stateless()->redirect();
    }

    public function handleTwitterCallback()
    {
        $twitterUser = Socialite::driver('twitter')->stateless()->user();

        $user = User::firstOrCreate(
            ['twitter_id' => $twitterUser->id],
            ['name' => $twitterUser->name, 'email' => $twitterUser->email ?? null]
        );

        Auth::login($user);

        return redirect('/home');
    }
}
