<?php

namespace App\Contracts\Api\V1\User;

interface UpdatePasswordInterface
{
    public function handle($request);
}
