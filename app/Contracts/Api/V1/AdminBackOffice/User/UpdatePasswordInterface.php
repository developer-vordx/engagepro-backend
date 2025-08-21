<?php

namespace App\Contracts\Api\V1\AdminBackOffice\User;

interface UpdatePasswordInterface
{
    public function handle($request);
}
