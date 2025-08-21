<?php

namespace App\Contracts\Api\V1\AdminBackOffice\Auth;

interface ForgotPasswordInterface
{
    public function handle($request);
}
