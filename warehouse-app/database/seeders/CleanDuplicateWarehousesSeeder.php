<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;

class CleanDuplicateWarehousesSeeder extends Seeder
{
    /**
     * Clean duplicate warehouses and keep only production ones
     */
    public function run(): void
    {
        $this->command->info('🧹 تنظيف المخازن المكررة...');

        // Delete old warehouse names that start with "مخزن"
        $oldWarehouses = Warehouse::where('name', 'like', 'مخزن%')->get();
        
        foreach ($oldWarehouses as $warehouse) {
            $this->command->info("🗑️  حذف المخزن القديم: {$warehouse->name}");
            
            // Delete related inventory first
            WarehouseInventory::where('warehouse_id', $warehouse->id)->delete();
            
            // Delete warehouse
            $warehouse->delete();
        }

        // Also delete "المخزن 1" and "المخزن 2" if they exist
        $oldNames = ['المخزن 1', 'المخزن 2'];
        foreach ($oldNames as $name) {
            $warehouse = Warehouse::where('name', $name)->first();
            if ($warehouse) {
                $this->command->info("🗑️  حذف المخزن القديم: {$warehouse->name}");
                WarehouseInventory::where('warehouse_id', $warehouse->id)->delete();
                $warehouse->delete();
            }
        }

        $this->command->info('✅ تم تنظيف المخازن المكررة بنجاح!');
        
        // Show remaining warehouses
        $remaining = Warehouse::all();
        $this->command->info('📋 المخازن المتبقية:');
        foreach ($remaining as $warehouse) {
            $this->command->info("   - {$warehouse->name}");
        }
    }
}
