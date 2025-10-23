<?php

namespace App\Services\Api\V1\CustomerBackOffice\Notification;

use App\Contracts\Api\V1\CustomerBackOffice\Notification\DeleteNotificationInterface;
use App\Utils\BaseService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Helper;

class DeleteNotificationService extends BaseService implements DeleteNotificationInterface
{
    public function handle($request, $notificationId)
    {
        try {
            $customer = Auth::guard('customer')->user();
            
            if ($notificationId === 'all') {
                $customer->notifications()->delete();
                return Helper::response('All notifications deleted', ResponseAlias::HTTP_OK);
            }
            
            $notification = $customer->notifications()->where('id', $notificationId)->first();
            
            if (!$notification) {
                return Helper::response('Notification not found', ResponseAlias::HTTP_NOT_FOUND);
            }
            
            $notification->delete();
            
            return Helper::response('Notification deleted', ResponseAlias::HTTP_OK);
            
        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}

