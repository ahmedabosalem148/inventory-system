<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by read status
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        // Pagination
        $perPage = $request->get('per_page', 20);
        $notifications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Get unread count for authenticated user
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    /**
     * Get recent notifications (for bell dropdown)
     */
    public function recent(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإشعار بنجاح',
            'data' => $notification->fresh(),
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->markAsUnread();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الإشعار بنجاح',
            'data' => $notification->fresh(),
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = Notification::where('user_id', $request->user()->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "تم تحديث {$updated} إشعار",
            'data' => [
                'updated_count' => $updated,
            ],
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإشعار بنجاح',
        ]);
    }

    /**
     * Delete all read notifications
     */
    public function clearRead(Request $request): JsonResponse
    {
        $deleted = Notification::where('user_id', $request->user()->id)
            ->read()
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "تم حذف {$deleted} إشعار",
            'data' => [
                'deleted_count' => $deleted,
            ],
        ]);
    }

    /**
     * Get notification types (for filters)
     */
    public function types(): JsonResponse
    {
        $types = [
            ['value' => Notification::TYPE_LOW_STOCK, 'label' => 'نقص المخزون'],
            ['value' => Notification::TYPE_PAYMENT_DUE, 'label' => 'دفعة مستحقة'],
            ['value' => Notification::TYPE_NEW_ORDER, 'label' => 'طلب جديد'],
            ['value' => Notification::TYPE_RETURN_VOUCHER, 'label' => 'إذن مرتجع'],
            ['value' => Notification::TYPE_STOCK_ADJUSTMENT, 'label' => 'تسوية مخزون'],
            ['value' => Notification::TYPE_USER_CREATED, 'label' => 'مستخدم جديد'],
            ['value' => Notification::TYPE_SYSTEM, 'label' => 'نظام'],
        ];

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }
}
