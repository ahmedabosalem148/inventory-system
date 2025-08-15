<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class AddWarehousePasswordsSeeder extends Seeder
{
    /**
     * Add passwords to existing warehouses
     */
    public function run(): void
    {
        $warehousePasswords = [
            'مخزن العتبة' => '1234',
            'مخزن امبابة' => '2345', 
            'مخزن المصنع' => '3456'
        ];

        foreach ($warehousePasswords as $warehouseName => $password) {
            $warehouse = Warehouse::where('name', $warehouseName)->first();
            
            if ($warehouse) {
                $warehouse->update(['password' => $password]);
                $this->command->info("Updated password for: {$warehouseName} -> {$password}");
            } else {
                $this->command->info("Warehouse '{$warehouseName}' not found, skipping...");
            }
        }

        $this->command->info('Warehouse passwords added successfully!');
        
        // Show all warehouses with their passwords
        $warehouses = Warehouse::all(['id', 'name', 'password']);
        $this->command->info("\nWarehouse credentials:");
        foreach ($warehouses as $warehouse) {
            $this->command->info("  - {$warehouse->name}: {$warehouse->password}");
        }
    }
}
