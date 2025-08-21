<?php

namespace App\Contracts\Api\V1\AdminBackOffice\Auth;

interface LoginInterface
{
    public function handle($request);
}
