<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CustomerBackOfficeServiceBindingProvider extends ServiceProvider
{
    public function register()
    {
        // Auth Services
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\LoginInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\LoginService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\GetAuthUserInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\GetAuthUserService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\SignUpInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\SignUpService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\ForgotPasswordInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\ForgotPasswordService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\UpdatePasswordInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\UpdatePasswordService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\UpdateProfileInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\UpdateProfileService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\LogoutInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\LogoutService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\VerifyEmailInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\VerifyEmailService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\SetPasswordInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\SetPasswordService::class);
        
        // OAuth Services - Google
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Google\GoogleRedirectInterface::class, \App\Services\Api\V1\CustomerBackOffice\Google\GoogleRedirectService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Google\GoogleCallBackInterface::class, \App\Services\Api\V1\CustomerBackOffice\Google\GoogleCallBackService::class);
        
        // OAuth Services - Facebook
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Facebook\FacebookRedirectInterface::class, \App\Services\Api\V1\CustomerBackOffice\Facebook\FacebookRedirectService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Facebook\FacebookCallBackInterface::class, \App\Services\Api\V1\CustomerBackOffice\Facebook\FacebookCallBackService::class);
        
        // OAuth Services - X (Twitter)
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\X\XRedirectInterface::class, \App\Services\Api\V1\CustomerBackOffice\X\XRedirectService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\X\XCallBackInterface::class, \App\Services\Api\V1\CustomerBackOffice\X\XCallBackService::class);
        
        // OAuth Services - LinkedIn
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\LinkedIn\LinkedInRedirectInterface::class, \App\Services\Api\V1\CustomerBackOffice\LinkedIn\LinkedInRedirectService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\LinkedIn\LinkedInCallBackInterface::class, \App\Services\Api\V1\CustomerBackOffice\LinkedIn\LinkedInCallBackService::class);
        
        // OAuth Services - TikTok
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\TikTok\TikTokRedirectInterface::class, \App\Services\Api\V1\CustomerBackOffice\TikTok\TikTokRedirectService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\TikTok\TikTokCallBackInterface::class, \App\Services\Api\V1\CustomerBackOffice\TikTok\TikTokCallBackService::class);
        
        // Social Media Services
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Social\GetAuthUrlInterface::class, \App\Services\Api\V1\CustomerBackOffice\Social\GetAuthUrlService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Social\HandleCallbackInterface::class, \App\Services\Api\V1\CustomerBackOffice\Social\HandleCallbackService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Social\GetPlatformsInterface::class, \App\Services\Api\V1\CustomerBackOffice\Social\GetPlatformsService::class);
        
        // Notification Services
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Notification\GetNotificationsInterface::class, \App\Services\Api\V1\CustomerBackOffice\Notification\GetNotificationsService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Notification\MarkAsReadInterface::class, \App\Services\Api\V1\CustomerBackOffice\Notification\MarkAsReadService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Notification\DeleteNotificationInterface::class, \App\Services\Api\V1\CustomerBackOffice\Notification\DeleteNotificationService::class);
        
        // Plan Services
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Plan\GetPlansInterface::class, \App\Services\Api\V1\CustomerBackOffice\Plan\GetPlansService::class);
        
        // Analytics Services
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Analytics\GetOverviewInterface::class, \App\Services\Api\V1\CustomerBackOffice\Analytics\GetOverviewService::class);

    }

    public function boot(): void
    {
    }
}
