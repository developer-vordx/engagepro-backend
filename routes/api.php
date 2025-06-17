<?php


use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Utils\HttpStatusCode;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['request_logs'])->group(function () {


    Route::get('google', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

    Route::get('twitter', [\App\Http\Controllers\Api\V1\Auth\TwitterController::class, 'redirectToTwitter']);
    Route::get('twitter/callback', [\App\Http\Controllers\Api\V1\Auth\TwitterController::class, 'handleTwitterCallback']);


    Route::get('facebook', [\App\Http\Controllers\Api\V1\Auth\FacebookController::class, 'redirectToFacebook']);
    Route::get('facebook/callback', [\App\Http\Controllers\Api\V1\Auth\FacebookController::class, 'handleFacebookCallback']);

    Route::post('signup', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'signup']);
    Route::post('login', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'login']);

    Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);


Route::middleware(['auth'])->group(function () {

    Route::post('/logout', [\App\Http\Controllers\Api\V1\Auth\AuthController::class, 'logout']);

    Route::prefix('user')->group(function () {

        Route::get('/', [\App\Http\Controllers\Api\V1\User\ProfileController::class, 'getProfile']);
        Route::post('/', [\App\Http\Controllers\Api\V1\User\ProfileController::class, 'updateProfile']);
        Route::post('change-password', [\App\Http\Controllers\Api\V1\User\ProfileController::class, 'updatePassword']);
        Route::post('/avatar', [\App\Http\Controllers\Api\V1\User\ProfileController::class, 'updateAvatar']);
        Route::post('change-email', [\App\Http\Controllers\Api\V1\User\ProfileController::class, 'updateEmail']);
    });

});



});



Route::any('{any}', function () {
    return  errors('API route not found', ['error' => 'API route not found'],HttpStatusCode::HTTP_NOT_FOUND );
})->where('any', '.*');
