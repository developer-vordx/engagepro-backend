<?php

namespace App\Contracts\Api\V1\CustomerBackOffice\Social;

interface HandleCallbackInterface
{
    public function handle($request, $platform);
}
