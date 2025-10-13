<?php

use App\Http\Controllers\Api\V1\CustomerBackOffice\Social\SocialController;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Illuminate\Support\Facades\Route;
use App\Helper;

Route::prefix('v1')->middleware(['request_logs'])->group(function () {

    // Public authentication routes
    Route::post('signup', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'signup']);
    Route::post('login', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'login']);
    
    // Password management
    Route::post('forgot-password', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\PasswordController::class, 'forgotPassword']);
    Route::post('set-password', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\PasswordController::class, 'setPassword']);
    Route::post('verify-email', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\PasswordController::class, 'verifyEmail']);

    // OAuth routes
    Route::get('google', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\GoogleAuthController::class, 'handleGoogleCallback']);

    // Protected customer routes
    Route::prefix('customerBackOffice')->middleware(['customer.auth'])->group(function () {
        
        // Auth routes
        Route::get('authenticate', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'getAuthUser']);
        Route::post('update-profile', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'updateProfile']);
        Route::post('logout', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'logout']);
        Route::post('update-password', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'updatePassword']);

        // Social media integration routes
        Route::prefix('social')->group(function () {
            Route::get('/{platform}/auth-url', [SocialController::class, 'getAuthUrl']);
            Route::post('/{platform}/callback', [SocialController::class, 'handleCallback']);
        });

        // Post management routes
        Route::prefix('posts')->group(function () {
            // Basic CRUD operations
            Route::get('/', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'destroy']);
            
            // Publishing routes
            Route::post('/{id}/publish', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'publish']);
            
            // Analytics routes
            Route::get('/{id}/analytics', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'analytics']);
            
            // Enhanced endpoints with subscription validation
            Route::middleware(['subscription.validate:post'])->group(function () {
                Route::post('/upload', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'upload']);
            });
            
            Route::middleware(['subscription.validate:post', 'social.security'])->group(function () {
                Route::post('/{id}/publish-social', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'publishToSocial']);
            });
            
            Route::middleware(['subscription.validate:analytics'])->group(function () {
                Route::get('/stats/overview', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'stats']);
            });
            
            // Subscription info endpoint
            Route::get('/subscription/info', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'subscriptionInfo']);
        });
    });

    // Catch-all route for 404
    Route::any('{any}', function () {
        return Helper::response('Requested api or method not found.', ResponseAlias::HTTP_NOT_FOUND);
    })->where('any', '.*');
});
