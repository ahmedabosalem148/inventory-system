<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // إنشاء الصلاحيات (Permissions)
        $permissions = [
            // إدارة الفروع
            'view-branches',
            'create-branches',
            'edit-branches',
            'delete-branches',

            // إدارة التصنيفات
            'view-categories',
            'create-categories',
            'edit-categories',
            'delete-categories',

            // إدارة المنتجات
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',

            // إدارة أذون الصرف
            'view-issue-vouchers',
            'create-issue-vouchers',
            'edit-issue-vouchers',
            'approve-issue-vouchers',
            'delete-issue-vouchers',
            'print-issue-vouchers',

            // إدارة أذون الارتجاع
            'view-return-vouchers',
            'create-return-vouchers',
            'edit-return-vouchers',
            'approve-return-vouchers',
            'delete-return-vouchers',
            'print-return-vouchers',

            // إدارة العملاء
            'view-customers',
            'create-customers',
            'edit-customers',
            'delete-customers',
            'view-customer-ledger',
            'print-customer-statement',

            // إدارة المدفوعات
            'view-payments',
            'create-payments',
            'edit-payments',
            'delete-payments',

            // إدارة الشيكات
            'view-cheques',
            'create-cheques',
            'edit-cheques',
            'clear-cheques',
            'return-cheques',

            // التقارير
            'view-inventory-reports',
            'view-customer-reports',
            'view-financial-reports',
            'export-reports',

            // الاستيراد
            'import-data',

            // لوحة المتابعة
            'view-dashboard',

            // سجل التدقيق
            'view-activity-log',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ===============================================
        // إنشاء الأدوار (Roles) وربط الصلاحيات
        // ===============================================

        // 1. مدير (Manager) - صلاحيات كاملة
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->givePermissionTo(Permission::all());

        // 2. مستخدم مخزن (Store User) - مرتبط بفرع واحد
        $storeUserRole = Role::firstOrCreate(['name' => 'store_user']);
        $storeUserRole->givePermissionTo([
            'view-products',
            'view-issue-vouchers',
            'create-issue-vouchers',
            'edit-issue-vouchers',
            'approve-issue-vouchers',  // فقط لفرعه
            'print-issue-vouchers',
            'view-return-vouchers',
            'create-return-vouchers',
            'edit-return-vouchers',
            'approve-return-vouchers',  // فقط لفرعه
            'print-return-vouchers',
            'view-customers',
            'create-customers',
            'edit-customers',
            'view-customer-ledger',
            'view-inventory-reports',  // فقط لفرعه
            'view-dashboard',
        ]);

        // 3. محاسب (Accounting) - مالية + قراءة مخزون
        $accountingRole = Role::firstOrCreate(['name' => 'accounting']);
        $accountingRole->givePermissionTo([
            // قراءة فقط للمخزون
            'view-products',
            'view-issue-vouchers',
            'view-return-vouchers',
            'view-inventory-reports',

            // صلاحيات كاملة على المالية
            'view-customers',
            'create-customers',
            'edit-customers',
            'view-customer-ledger',
            'print-customer-statement',
            'view-payments',
            'create-payments',
            'edit-payments',
            'delete-payments',
            'view-cheques',
            'create-cheques',
            'edit-cheques',
            'clear-cheques',
            'return-cheques',
            'view-customer-reports',
            'view-financial-reports',
            'export-reports',
            'view-dashboard',
        ]);

        // ===============================================
        // إنشاء مستخدمين تجريبيين (اختياري)
        // ===============================================

        // مستخدم مدير
        $manager = User::firstOrCreate(
            ['email' => 'manager@inventory.local'],
            [
                'name' => 'مدير النظام',
                'password' => bcrypt('password'),
            ]
        );
        $manager->assignRole('manager');

        // مستخدم مخزن فرع المصنع
        $storeUser1 = User::firstOrCreate(
            ['email' => 'store1@inventory.local'],
            [
                'name' => 'أمين مخزن المصنع',
                'password' => bcrypt('password'),
            ]
        );
        $storeUser1->assignRole('store_user');

        // مستخدم محاسب
        $accountant = User::firstOrCreate(
            ['email' => 'accounting@inventory.local'],
            [
                'name' => 'محاسب',
                'password' => bcrypt('password'),
            ]
        );
        $accountant->assignRole('accounting');

        $this->command->info('✅ تم إنشاء الأدوار والصلاحيات بنجاح!');
        $this->command->info('📧 المستخدمين:');
        $this->command->info('   Manager: manager@inventory.local / password');
        $this->command->info('   Store User: store1@inventory.local / password');
        $this->command->info('   Accounting: accounting@inventory.local / password');
    }
}
