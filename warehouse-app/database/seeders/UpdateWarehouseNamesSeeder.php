<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class UpdateWarehouseNamesSeeder extends Seeder
{
    /**
     * Update warehouse names to the new Arabic names
     */
    public function run(): void
    {
        // Update existing warehouses with new names
        $warehouses = [
            ['old_name' => 'المخزن الرئيسي', 'new_name' => 'مخزن العتبة'],
            ['old_name' => 'مخزن فرعي أ', 'new_name' => 'مخزن امبابة'],
            ['old_name' => 'مخزن فرعي ب', 'new_name' => 'مخزن المصنع'],
        ];

        foreach ($warehouses as $warehouseData) {
            $warehouse = Warehouse::where('name', $warehouseData['old_name'])->first();
            if ($warehouse) {
                $warehouse->update(['name' => $warehouseData['new_name']]);
                $this->command->info("Updated warehouse: {$warehouseData['old_name']} -> {$warehouseData['new_name']}");
            }
        }

        // If no warehouses exist, create the new ones
        if (Warehouse::count() === 0) {
            $newWarehouses = [
                ['name' => 'مخزن العتبة'],
                ['name' => 'مخزن امبابة'],
                ['name' => 'مخزن المصنع'],
            ];

            foreach ($newWarehouses as $warehouseData) {
                Warehouse::create($warehouseData);
                $this->command->info("Created warehouse: {$warehouseData['name']}");
            }
        }

        $this->command->info('Warehouse names updated successfully!');
    }
}
