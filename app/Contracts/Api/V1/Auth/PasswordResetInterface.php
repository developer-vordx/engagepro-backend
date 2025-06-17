<?php

namespace App\Contracts\Api\V1\Auth;

interface PasswordResetInterface
{
    public function handle($request);
}
