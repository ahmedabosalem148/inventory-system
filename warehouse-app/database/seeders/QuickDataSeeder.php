<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\WarehouseInventory;

class QuickDataSeeder extends Seeder
{
    public function run()
    {
        echo "إنشاء المخازن...\n";
        
        // إنشاء مخازن تجريبية
        $warehouses = [
            ['name' => 'مخزن العتبة'],
            ['name' => 'مخزن امبابة'],
            ['name' => 'مخزن المصنع']
        ];

        foreach ($warehouses as $warehouseData) {
            $warehouse = Warehouse::firstOrCreate(
                ['name' => $warehouseData['name']], 
                $warehouseData
            );
            echo "- تم إنشاء: {$warehouse->name} (ID: {$warehouse->id})\n";
        }

        echo "\nإنشاء منتجات إضافية...\n";
        
        // إنشاء منتجات إضافية
        $products = [
            ['name' => 'شامبو الأطفال', 'carton_size' => 12, 'active' => true],
            ['name' => 'معجون الأسنان', 'carton_size' => 6, 'active' => true],
            ['name' => 'صابون اليدين', 'carton_size' => 24, 'active' => true],
            ['name' => 'كريم الوجه', 'carton_size' => 8, 'active' => true]
        ];

        foreach ($products as $productData) {
            $product = Product::firstOrCreate(
                ['name' => $productData['name']], 
                $productData
            );
            echo "- تم إنشاء: {$product->name} (ID: {$product->id})\n";
        }

        echo "\nإضافة مخزون تجريبي...\n";

        // الحصول على جميع المنتجات والمخازن
        $allProducts = Product::all();
        $allWarehouses = Warehouse::all();

        foreach ($allWarehouses as $warehouse) {
            foreach ($allProducts as $product) {
                // تحقق من وجود المخزون مسبقاً
                $existing = WarehouseInventory::where('warehouse_id', $warehouse->id)
                                               ->where('product_id', $product->id)
                                               ->first();
                
                if (!$existing) {
                    $closedCartons = rand(1, 15);
                    $looseUnits = rand(0, $product->carton_size - 1);
                    $minThreshold = rand(20, 80);
                    
                    WarehouseInventory::create([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                        'closed_cartons' => $closedCartons,
                        'loose_units' => $looseUnits,
                        'min_threshold' => $minThreshold
                    ]);
                    
                    $total = ($closedCartons * $product->carton_size) + $looseUnits;
                    echo "  - {$warehouse->name} <- {$product->name}: {$total} وحدة\n";
                }
            }
        }

        echo "\nتم الانتهاء! الإحصائيات:\n";
        echo "- المخازن: " . Warehouse::count() . "\n";
        echo "- المنتجات: " . Product::count() . "\n";
        echo "- عناصر المخزون: " . WarehouseInventory::count() . "\n";
    }
}
