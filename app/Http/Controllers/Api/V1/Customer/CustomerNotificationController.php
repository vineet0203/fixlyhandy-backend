<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\CustomerNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerNotificationController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('customer');
        $customerId = $customer['id'];

        $notifications = CustomerNotification::where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $unread = CustomerNotification::where('customer_id', $customerId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success'       => true,
            'notifications' => $notifications,
            'unread_count'  => $unread,
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $customer = $request->attributes->get('customer');
        $notif = CustomerNotification::where('customer_id', $customer['id'])->find($id);
        if ($notif) $notif->markAsRead();
        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $customer = $request->attributes->get('customer');
        CustomerNotification::where('customer_id', $customer['id'])
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['success' => true]);
    }
}
