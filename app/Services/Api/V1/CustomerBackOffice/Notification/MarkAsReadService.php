<?php

namespace App\Services\Api\V1\CustomerBackOffice\Notification;

use App\Contracts\Api\V1\CustomerBackOffice\Notification\MarkAsReadInterface;
use App\Utils\BaseService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Helper;

class MarkAsReadService extends BaseService implements MarkAsReadInterface
{
    public function handle($request, $notificationId)
    {
        try {
            $customer = Auth::guard('customer')->user();
            
            if ($notificationId === 'all') {
                $customer->unreadNotifications->markAsRead();
                return Helper::response('All notifications marked as read', ResponseAlias::HTTP_OK);
            }
            
            $notification = $customer->notifications()->where('id', $notificationId)->first();
            
            if (!$notification) {
                return Helper::response('Notification not found', ResponseAlias::HTTP_NOT_FOUND);
            }
            
            $notification->markAsRead();
            
            return Helper::response('Notification marked as read', ResponseAlias::HTTP_OK);
            
        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}

