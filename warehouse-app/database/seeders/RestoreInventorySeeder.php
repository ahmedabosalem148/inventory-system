<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;

class RestoreInventorySeeder extends Seeder
{
    /**
     * Restore inventory for clean warehouses
     */
    public function run(): void
    {
        $this->command->info('📦 إعادة إضافة المخزون للمخازن...');

        $warehouses = Warehouse::all();
        $products = Product::where('active', true)->get();

        if ($products->isEmpty()) {
            $this->command->warn('⚠️  لا توجد منتجات! يرجى إضافة منتجات أولاً');
            return;
        }

        foreach ($warehouses as $warehouse) {
            $this->command->info("📋 إضافة مخزون لمخزن: {$warehouse->name}");
            
            foreach ($products as $product) {
                $existing = WarehouseInventory::where('warehouse_id', $warehouse->id)
                    ->where('product_id', $product->id)
                    ->first();
                    
                if (!$existing) {
                    $inventory = WarehouseInventory::create([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                        'closed_cartons' => rand(5, 20),
                        'loose_units' => rand(0, ($product->carton_size ?? 12) - 1),
                        'min_threshold' => rand(10, 50),
                    ]);
                    
                    $this->command->info("   ✅ {$product->name_ar}: {$inventory->closed_cartons} كراتين، {$inventory->loose_units} وحدة");
                }
            }
        }

        $this->command->info('🎉 تم إعادة إضافة المخزون بنجاح!');
    }
}
