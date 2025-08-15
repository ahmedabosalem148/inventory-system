<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class RemoveOldWarehousesSeeder extends Seeder
{
    /**
     * Remove old warehouses (الجيزة والإسكندرية)
     */
    public function run(): void
    {
        $oldWarehouseNames = [
            'مخزن الجيزة',
            'مخزن الإسكندرية',
            'المخزن الرئيسي'
        ];

        foreach ($oldWarehouseNames as $warehouseName) {
            $warehouse = Warehouse::where('name', $warehouseName)->first();
            
            if ($warehouse) {
                $this->command->info("Deleting warehouse: {$warehouse->name} (ID: {$warehouse->id})");
                
                // Delete associated inventory records first
                $inventoryCount = $warehouse->inventory()->count();
                $warehouse->inventory()->delete();
                $this->command->info("  - Deleted {$inventoryCount} inventory records");
                
                // Then delete the warehouse
                $warehouse->delete();
                $this->command->info("  - Warehouse deleted successfully");
            } else {
                $this->command->info("Warehouse '{$warehouseName}' not found, skipping...");
            }
        }

        $this->command->info('Old warehouses cleanup completed!');
        
        // Show remaining warehouses
        $remaining = Warehouse::all(['id', 'name']);
        $this->command->info("\nRemaining warehouses:");
        foreach ($remaining as $warehouse) {
            $this->command->info("  - {$warehouse->name} (ID: {$warehouse->id})");
        }
    }
}
