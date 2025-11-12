<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class RealDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating real users and notifications...');

        // Create realistic users
        $users = $this->createUsers();
        
        // Create realistic notifications for each user
        $this->createNotifications($users);
        
        $this->command->info('Real data seeded successfully!');
    }

    /**
     * Create realistic users
     */
    private function createUsers(): array
    {
        $users = [];

        // 1. Admin Users
        $users[] = User::firstOrCreate(
            ['email' => 'admin@inventory.sa'],
            [
                'name' => 'أحمد محمد السعيد',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'phone' => '0501234567',
                'created_at' => Carbon::now()->subMonths(6),
            ]
        );

        $users[] = User::firstOrCreate(
            ['email' => 'manager@inventory.sa'],
            [
                'name' => 'فاطمة عبدالله العتيبي',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'phone' => '0509876543',
                'created_at' => Carbon::now()->subMonths(5),
            ]
        );

        // 2. Warehouse Managers
        $users[] = User::firstOrCreate(
            ['email' => 'warehouse1@inventory.sa'],
            [
                'name' => 'خالد أحمد الغامدي',
                'password' => Hash::make('password'),
                'role' => 'warehouse_manager',
                'is_active' => true,
                'phone' => '0551234567',
                'created_at' => Carbon::now()->subMonths(4),
            ]
        );

        $users[] = User::firstOrCreate(
            ['email' => 'warehouse2@inventory.sa'],
            [
                'name' => 'نورة سعد المطيري',
                'password' => Hash::make('password'),
                'role' => 'warehouse_manager',
                'is_active' => true,
                'phone' => '0559876543',
                'created_at' => Carbon::now()->subMonths(3),
            ]
        );

        // 3. Sales Staff
        $users[] = User::firstOrCreate(
            ['email' => 'sales1@inventory.sa'],
            [
                'name' => 'محمد عبدالرحمن القحطاني',
                'password' => Hash::make('password'),
                'role' => 'sales',
                'is_active' => true,
                'phone' => '0561234567',
                'created_at' => Carbon::now()->subMonths(2),
            ]
        );

        $users[] = User::firstOrCreate(
            ['email' => 'sales2@inventory.sa'],
            [
                'name' => 'سارة محمد الشمري',
                'password' => Hash::make('password'),
                'role' => 'sales',
                'is_active' => true,
                'phone' => '0569876543',
                'created_at' => Carbon::now()->subMonths(2),
            ]
        );

        $users[] = User::firstOrCreate(
            ['email' => 'sales3@inventory.sa'],
            [
                'name' => 'عبدالله فهد الدوسري',
                'password' => Hash::make('password'),
                'role' => 'sales',
                'is_active' => true,
                'phone' => '0571234567',
                'created_at' => Carbon::now()->subMonth(),
            ]
        );

        // 4. Cashiers
        $users[] = User::firstOrCreate(
            ['email' => 'cashier1@inventory.sa'],
            [
                'name' => 'ريم خالد العنزي',
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'is_active' => true,
                'phone' => '0581234567',
                'created_at' => Carbon::now()->subMonth(),
            ]
        );

        $users[] = User::firstOrCreate(
            ['email' => 'cashier2@inventory.sa'],
            [
                'name' => 'هند سلطان الحربي',
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'is_active' => true,
                'phone' => '0589876543',
                'created_at' => Carbon::now()->subWeeks(3),
            ]
        );

        // 5. Accountants
        $users[] = User::firstOrCreate(
            ['email' => 'accountant1@inventory.sa'],
            [
                'name' => 'يوسف علي الزهراني',
                'password' => Hash::make('password'),
                'role' => 'accountant',
                'is_active' => true,
                'phone' => '0531234567',
                'created_at' => Carbon::now()->subMonths(3),
            ]
        );

        $users[] = User::firstOrCreate(
            ['email' => 'accountant2@inventory.sa'],
            [
                'name' => 'مريم فيصل الشهري',
                'password' => Hash::make('password'),
                'role' => 'accountant',
                'is_active' => true,
                'phone' => '0539876543',
                'created_at' => Carbon::now()->subMonths(2),
            ]
        );

        // 6. Inventory Staff
        $users[] = User::firstOrCreate(
            ['email' => 'inventory1@inventory.sa'],
            [
                'name' => 'عمر حسن الجهني',
                'password' => Hash::make('password'),
                'role' => 'inventory_staff',
                'is_active' => true,
                'phone' => '0541234567',
                'created_at' => Carbon::now()->subWeeks(6),
            ]
        );

        $users[] = User::firstOrCreate(
            ['email' => 'inventory2@inventory.sa'],
            [
                'name' => 'لطيفة ناصر البقمي',
                'password' => Hash::make('password'),
                'role' => 'inventory_staff',
                'is_active' => true,
                'phone' => '0549876543',
                'created_at' => Carbon::now()->subWeeks(4),
            ]
        );

        // 7. Recently Added Staff
        $users[] = User::firstOrCreate(
            ['email' => 'staff1@inventory.sa'],
            [
                'name' => 'تركي سعود العسيري',
                'password' => Hash::make('password'),
                'role' => 'sales',
                'is_active' => true,
                'phone' => '0521234567',
                'created_at' => Carbon::now()->subWeeks(2),
            ]
        );

        $users[] = User::firstOrCreate(
            ['email' => 'staff2@inventory.sa'],
            [
                'name' => 'منى إبراهيم الرشيدي',
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'is_active' => true,
                'phone' => '0529876543',
                'created_at' => Carbon::now()->subWeek(),
            ]
        );

        // 8. Inactive User (for testing)
        $users[] = User::firstOrCreate(
            ['email' => 'inactive@inventory.sa'],
            [
                'name' => 'علي محمد الخالدي',
                'password' => Hash::make('password'),
                'role' => 'sales',
                'is_active' => false,
                'phone' => '0591234567',
                'created_at' => Carbon::now()->subMonths(4),
            ]
        );

        $this->command->info('Created ' . count($users) . ' users');

        return $users;
    }

    /**
     * Create realistic notifications
     */
    private function createNotifications(array $users): void
    {
        $notificationCount = 0;

        // Notification templates
        $templates = [
            // Low Stock Notifications
            [
                'type' => 'low_stock',
                'templates' => [
                    ['title' => 'تنبيه مخزون منخفض', 'message' => 'المنتج "لابتوب ديل XPS 13" وصل إلى الحد الأدنى (5 وحدات متبقية)', 'action_url' => '/products/1'],
                    ['title' => 'نفاد وشيك', 'message' => 'المنتج "شاشة سامسونج 27 بوصة" أوشك على النفاد (3 وحدات فقط)', 'action_url' => '/products/2'],
                    ['title' => 'تحذير مخزون', 'message' => 'لوحة مفاتيح لوجيتك - الكمية المتبقية 8 وحدات (الحد الأدنى: 10)', 'action_url' => '/products/3'],
                    ['title' => 'إعادة طلب مطلوبة', 'message' => 'ماوس لاسلكي - المخزون منخفض جداً (4 وحدات)', 'action_url' => '/products/4'],
                    ['title' => 'مخزون حرج', 'message' => 'كابل HDMI 2 متر - متبقي 6 وحدات فقط', 'action_url' => '/products/5'],
                ]
            ],
            // Payment Due Notifications
            [
                'type' => 'payment_due',
                'templates' => [
                    ['title' => 'فاتورة مستحقة قريباً', 'message' => 'الفاتورة #INV-2024-1156 بمبلغ 15,750 ريال مستحقة خلال يومين', 'action_url' => '/invoices/1156'],
                    ['title' => 'تذكير دفع', 'message' => 'العميل "شركة التقنية المتقدمة" - فاتورة #INV-2024-1189 (24,500 ريال) تستحق غداً', 'action_url' => '/invoices/1189'],
                    ['title' => 'دفعة متأخرة', 'message' => 'الفاتورة #INV-2024-1098 متأخرة بـ 3 أيام - المبلغ: 8,900 ريال', 'action_url' => '/invoices/1098'],
                    ['title' => 'استحقاق اليوم', 'message' => 'فاتورة "مؤسسة النجاح" #INV-2024-1203 تستحق اليوم (12,300 ريال)', 'action_url' => '/invoices/1203'],
                    ['title' => 'تنبيه متأخرات', 'message' => 'إجمالي المتأخرات: 45,600 ريال من 5 فواتير', 'action_url' => '/reports/outstanding'],
                ]
            ],
            // New Order Notifications
            [
                'type' => 'new_order',
                'templates' => [
                    ['title' => 'طلب جديد', 'message' => 'طلب #ORD-2024-3421 من العميل "شركة الابتكار" - القيمة: 28,750 ريال', 'action_url' => '/orders/3421'],
                    ['title' => 'طلب عاجل', 'message' => 'طلب كبير #ORD-2024-3445 - 150 قطعة، يتطلب تجهيز سريع', 'action_url' => '/orders/3445'],
                    ['title' => 'طلب بالجملة', 'message' => 'طلب جملة من "متجر الإلكترونيات" - 200 وحدة بقيمة 65,000 ريال', 'action_url' => '/orders/3458'],
                    ['title' => 'طلب متكرر', 'message' => 'العميل "مكتبة المدينة" أعاد الطلب #ORD-2024-3467 (15,200 ريال)', 'action_url' => '/orders/3467'],
                    ['title' => 'طلب جديد', 'message' => 'طلب #ORD-2024-3489 من عميل جديد - يحتاج مراجعة', 'action_url' => '/orders/3489'],
                ]
            ],
            // Return Notifications
            [
                'type' => 'order_return',
                'templates' => [
                    ['title' => 'مرتجع جديد', 'message' => 'مرتجع #RET-2024-145 - 3 أجهزة لابتوب بسبب عيب تصنيع', 'action_url' => '/returns/145'],
                    ['title' => 'طلب استبدال', 'message' => 'العميل يطلب استبدال شاشة معيبة - مرتجع #RET-2024-156', 'action_url' => '/returns/156'],
                    ['title' => 'مرتجع معتمد', 'message' => 'تم اعتماد مرتجع #RET-2024-167 - استرجاع مبلغ 4,500 ريال', 'action_url' => '/returns/167'],
                    ['title' => 'مرتجع قيد المراجعة', 'message' => 'مرتجع #RET-2024-178 يحتاج موافقة المدير', 'action_url' => '/returns/178'],
                    ['title' => 'مرتجع عاجل', 'message' => 'مرتجع كمية كبيرة #RET-2024-189 - 25 وحدة', 'action_url' => '/returns/189'],
                ]
            ],
            // New User Notifications (for admins only)
            [
                'type' => 'new_user',
                'templates' => [
                    ['title' => 'موظف جديد', 'message' => 'تم إضافة الموظف "تركي سعود العسيري" - قسم المبيعات', 'action_url' => '/users'],
                    ['title' => 'حساب جديد', 'message' => 'الموظفة "منى إبراهيم الرشيدي" انضمت كمحاسبة', 'action_url' => '/users'],
                    ['title' => 'مستخدم جديد', 'message' => 'تم تفعيل حساب "هند سلطان الحربي" - الصلاحية: أمين صندوق', 'action_url' => '/users'],
                ]
            ],
            // System Notifications
            [
                'type' => 'general',
                'templates' => [
                    ['title' => 'نسخ احتياطي تلقائي', 'message' => 'تم إنشاء نسخة احتياطية للنظام بنجاح - 2.4 جيجابايت', 'action_url' => '/settings/backup'],
                    ['title' => 'تحديث النظام', 'message' => 'يتوفر تحديث جديد للنظام (الإصدار 2.5.0) - يرجى التحديث قريباً', 'action_url' => '/settings/updates'],
                    ['title' => 'تقرير شهري', 'message' => 'تقرير المبيعات الشهري جاهز - إجمالي المبيعات: 485,600 ريال', 'action_url' => '/reports/monthly'],
                    ['title' => 'صيانة مجدولة', 'message' => 'صيانة النظام مجدولة يوم الجمعة من 12-2 صباحاً', 'action_url' => '/announcements'],
                    ['title' => 'إنجاز هدف', 'message' => 'تهانينا! تم تحقيق هدف المبيعات الشهري (500,000 ريال)', 'action_url' => '/dashboard'],
                ]
            ],
        ];

        // Create notifications for each user based on their role
        foreach ($users as $user) {
            $userNotifications = $this->getNotificationsForRole($user, $templates);
            
            foreach ($userNotifications as $notification) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => $notification['type'],
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'action_url' => $notification['action_url'],
                    'is_read' => $notification['is_read'],
                    'created_at' => $notification['created_at'],
                ]);
                $notificationCount++;
            }
        }

        $this->command->info('Created ' . $notificationCount . ' notifications');
    }

    /**
     * Get appropriate notifications for user role
     */
    private function getNotificationsForRole(User $user, array $templates): array
    {
        $notifications = [];
        $now = Carbon::now();

        // Different notification sets based on role
        switch ($user->role) {
            case 'admin':
                // Admins get all types of notifications
                $notifications = array_merge(
                    $this->generateFromTemplate($templates[0], 5, $now), // Low stock
                    $this->generateFromTemplate($templates[1], 5, $now), // Payment due
                    $this->generateFromTemplate($templates[2], 5, $now), // New orders
                    $this->generateFromTemplate($templates[3], 4, $now), // Returns
                    $this->generateFromTemplate($templates[4], 3, $now), // New users
                    $this->generateFromTemplate($templates[5], 5, $now)  // General
                );
                break;

            case 'warehouse_manager':
                // Warehouse managers focus on inventory and orders
                $notifications = array_merge(
                    $this->generateFromTemplate($templates[0], 5, $now), // Low stock
                    $this->generateFromTemplate($templates[2], 4, $now), // New orders
                    $this->generateFromTemplate($templates[3], 3, $now), // Returns
                    $this->generateFromTemplate($templates[5], 2, $now)  // General
                );
                break;

            case 'sales':
                // Sales staff get orders and customer-related notifications
                $notifications = array_merge(
                    $this->generateFromTemplate($templates[2], 4, $now), // New orders
                    $this->generateFromTemplate($templates[3], 2, $now), // Returns
                    $this->generateFromTemplate($templates[5], 2, $now)  // General
                );
                break;

            case 'accountant':
                // Accountants focus on payments
                $notifications = array_merge(
                    $this->generateFromTemplate($templates[1], 5, $now), // Payment due
                    $this->generateFromTemplate($templates[2], 2, $now), // New orders
                    $this->generateFromTemplate($templates[5], 3, $now)  // General
                );
                break;

            case 'cashier':
                // Cashiers get payment and order notifications
                $notifications = array_merge(
                    $this->generateFromTemplate($templates[1], 3, $now), // Payment due
                    $this->generateFromTemplate($templates[2], 3, $now), // New orders
                    $this->generateFromTemplate($templates[5], 2, $now)  // General
                );
                break;

            case 'inventory_staff':
                // Inventory staff focus on stock levels
                $notifications = array_merge(
                    $this->generateFromTemplate($templates[0], 5, $now), // Low stock
                    $this->generateFromTemplate($templates[3], 2, $now), // Returns
                    $this->generateFromTemplate($templates[5], 2, $now)  // General
                );
                break;

            default:
                // Default users get general notifications
                $notifications = $this->generateFromTemplate($templates[5], 3, $now);
                break;
        }

        return $notifications;
    }

    /**
     * Generate notifications from template
     */
    private function generateFromTemplate(array $template, int $count, Carbon $now): array
    {
        $notifications = [];
        $items = $template['templates'];
        $type = $template['type'];

        for ($i = 0; $i < min($count, count($items)); $i++) {
            $item = $items[$i];
            
            // Vary the notification age
            $daysAgo = rand(0, 14); // Last 2 weeks
            $hoursAgo = rand(0, 23);
            $minutesAgo = rand(0, 59);
            
            $createdAt = $now->copy()
                ->subDays($daysAgo)
                ->subHours($hoursAgo)
                ->subMinutes($minutesAgo);

            // Recent notifications are more likely to be unread
            $isRead = $daysAgo > 3 ? (rand(1, 100) > 30) : (rand(1, 100) > 70);

            $notifications[] = [
                'type' => $type,
                'title' => $item['title'],
                'message' => $item['message'],
                'action_url' => $item['action_url'],
                'is_read' => $isRead,
                'created_at' => $createdAt,
            ];
        }

        return $notifications;
    }
}
