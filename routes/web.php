<?php

use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use App\Http\Controllers\Api\V1\Auth\TwitterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


// Route::get('/doc', function () {
//     return view('vendor/l5-swagger/index');
// });

Route::get('/twitter/redirect', [TwitterController::class, 'redirectToTwitter']);
Route::get('/twitter/callback', [TwitterController::class, 'handleTwitterCallback']);

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

// // In routes/web.php or routes/api.php
// Route::get('/docs', function () {
//     return response()->file(base_path('docs/swagger.yaml'));
// });
