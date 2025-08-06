<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CustomerBackOfficeServiceBindingProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\LoginInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\LoginService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\SignUpInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\SignUpService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\PasswordResetInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\PasswordResetService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\ForgotPasswordInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\ForgotPasswordService::class);
        $this->app->bind(\App\Contracts\Api\V1\CustomerBackOffice\Auth\UpdatePasswordInterface::class, \App\Services\Api\V1\CustomerBackOffice\Auth\UpdatePasswordService::class);

    }

    public function boot(): void
    {
    }
}
