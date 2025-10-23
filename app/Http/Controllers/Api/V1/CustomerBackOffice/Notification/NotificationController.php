<?php

namespace App\Http\Controllers\Api\V1\CustomerBackOffice\Notification;

use App\Contracts\Api\V1\CustomerBackOffice\Notification\GetNotificationsInterface;
use App\Contracts\Api\V1\CustomerBackOffice\Notification\MarkAsReadInterface;
use App\Contracts\Api\V1\CustomerBackOffice\Notification\DeleteNotificationInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected GetNotificationsInterface $getNotifications;
    protected MarkAsReadInterface $markAsRead;
    protected DeleteNotificationInterface $deleteNotification;

    public function __construct(
        GetNotificationsInterface $getNotifications,
        MarkAsReadInterface $markAsRead,
        DeleteNotificationInterface $deleteNotification
    ) {
        $this->getNotifications = $getNotifications;
        $this->markAsRead = $markAsRead;
        $this->deleteNotification = $deleteNotification;
    }

    public function index(Request $request)
    {
        return $this->getNotifications->handle($request);
    }

    public function markAsRead(Request $request, $id)
    {
        return $this->markAsRead->handle($request, $id);
    }

    public function destroy(Request $request, $id)
    {
        return $this->deleteNotification->handle($request, $id);
    }
}

