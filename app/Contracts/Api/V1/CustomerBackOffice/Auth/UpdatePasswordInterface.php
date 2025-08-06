<?php

namespace App\Contracts\Api\V1\CustomerBackOffice\Auth;

interface UpdatePasswordInterface
{
    public function handle($request);
}
