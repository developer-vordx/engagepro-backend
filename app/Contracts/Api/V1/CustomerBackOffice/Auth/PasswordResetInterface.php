<?php

namespace App\Contracts\Api\V1\CustomerBackOffice\Auth;

interface PasswordResetInterface
{
    public function handle($request);
}
