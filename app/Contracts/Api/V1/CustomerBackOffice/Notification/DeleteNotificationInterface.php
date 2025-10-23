<?php

namespace App\Contracts\Api\V1\CustomerBackOffice\Notification;

interface DeleteNotificationInterface
{
    public function handle($request, $notificationId);
}

