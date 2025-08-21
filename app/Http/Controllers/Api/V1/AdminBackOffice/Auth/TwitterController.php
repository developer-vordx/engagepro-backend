<?php

namespace App\Http\Controllers\Api\V1\AdminBackOffice\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class TwitterController extends Controller
{

    public function redirectToTwitter()
    {
        return Socialite::driver('twitter')->redirect();
    }

    public function handleTwitterCallback()
    {
        $twitterUser = Socialite::driver('twitter')->user();

        $user = User::firstOrCreate(
            ['twitter_id' => $twitterUser->id],
            ['name' => $twitterUser->name, 'email' => $twitterUser->email ?? null]
        );

        \Auth::login($user);

        return redirect('/home');
    }
}
