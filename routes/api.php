<?php


use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Support\Facades\Route;
use App\Helper;

Route::prefix('v1')->middleware(['request_logs'])->group(function () {

    Route::get('twitter', [\App\Http\Controllers\Api\V1\AdminBackOffice\Auth\TwitterController::class, 'redirectToTwitter']);
    Route::get('twitter/callback', [\App\Http\Controllers\Api\V1\AdminBackOffice\Auth\TwitterController::class, 'handleTwitterCallback']);

    Route::get('facebook', [\App\Http\Controllers\Api\V1\AdminBackOffice\Auth\FacebookController::class, 'redirectToFacebook']);
    Route::get('facebook/callback', [\App\Http\Controllers\Api\V1\AdminBackOffice\Auth\FacebookController::class, 'handleFacebookCallback']);




    Route::post('signup', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'signup']);
    Route::post('login', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'login']);

    Route::get('google', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\GoogleAuthController::class, 'handleGoogleCallback']);

    Route::post('forgot-password', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\PasswordController::class, 'forgotPassword']);
    Route::post('set-password', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\PasswordController::class, 'setPassword']);
    Route::post('verify-email', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\PasswordController::class, 'verifyEmail']);

    Route::middleware(['customer.auth'])->group(function () {
        Route::post('update-profile', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'updateProfile']);
        Route::post('logout', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'logout']);
        Route::post('update-password', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'updatePassword']);
    });




















//    Route::prefix('adminBackOffice')->group(function () {
//
//        Route::post('signup', [\App\Http\Controllers\Api\V1\AdminBackOffice\Auth\AuthController::class, 'signup']);
//        Route::post('login', [\App\Http\Controllers\Api\V1\AdminBackOffice\Auth\AuthController::class, 'login']);
//
//        Route::post('forgot-password', [\App\Http\Controllers\Api\V1\AdminBackOffice\Auth\PasswordResetController::class, 'forgotPassword']);
//        Route::post('reset-password', [\App\Http\Controllers\Api\V1\AdminBackOffice\Auth\PasswordResetController::class, 'resetPassword']);
//
//        Route::middleware(['user.auth'])->group(function () {
//
//            Route::post('/logout', [\App\Http\Controllers\Api\V1\AdminBackOffice\Auth\AuthController::class, 'logout']);
//
//            Route::prefix('user')->group(function () {
//
//                Route::get('/', [\App\Http\Controllers\Api\V1\AdminBackOffice\User\ProfileController::class, 'getProfile']);
//                Route::post('/', [\App\Http\Controllers\Api\V1\AdminBackOffice\User\ProfileController::class, 'updateProfile']);
//                Route::post('change-password', [\App\Http\Controllers\Api\V1\AdminBackOffice\User\ProfileController::class, 'updatePassword']);
//                Route::post('/avatar', [\App\Http\Controllers\Api\V1\AdminBackOffice\User\ProfileController::class, 'updateAvatar']);
//                Route::post('change-email', [\App\Http\Controllers\Api\V1\AdminBackOffice\User\ProfileController::class, 'updateEmail']);
//            });
//
//        });
//    });


});


Route::any('{any}', function () {
    return Helper::response('Not found', 'Requested api or method not found.', ResponseAlias::HTTP_NOT_FOUND);
})->where('any', '.*');
