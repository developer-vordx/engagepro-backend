<?php

namespace App\Contracts\Api\V1\CustomerBackOffice\Social;

interface GetAuthUrlInterface
{
    public function handle($request,$platform);
}
