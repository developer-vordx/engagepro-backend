<?php

namespace App\Contracts\Api\V1\CustomerBackOffice\Auth;

interface SetPasswordInterface
{
    public function handle($request);
}
