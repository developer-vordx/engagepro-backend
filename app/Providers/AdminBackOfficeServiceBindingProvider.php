<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AdminBackOfficeServiceBindingProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(\App\Contracts\Api\V1\AdminBackOffice\Auth\LoginInterface::class, \App\Services\Api\V1\AdminBackoffice\Auth\LoginService::class);
        $this->app->bind(\App\Contracts\Api\V1\AdminBackOffice\Auth\SignUpInterface::class, \App\Services\Api\V1\AdminBackoffice\Auth\SignUpService::class);
        $this->app->bind(\App\Contracts\Api\V1\AdminBackOffice\Auth\ForgotPasswordInterface::class, \App\Services\Api\V1\AdminBackoffice\Auth\ForgotPasswordService::class);
        $this->app->bind(\App\Contracts\Api\V1\AdminBackOffice\Auth\PasswordResetInterface::class, \App\Services\Api\V1\AdminBackoffice\Auth\PasswordResetService::class);
        $this->app->bind(\App\Contracts\Api\V1\AdminBackOffice\User\ProfileInterface::class, \App\Services\Api\V1\AdminBackoffice\User\ProfileService::class);
        $this->app->bind(\App\Contracts\Api\V1\AdminBackOffice\User\UserProfileUpdateInterface::class, \App\Services\Api\V1\AdminBackoffice\User\UserProfileUpdateService::class);
        $this->app->bind(\App\Contracts\Api\V1\AdminBackOffice\User\UpdatePasswordInterface::class, \App\Services\Api\V1\AdminBackoffice\User\UpdatePasswordService::class);
        $this->app->bind(\App\Contracts\Api\V1\AdminBackOffice\User\UpdateEmailInterface::class, \App\Services\Api\V1\AdminBackoffice\User\UpdateEmailService::class);
        $this->app->bind(\App\Contracts\Api\V1\AdminBackOffice\User\UpdateEmailInterface::class, \App\Services\Api\V1\AdminBackoffice\User\UpdateEmailService::class);

    }

    public function boot(): void {}
}
