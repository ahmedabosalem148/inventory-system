<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send low stock notification
     */
    public function sendLowStockAlert(int $userId, array $product, int $currentStock, int $minStock): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => Notification::TYPE_LOW_STOCK,
            'title' => 'تنبيه: نقص في المخزون',
            'message' => "المنتج \"{$product['name']}\" وصل لمستوى منخفض ({$currentStock} / {$minStock})",
            'icon' => 'alert-triangle',
            'color' => 'orange',
            'data' => [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'current_stock' => $currentStock,
                'min_stock' => $minStock,
            ],
            'action_url' => '#products',
        ]);
    }

    /**
     * Send payment due notification
     */
    public function sendPaymentDueAlert(int $userId, array $customer, float $amount, string $dueDate): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => Notification::TYPE_PAYMENT_DUE,
            'title' => 'دفعة مستحقة',
            'message' => "العميل \"{$customer['name']}\" لديه دفعة مستحقة بقيمة {$amount} ج بتاريخ {$dueDate}",
            'icon' => 'dollar-sign',
            'color' => 'red',
            'data' => [
                'customer_id' => $customer['id'],
                'customer_name' => $customer['name'],
                'amount' => $amount,
                'due_date' => $dueDate,
            ],
            'action_url' => '#payments',
        ]);
    }

    /**
     * Send new order notification
     */
    public function sendNewOrderNotification(int $userId, array $voucher): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => Notification::TYPE_NEW_ORDER,
            'title' => 'إذن صرف جديد',
            'message' => "تم إنشاء إذن صرف جديد رقم {$voucher['id']} للعميل \"{$voucher['customer_name']}\"",
            'icon' => 'file-text',
            'color' => 'blue',
            'data' => [
                'voucher_id' => $voucher['id'],
                'customer_name' => $voucher['customer_name'],
                'total' => $voucher['total'] ?? 0,
            ],
            'action_url' => '#sales/' . $voucher['id'],
        ]);
    }

    /**
     * Send return voucher notification
     */
    public function sendReturnVoucherNotification(int $userId, array $voucher): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => Notification::TYPE_RETURN_VOUCHER,
            'title' => 'إذن مرتجع جديد',
            'message' => "تم إنشاء إذن مرتجع رقم {$voucher['id']} من العميل \"{$voucher['customer_name']}\"",
            'icon' => 'rotate-ccw',
            'color' => 'purple',
            'data' => [
                'voucher_id' => $voucher['id'],
                'customer_name' => $voucher['customer_name'],
                'total' => $voucher['total'] ?? 0,
            ],
            'action_url' => '#return-vouchers/' . $voucher['id'],
        ]);
    }

    /**
     * Send stock adjustment notification
     */
    public function sendStockAdjustmentNotification(int $userId, array $adjustment): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => Notification::TYPE_STOCK_ADJUSTMENT,
            'title' => 'تسوية مخزون',
            'message' => "تم تسوية المخزون للمنتج \"{$adjustment['product_name']}\" بمقدار {$adjustment['quantity']}",
            'icon' => 'edit',
            'color' => 'yellow',
            'data' => [
                'product_id' => $adjustment['product_id'],
                'product_name' => $adjustment['product_name'],
                'quantity' => $adjustment['quantity'],
                'reason' => $adjustment['reason'] ?? '',
            ],
            'action_url' => '#inventory',
        ]);
    }

    /**
     * Send user created notification
     */
    public function sendUserCreatedNotification(int $userId, string $userName, string $role): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => Notification::TYPE_USER_CREATED,
            'title' => 'مستخدم جديد',
            'message' => "تم إضافة مستخدم جديد: {$userName} ({$role})",
            'icon' => 'user-plus',
            'color' => 'green',
            'data' => [
                'user_name' => $userName,
                'role' => $role,
            ],
            'action_url' => '#users',
        ]);
    }

    /**
     * Send system notification
     */
    public function sendSystemNotification(int $userId, string $title, string $message, ?string $actionUrl = null): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => Notification::TYPE_SYSTEM,
            'title' => $title,
            'message' => $message,
            'icon' => 'info',
            'color' => 'blue',
            'data' => [],
            'action_url' => $actionUrl,
        ]);
    }

    /**
     * Send notification to multiple users
     */
    public function sendToMultipleUsers(array $userIds, string $type, string $title, string $message, array $data = [], ?string $actionUrl = null): Collection
    {
        $notifications = collect();

        foreach ($userIds as $userId) {
            $notification = Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'icon' => $this->getIconForType($type),
                'color' => $this->getColorForType($type),
                'data' => $data,
                'action_url' => $actionUrl,
            ]);

            $notifications->push($notification);
        }

        return $notifications;
    }

    /**
     * Send notification to all users with specific role
     */
    public function sendToRole(string $role, string $type, string $title, string $message, array $data = [], ?string $actionUrl = null): Collection
    {
        // Get users with this role (using whereHas to avoid guard issues)
        $users = User::whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        })->get();
        
        $userIds = $users->pluck('id')->toArray();

        if (empty($userIds)) {
            return collect(); // Return empty collection if no users found
        }

        return $this->sendToMultipleUsers($userIds, $type, $title, $message, $data, $actionUrl);
    }

    /**
     * Get icon for notification type
     */
    private function getIconForType(string $type): string
    {
        return match($type) {
            Notification::TYPE_LOW_STOCK => 'alert-triangle',
            Notification::TYPE_PAYMENT_DUE => 'dollar-sign',
            Notification::TYPE_NEW_ORDER => 'file-text',
            Notification::TYPE_RETURN_VOUCHER => 'rotate-ccw',
            Notification::TYPE_STOCK_ADJUSTMENT => 'edit',
            Notification::TYPE_USER_CREATED => 'user-plus',
            default => 'bell',
        };
    }

    /**
     * Get color for notification type
     */
    private function getColorForType(string $type): string
    {
        return match($type) {
            Notification::TYPE_LOW_STOCK => 'orange',
            Notification::TYPE_PAYMENT_DUE => 'red',
            Notification::TYPE_NEW_ORDER => 'blue',
            Notification::TYPE_RETURN_VOUCHER => 'purple',
            Notification::TYPE_STOCK_ADJUSTMENT => 'yellow',
            Notification::TYPE_USER_CREATED => 'green',
            default => 'blue',
        };
    }
}
