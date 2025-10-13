<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'code' => 'CUST-001',
                'name' => 'محمد أحمد علي',
                'type' => 'retail',
                'phone' => '01012345678',
                'address' => 'شارع النصر، القاهرة',
                'balance' => 0,
                'is_active' => true,
                'notes' => 'عميل دائم - خصم 5%',
            ],
            [
                'code' => 'CUST-002',
                'name' => 'أحمد محمود السيد',
                'type' => 'wholesale',
                'phone' => '01123456789',
                'address' => 'شارع الجمهورية، الجيزة',
                'balance' => 0,
                'is_active' => true,
                'notes' => null,
            ],
            [
                'code' => 'CUST-003',
                'name' => 'سارة محمد حسن',
                'type' => 'retail',
                'phone' => '01234567890',
                'address' => 'شارع الهرم، الجيزة',
                'balance' => 0,
                'is_active' => true,
                'notes' => null,
            ],
            [
                'code' => 'CUST-004',
                'name' => 'خالد عبدالله إبراهيم',
                'type' => 'wholesale',
                'phone' => '01098765432',
                'address' => 'ميدان العتبة، القاهرة',
                'balance' => 0,
                'is_active' => true,
                'notes' => 'شركة - دفع شهري',
            ],
            [
                'code' => 'CUST-005',
                'name' => 'فاطمة حسين علي',
                'type' => 'retail',
                'phone' => '01156789012',
                'address' => 'شارع رمسيس، القاهرة',
                'balance' => 0,
                'is_active' => true,
                'notes' => null,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }

        $this->command->info('✅ تم إضافة 5 عملاء نموذجيين بنجاح');
    }
}
