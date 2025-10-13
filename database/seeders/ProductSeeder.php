<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * منتجات أدوات كهربائية أساسية
     */
    public function run(): void
    {
        $products = [
            // لمبات LED (category_id = 1)
            [
                'category_id' => 1,
                'name' => 'لمبة LED 7 وات - أبيض',
                'description' => 'لمبة LED موفرة للطاقة 7 وات إضاءة بيضاء',
                'unit' => 'قطعة',
                'purchase_price' => 15.00,
                'sale_price' => 25.00,
                'min_stock' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 1,
                'name' => 'لمبة LED 12 وات - أصفر',
                'description' => 'لمبة LED 12 وات إضاءة صفراء دافئة',
                'unit' => 'قطعة',
                'purchase_price' => 20.00,
                'sale_price' => 35.00,
                'min_stock' => 40,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // مفاتيح كهربائية (category_id = 2)
            [
                'category_id' => 2,
                'name' => 'مفتاح إضاءة مفرد',
                'description' => 'مفتاح كهربائي مفرد جودة عالية',
                'unit' => 'قطعة',
                'purchase_price' => 8.00,
                'sale_price' => 15.00,
                'min_stock' => 100,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 2,
                'name' => 'مفتاح إضاءة مزدوج',
                'description' => 'مفتاح كهربائي مزدوج',
                'unit' => 'قطعة',
                'purchase_price' => 12.00,
                'sale_price' => 22.00,
                'min_stock' => 80,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // أسلاك كهربائية (category_id = 3)
            [
                'category_id' => 3,
                'name' => 'سلك كهرباء 1.5 ملم',
                'description' => 'سلك كهربائي نحاس 1.5 ملم',
                'unit' => 'متر',
                'purchase_price' => 5.00,
                'sale_price' => 8.00,
                'min_stock' => 200,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 3,
                'name' => 'سلك كهرباء 2.5 ملم',
                'description' => 'سلك كهربائي نحاس 2.5 ملم',
                'unit' => 'متر',
                'purchase_price' => 8.00,
                'sale_price' => 12.00,
                'min_stock' => 150,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // قواطع كهربائية (category_id = 4)
            [
                'category_id' => 4,
                'name' => 'قاطع كهربائي 16 أمبير',
                'description' => 'قاطع حماية 16 أمبير',
                'unit' => 'قطعة',
                'purchase_price' => 25.00,
                'sale_price' => 40.00,
                'min_stock' => 30,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => 4,
                'name' => 'قاطع كهربائي 32 أمبير',
                'description' => 'قاطع حماية 32 أمبير',
                'unit' => 'قطعة',
                'purchase_price' => 40.00,
                'sale_price' => 65.00,
                'min_stock' => 25,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('products')->insert($products);
    }
}
