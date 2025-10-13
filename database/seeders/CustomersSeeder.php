<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing customers
        DB::table('customers')->delete();

        $customers = [
            [
                'code' => 'CUS-00001',
                'name' => 'أحمد محمد علي',
                'type' => 'retail',
                'phone' => '01012345678',
                'address' => 'القاهرة، مصر الجديدة',
                'balance' => 0,
                'is_active' => true,
                'notes' => 'عميل دائم منذ 2020',
            ],
            [
                'code' => 'CUS-00002',
                'name' => 'فاطمة حسن إبراهيم',
                'type' => 'retail',
                'phone' => '01098765432',
                'address' => 'الإسكندرية، سموحة',
                'balance' => 500.00,
                'is_active' => true,
                'notes' => null,
            ],
            [
                'code' => 'CUS-00003',
                'name' => 'محمود السيد عبدالله',
                'type' => 'wholesale',
                'phone' => '01123456789',
                'address' => 'الجيزة، الهرم',
                'balance' => -250.00,
                'is_active' => true,
                'notes' => 'عميل جملة، خصم 10%',
            ],
            [
                'code' => 'CUS-00004',
                'name' => 'سارة أحمد محمد',
                'type' => 'retail',
                'phone' => '01234567890',
                'address' => 'القاهرة، المعادي',
                'balance' => 0,
                'is_active' => true,
                'notes' => null,
            ],
            [
                'code' => 'CUS-00005',
                'name' => 'خالد عبدالرحمن',
                'type' => 'wholesale',
                'phone' => '01156789012',
                'address' => 'المنصورة، شارع الجلاء',
                'balance' => 1200.00,
                'is_active' => true,
                'notes' => 'عميل ممتاز - دفع نقدي',
            ],
            [
                'code' => 'CUS-00006',
                'name' => 'نور الدين مصطفى',
                'type' => 'retail',
                'phone' => '01045678901',
                'address' => 'طنطا، شارع البحر',
                'balance' => -150.00,
                'is_active' => true,
                'notes' => null,
            ],
            [
                'code' => 'CUS-00007',
                'name' => 'مريم سعيد أحمد',
                'type' => 'retail',
                'phone' => '01087654321',
                'address' => 'أسيوط، الحمراء',
                'balance' => 300.00,
                'is_active' => false,
                'notes' => 'تم إيقاف الحساب مؤقتاً',
            ],
            [
                'code' => 'CUS-00008',
                'name' => 'عمر حسين محمود',
                'type' => 'wholesale',
                'phone' => '01198765432',
                'address' => 'الإسماعيلية، شارع سعد زغلول',
                'balance' => 0,
                'is_active' => true,
                'notes' => 'عميل جديد',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }

        $this->command->info('تم إضافة ' . count($customers) . ' عملاء بنجاح!');
    }
}
