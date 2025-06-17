<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ServiceBindingProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(\App\Contracts\Api\V1\Auth\LoginInterface::class, \App\Services\Api\V1\Auth\LoginService::class);
        $this->app->bind(\App\Contracts\Api\V1\Auth\SignUpInterface::class, \App\Services\Api\V1\Auth\SignUpService::class);
        $this->app->bind(\App\Contracts\Api\V1\Auth\ForgotPasswordInterface::class, \App\Services\Api\V1\Auth\ForgotPasswordService::class);
        $this->app->bind(\App\Contracts\Api\V1\Auth\PasswordResetInterface::class, \App\Services\Api\V1\Auth\PasswordResetService::class);
        $this->app->bind(\App\Contracts\Api\V1\User\ProfileInterface::class, \App\Services\Api\V1\User\ProfileService::class);
        $this->app->bind(\App\Contracts\Api\V1\User\UserProfileUpdateInterface::class, \App\Services\Api\V1\User\UserProfileUpdateService::class);
        $this->app->bind(\App\Contracts\Api\V1\User\UpdatePasswordInterface::class, \App\Services\Api\V1\User\UpdatePasswordService::class);
        $this->app->bind(\App\Contracts\Api\V1\User\UpdateEmailInterface::class, \App\Services\Api\V1\User\UpdateEmailService::class);

    }

    public function boot(): void {}
}
