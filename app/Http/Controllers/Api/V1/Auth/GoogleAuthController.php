<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(16),
                ]
            );

            // Generate a Sanctum token
//            $token = $user->createToken('google-token')->plainTextToken;

            return response()->json([
                'user' => $user,
//                'token' => $token
            ]);

        } catch (\Exception $e) {

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

