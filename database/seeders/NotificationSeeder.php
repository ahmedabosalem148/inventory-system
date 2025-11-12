<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user
        $admin = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->first();

        if (!$admin) {
            $this->command->warn('No admin user found. Creating notifications for first user...');
            $admin = User::first();
        }

        if (!$admin) {
            $this->command->error('No users found in the database!');
            return;
        }

        $this->command->info("Creating notifications for user: {$admin->name}");

        // Create various types of notifications
        $notifications = [
            // Low Stock Alert
            [
                'user_id' => $admin->id,
                'type' => Notification::TYPE_LOW_STOCK,
                'title' => 'تنبيه مخزون منخفض',
                'message' => 'منتج "لابتوب ديل" وصل لأقل من الحد الأدنى للمخزون (5 وحدات متبقية)',
                'icon' => 'package',
                'color' => 'red',
                'data' => json_encode([
                    'product_id' => 1,
                    'product_name' => 'لابتوب ديل',
                    'current_stock' => 5,
                    'min_stock' => 10,
                ]),
                'action_url' => '#products',
                'is_read' => false,
                'created_at' => Carbon::now()->subMinutes(15),
                'updated_at' => Carbon::now()->subMinutes(15),
            ],
            
            // Payment Due Alert
            [
                'user_id' => $admin->id,
                'type' => Notification::TYPE_PAYMENT_DUE,
                'title' => 'تذكير بدفعة مستحقة',
                'message' => 'العميل "محمد أحمد" لديه دفعة مستحقة بقيمة 5,000 ريال',
                'icon' => 'dollar-sign',
                'color' => 'yellow',
                'data' => json_encode([
                    'customer_id' => 1,
                    'customer_name' => 'محمد أحمد',
                    'amount' => 5000,
                    'due_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
                ]),
                'action_url' => '#customers',
                'is_read' => false,
                'created_at' => Carbon::now()->subHour(),
                'updated_at' => Carbon::now()->subHour(),
            ],

            // New Order
            [
                'user_id' => $admin->id,
                'type' => Notification::TYPE_NEW_ORDER,
                'title' => 'طلب جديد',
                'message' => 'تم إنشاء فاتورة مبيعات جديدة برقم #INV-0123',
                'icon' => 'shopping-cart',
                'color' => 'green',
                'data' => json_encode([
                    'voucher_id' => 123,
                    'voucher_number' => 'INV-0123',
                    'total_amount' => 15000,
                ]),
                'action_url' => '#sales/123',
                'is_read' => true,
                'read_at' => Carbon::now()->subMinutes(30),
                'created_at' => Carbon::now()->subHours(2),
                'updated_at' => Carbon::now()->subMinutes(30),
            ],

            // Return Voucher
            [
                'user_id' => $admin->id,
                'type' => Notification::TYPE_RETURN_VOUCHER,
                'title' => 'فاتورة مرتجعات جديدة',
                'message' => 'تم إنشاء فاتورة مرتجعات برقم #RET-0045',
                'icon' => 'rotate-ccw',
                'color' => 'orange',
                'data' => json_encode([
                    'voucher_id' => 45,
                    'voucher_number' => 'RET-0045',
                    'total_amount' => 3500,
                ]),
                'action_url' => '#returns/45',
                'is_read' => false,
                'created_at' => Carbon::now()->subHours(3),
                'updated_at' => Carbon::now()->subHours(3),
            ],

            // Stock Adjustment
            [
                'user_id' => $admin->id,
                'type' => Notification::TYPE_STOCK_ADJUSTMENT,
                'title' => 'تعديل مخزون',
                'message' => 'تم تعديل مخزون منتج "كيبورد لوجيتك" من 50 إلى 48 وحدة',
                'icon' => 'refresh-cw',
                'color' => 'blue',
                'data' => json_encode([
                    'product_id' => 5,
                    'product_name' => 'كيبورد لوجيتك',
                    'old_quantity' => 50,
                    'new_quantity' => 48,
                    'reason' => 'تالف',
                ]),
                'action_url' => '#products',
                'is_read' => true,
                'read_at' => Carbon::now()->subHours(1),
                'created_at' => Carbon::now()->subHours(4),
                'updated_at' => Carbon::now()->subHours(1),
            ],

            // User Created
            [
                'user_id' => $admin->id,
                'type' => Notification::TYPE_USER_CREATED,
                'title' => 'مستخدم جديد',
                'message' => 'تم إضافة مستخدم جديد "علي محمود" بصلاحية موظف مبيعات',
                'icon' => 'user-plus',
                'color' => 'purple',
                'data' => json_encode([
                    'new_user_id' => 10,
                    'new_user_name' => 'علي محمود',
                    'role' => 'موظف مبيعات',
                ]),
                'action_url' => '#users',
                'is_read' => true,
                'read_at' => Carbon::now()->subHours(2),
                'created_at' => Carbon::now()->subHours(5),
                'updated_at' => Carbon::now()->subHours(2),
            ],

            // System Notification
            [
                'user_id' => $admin->id,
                'type' => Notification::TYPE_SYSTEM,
                'title' => 'تحديث النظام',
                'message' => 'تم تحديث نظام الإشعارات بنجاح! الآن يمكنك متابعة جميع الأحداث المهمة',
                'icon' => 'bell',
                'color' => 'blue',
                'data' => json_encode([
                    'version' => '1.2.0',
                    'features' => ['إشعارات فورية', 'تذكيرات الدفعات', 'تنبيهات المخزون'],
                ]),
                'action_url' => '#notifications',
                'is_read' => false,
                'created_at' => Carbon::now()->subMinutes(5),
                'updated_at' => Carbon::now()->subMinutes(5),
            ],

            // Another Low Stock (older, read)
            [
                'user_id' => $admin->id,
                'type' => Notification::TYPE_LOW_STOCK,
                'title' => 'تنبيه مخزون منخفض',
                'message' => 'منتج "ماوس لاسلكي" وصل لأقل من الحد الأدنى للمخزون',
                'icon' => 'package',
                'color' => 'red',
                'data' => json_encode([
                    'product_id' => 8,
                    'product_name' => 'ماوس لاسلكي',
                    'current_stock' => 3,
                    'min_stock' => 15,
                ]),
                'action_url' => '#products',
                'is_read' => true,
                'read_at' => Carbon::now()->subDays(1),
                'created_at' => Carbon::now()->subDays(2),
                'updated_at' => Carbon::now()->subDays(1),
            ],
        ];

        // Insert notifications
        foreach ($notifications as $notification) {
            Notification::create($notification);
        }

        $this->command->info('✓ Created ' . count($notifications) . ' demo notifications');
        $unreadCount = collect($notifications)->where('is_read', false)->count();
        $this->command->info("  • {$unreadCount} unread notifications");
        $this->command->info("  • " . (count($notifications) - $unreadCount) . " read notifications");
    }
}

