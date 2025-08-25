<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CustomerBackOfficeServiceBindingProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\LoginInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\LoginService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\GetAuthUserInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\GetAuthUserService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\SignUpInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\SignUpService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\ForgotPasswordInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\ForgotPasswordService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\UpdatePasswordInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\UpdatePasswordService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\UpdateProfileInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\UpdateProfileService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\LogoutInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\LogoutService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\VerifyEmailInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\VerifyEmailService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\SetPasswordInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\SetPasswordService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Google\GoogleRedirectInterface::class, \App\Services\Api\V1\CustomerBackOffice\Google\GoogleRedirectService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Google\GoogleCallBackInterface::class, \App\Services\Api\V1\CustomerBackOffice\Google\GoogleCallBackService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Social\GetAuthUrlInterface::class, \App\Services\Api\V1\CustomerBackOffice\Social\GetAuthUrlService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Social\HandleCallbackInterface::class, \App\Services\Api\V1\CustomerBackOffice\Social\HandleCallbackService::class);

    }

    public function boot(): void
    {
    }
}
