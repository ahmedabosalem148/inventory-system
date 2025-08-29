<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;

class UpdateWarehousePasswordsSeeder extends Seeder
{
    /**
     * تحديث كلمات مرور المخازن لتكون 1234
     */
    public function run(): void
    {
        $this->command->info('🔑 تحديث كلمات مرور المخازن...');

        $warehouses = Warehouse::all();
        
        foreach ($warehouses as $warehouse) {
            $warehouse->update([
                'password' => Hash::make('1234')
            ]);
            
            $this->command->info("✅ تم تحديث كلمة مرور {$warehouse->name} إلى: 1234");
        }

        $this->command->info('🎉 تم تحديث جميع كلمات المرور بنجاح!');
        $this->command->info('💡 استخدم كلمة المرور: 1234 لجميع المخازن');
    }
}
