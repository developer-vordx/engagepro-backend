<?php

namespace App\Contracts\Api\V1\CustomerBackOffice\Notification;

interface MarkAsReadInterface
{
    public function handle($request, $notificationId);
}

