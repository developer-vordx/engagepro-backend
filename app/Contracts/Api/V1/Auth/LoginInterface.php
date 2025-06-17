<?php

namespace App\Contracts\Api\V1\Auth;

interface LoginInterface
{
    public function handle($request);
}
