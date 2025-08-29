<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;

class SimpleProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📦 إضافة منتجات تجريبية...');

        // Create some sample products
        $products = [
            [
                'name_ar' => 'شامبو للشعر',
                'carton_size' => 12,
                'active' => true,
            ],
            [
                'name_ar' => 'صابون استحمام',
                'carton_size' => 24,
                'active' => true,
            ],
            [
                'name_ar' => 'معجون أسنان',
                'carton_size' => 6,
                'active' => true,
            ],
            [
                'name_ar' => 'كريم لليدين',
                'carton_size' => 10,
                'active' => true,
            ],
            [
                'name_ar' => 'فرشاة أسنان',
                'carton_size' => 50,
                'active' => true,
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::firstOrCreate(
                ['name_ar' => $productData['name_ar']],
                $productData
            );
            $this->command->info("✅ منتج: {$product->name_ar}");
        }

        // Add inventory to warehouses
        $warehouses = Warehouse::all();
        $products = Product::where('active', true)->get();

        foreach ($warehouses as $warehouse) {
            $this->command->info("📋 إضافة مخزون لمخزن: {$warehouse->name}");
            
            foreach ($products as $product) {
                $inventory = WarehouseInventory::firstOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'closed_cartons' => rand(5, 20),
                        'loose_units' => rand(0, $product->carton_size - 1),
                        'min_threshold' => rand(10, 50),
                    ]
                );
                
                if ($inventory->wasRecentlyCreated) {
                    $this->command->info("   ✅ {$product->name_ar}: {$inventory->closed_cartons} كراتين، {$inventory->loose_units} وحدة");
                }
            }
        }

        $this->command->info('🎉 تم إضافة المنتجات والمخزون بنجاح!');
    }
}
