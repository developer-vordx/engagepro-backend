<?php

namespace App\Contracts\Api\V1\Auth;

interface ForgotPasswordInterface
{
    public function handle($request);
}
