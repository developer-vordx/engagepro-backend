<?php

namespace App\Services\Api\V1\CustomerBackOffice\Notification;

use App\Contracts\Api\V1\CustomerBackOffice\Notification\GetNotificationsInterface;
use App\Utils\BaseService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Helper;

class GetNotificationsService extends BaseService implements GetNotificationsInterface
{
    public function handle($request)
    {
        try {
            $customer = Auth::guard('customer')->user();
            
            $perPage = $request->per_page ?? 20;
            $unreadOnly = $request->unread_only ?? false;
            
            $query = $customer->notifications();
            
            if ($unreadOnly) {
                $query->whereNull('read_at');
            }
            
            $notifications = $query->latest()->paginate($perPage);
            
            $data = [
                'notifications' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
                'unread_count' => $customer->unreadNotifications()->count(),
            ];
            
            return Helper::response($data, ResponseAlias::HTTP_OK);
            
        } catch (\Exception $e) {
            return Helper::errors($e);
        }
    }
}

