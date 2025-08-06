<?php

namespace App\Contracts\Api\V1\CustomerBackOffice\Auth;

interface ForgotPasswordInterface
{
    public function handle($request);
}
