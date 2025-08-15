<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;

class ProductionWarehousesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $this->command->info('🏪 إضافة المخازن الإنتاجية...');

        $warehouses = [
            [
                'name' => 'العتبة',
                'password' => Hash::make('ataba123'), // كلمة مرور: ataba123
            ],
            [
                'name' => 'امبابة',
                'password' => Hash::make('imbaba123'), // كلمة مرور: imbaba123
            ],
            [
                'name' => 'المصنع',
                'password' => Hash::make('factory123'), // كلمة مرور: factory123
            ],
        ];

        foreach ($warehouses as $warehouseData) {
            $existing = Warehouse::where('name', $warehouseData['name'])->first();
            
            if ($existing) {
                $this->command->warn("⚠️  المخزن '{$warehouseData['name']}' موجود بالفعل، تم التخطي");
                continue;
            }

            $warehouse = Warehouse::create($warehouseData);
            $this->command->info("✅ تم إضافة مخزن: {$warehouse->name}");
        }

        $this->command->info('');
        $this->command->info('🔑 كلمات المرور للمخازن الجديدة:');
        $this->command->info('   العتبة: ataba123');
        $this->command->info('   امبابة: imbaba123');
        $this->command->info('   المصنع: factory123');
        $this->command->info('');
        $this->command->info('📋 ملاحظة: المخازن فارغة ولا تحتوي على أي بيانات تجريبية');
    }
}
