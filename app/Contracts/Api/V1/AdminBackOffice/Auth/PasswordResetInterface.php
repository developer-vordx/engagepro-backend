<?php

namespace App\Contracts\Api\V1\AdminBackOffice\Auth;

interface PasswordResetInterface
{
    public function handle($request);
}
