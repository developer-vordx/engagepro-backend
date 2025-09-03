<?php


use App\Http\Controllers\Api\V1\CustomerBackOffice\Social\SocialController;
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

        Route::get('authenticate', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'getAuthUser']);

        Route::post('update-profile', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'updateProfile']);
        Route::post('logout', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'logout']);
        Route::post('update-password', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Auth\AuthController::class, 'updatePassword']);

        Route::prefix('social')->group(function () {
//        Route::get('/accounts', [SocialController::class, 'getLinkedAccounts']);

            Route::get('/{platform}/auth-url', [SocialController::class, 'getAuthUrl']);
//            ->middleware([CheckSubscriptionLimits::class . ':accounts']);
        Route::post('/{platform}/callback', [SocialController::class, 'handleCallback']);
//        Route::delete('/accounts/{accountId}', [SocialController::class, 'disconnectAccount']);
//        Route::post('/accounts/{accountId}/refresh', [SocialController::class, 'refreshToken']);
        });

        // Post management routes
        Route::prefix('posts')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'destroy']);
            Route::post('/{id}/publish', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'publish']);
            Route::get('/{id}/analytics', [\App\Http\Controllers\Api\V1\CustomerBackOffice\Post\PostController::class, 'analytics']);
        });
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


    Route::get('/update' , function (){
        $response = (new App\Library\SocialManager\TikTokService)->getUserVideos('act.fkyMNKY7TgbUGW14kW3B5GoEeMl80KnCs6g7mroLMToOmHoc2gj2FROISHXd!5879.va');
    dd($response);

    });

    // Test route for X (Twitter) posting
    Route::get('/test-x-post', function () {
        try {
            // This is a test route to demonstrate X (Twitter) posting
            // In production, this should be removed
            $xService = new \App\Library\SocialManager\XService();
            
            // Get posting limits
            $limits = $xService->getPostingLimits();
            
            // Get available scopes
            $scopes = $xService->getAvailableScopes();
            
            return response()->json([
                'message' => 'X (Twitter) service is working',
                'limits' => $limits,
                'scopes' => $scopes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Comprehensive test route for TikTok posting
    Route::get('/test-tiktok-full', function () {
        try {
            // This is a comprehensive test route for TikTok integration
            // In production, this should be removed
            
            // Create test data
            $customer = \App\Models\Customer::firstOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test Customer',
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                    'status' => 'active'
                ]
            );

            $socialAccount = \App\Models\SocialAccount::where('slug', 'tiktok')->first();
            if (!$socialAccount) {
                return response()->json(['error' => 'TikTok social account not found'], 404);
            }

            $customerAccount = \App\Models\CustomerAccount::firstOrCreate(
                [
                    'customer_id' => $customer->id,
                    'social_accounts_id' => $socialAccount->id,
                    'identifier' => 'test_tiktok_user_123'
                ],
                [
                    'username' => 'testuser',
                    'display_name' => 'Test User',
                    'access_token' => 'test_access_token_123',
                    'refresh_token' => 'test_refresh_token_123',
                    'is_active' => true,
                    'follower_count' => 100,
                    'following_count' => 50
                ]
            );

            $post = \App\Models\Post::create([
                'customer_id' => $customer->id,
                'title' => 'Test TikTok Video Post',
                'description' => 'This is a test video post for TikTok integration testing. #test #tiktok #integration',
                'tags' => ['test', 'tiktok', 'integration'],
                'target_platforms' => ['tiktok'],
                'status' => 'draft',
                'metadata' => ['test_post' => true]
            ]);

            // Create a test video file
            $testVideoContent = 'This is a dummy video file for testing purposes.';
            $storagePath = 'posts/' . $post->id . '/test_video.mp4';
            \Illuminate\Support\Facades\Storage::disk('private')->put($storagePath, $testVideoContent);
            
            $postFile = \App\Models\PostFile::create([
                'post_id' => $post->id,
                'file_path' => $storagePath,
                'file_type' => 'video',
                'file_size' => strlen($testVideoContent),
                'mime_type' => 'video/mp4',
                'status' => 'validated'
            ]);

            // Test TikTok service
            $tiktokService = new \App\Library\SocialManager\TikTokService();
            
            $limits = $tiktokService->getPostingLimits();
            $scopes = $tiktokService->getAvailableScopes();
            
            $validation = $tiktokService->validateContent(
                [storage_path('app/' . $storagePath)],
                ['title' => $post->title, 'description' => $post->description]
            );

            // Clean up test files
            \Illuminate\Support\Facades\Storage::disk('private')->delete($storagePath);
            
            return response()->json([
                'message' => 'TikTok integration test completed successfully',
                'test_data' => [
                    'customer_id' => $customer->id,
                    'post_id' => $post->id,
                    'customer_account_id' => $customerAccount->id
                ],
                'tiktok_service' => [
                    'limits' => $limits,
                    'scopes' => $scopes,
                    'content_validation' => $validation
                ],
                'status' => 'ready_for_real_integration'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });

    // Meta (Facebook & Instagram) testing routes
    Route::get('/test-meta-auth', function () {
        try {
            $metaService = new \App\Library\SocialManager\MetaService();
            
            return response()->json([
                'message' => 'Meta service is working',
                'platform' => $metaService->getPlatform(),
                'auth_url' => $metaService->getAuthorizationUrl()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::get('/test-meta-post', function () {
        try {
            $metaService = new \App\Library\SocialManager\MetaService();
            
            return response()->json([
                'message' => 'Meta posting service is ready',
                'platform' => $metaService->getPlatform(),
                'supported_media' => [
                    'facebook' => ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/avi', 'video/mov'],
                    'instagram' => ['image/jpeg', 'image/png', 'video/mp4', 'video/mov']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // YouTube testing routes
    Route::get('/test-youtube-auth', function () {
        try {
            $youtubeService = new \App\Library\SocialManager\YouTubeService();
            
            return response()->json([
                'message' => 'YouTube service is working',
                'platform' => $youtubeService->getPlatform(),
                'auth_url' => $youtubeService->getAuthorizationUrl(),
                'posting_limits' => $youtubeService->getPostingLimits(),
                'available_scopes' => $youtubeService->getAvailableScopes()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::get('/test-youtube-upload', function () {
        try {
            $youtubeService = new \App\Library\SocialManager\YouTubeService();
            
            return response()->json([
                'message' => 'YouTube upload service is ready',
                'platform' => $youtubeService->getPlatform(),
                'supported_video_formats' => [
                    'MP4', 'MOV', 'AVI', 'WMV', 'FLV', 'WebM', 'MKV'
                ],
                'upload_limits' => [
                    'max_video_size' => '128GB',
                    'max_video_duration' => '12 hours',
                    'daily_upload_limit' => '100 videos'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    });

    Route::any('{any}', function () {
        return Helper::response('Requested api or method not found.', ResponseAlias::HTTP_NOT_FOUND);
    })->where('any', '.*');
});



