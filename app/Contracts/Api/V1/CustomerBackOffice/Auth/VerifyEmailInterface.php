<?php

namespace App\Contracts\Api\V1\CustomerBackOffice\Auth;

interface VerifyEmailInterface
{
    public function handle($request);
}
