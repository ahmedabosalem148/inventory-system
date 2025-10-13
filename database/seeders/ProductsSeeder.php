<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, ensure we have categories
        $categories = [
            ['id' => 1, 'name' => 'كابلات كهربائية', 'description' => 'كابلات وأسلاك كهربائية بجميع المقاسات', 'is_active' => true],
            ['id' => 2, 'name' => 'مفاتيح كهربائية', 'description' => 'مفاتيح إضاءة وكنترول بأنواعها', 'is_active' => true],
            ['id' => 3, 'name' => 'لمبات', 'description' => 'لمبات LED وتوفير طاقة', 'is_active' => true],
            ['id' => 4, 'name' => 'أفياش ومقابس', 'description' => 'أفياش حائط ومقابس', 'is_active' => true],
            ['id' => 5, 'name' => 'قواطع وحماية', 'description' => 'قواطع كهرباء وأجهزة حماية', 'is_active' => true]
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['id' => $category['id']],
                array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }

        // Add test products
        $products = [
            // كابلات كهربائية
            [
                'category_id' => 1,
                'name' => 'كابل كهرباء 1.5 مم',
                'description' => 'كابل كهرباء نحاس 1.5 مم² - 100 متر - معزول PVC',
                'unit' => 'متر',
                'purchase_price' => 8.50,
                'sale_price' => 12.00,
                'min_stock' => 500,
                'pack_size' => 100,
                'reorder_level' => 200,
                'is_active' => true
            ],
            [
                'category_id' => 1,
                'name' => 'كابل كهرباء 2.5 مم',
                'description' => 'كابل كهرباء نحاس 2.5 مم² - 100 متر - معزول PVC',
                'unit' => 'متر',
                'purchase_price' => 12.00,
                'sale_price' => 17.00,
                'min_stock' => 400,
                'pack_size' => 100,
                'reorder_level' => 150,
                'is_active' => true
            ],
            [
                'category_id' => 1,
                'name' => 'كابل كهرباء 4 مم',
                'description' => 'كابل كهرباء نحاس 4 مم² - 100 متر - معزول PVC',
                'unit' => 'متر',
                'purchase_price' => 18.00,
                'sale_price' => 25.00,
                'min_stock' => 300,
                'pack_size' => 100,
                'reorder_level' => 100,
                'is_active' => true
            ],
            
            // مفاتيح كهربائية
            [
                'category_id' => 2,
                'name' => 'مفتاح فينوس عادي',
                'description' => 'مفتاح فينوس عادي - 10 أمبير - أبيض',
                'unit' => 'قطعة',
                'purchase_price' => 15.00,
                'sale_price' => 25.00,
                'min_stock' => 100,
                'pack_size' => 10,
                'reorder_level' => 50,
                'is_active' => true
            ],
            [
                'category_id' => 2,
                'name' => 'مفتاح فينوس دبل',
                'description' => 'مفتاح فينوس دبل - 10 أمبير - أبيض',
                'unit' => 'قطعة',
                'purchase_price' => 22.00,
                'sale_price' => 35.00,
                'min_stock' => 80,
                'pack_size' => 10,
                'reorder_level' => 40,
                'is_active' => true
            ],
            [
                'category_id' => 2,
                'name' => 'مفتاح مودرن تاتش',
                'description' => 'مفتاح كريستال تاتش - LED مدمج - أسود',
                'unit' => 'قطعة',
                'purchase_price' => 45.00,
                'sale_price' => 70.00,
                'min_stock' => 50,
                'pack_size' => 5,
                'reorder_level' => 25,
                'is_active' => true
            ],
            
            // لمبات
            [
                'category_id' => 3,
                'name' => 'لمبة LED 7 وات',
                'description' => 'لمبة LED موفرة للطاقة - 7 وات - ضوء أبيض',
                'unit' => 'قطعة',
                'purchase_price' => 12.00,
                'sale_price' => 20.00,
                'min_stock' => 200,
                'pack_size' => 20,
                'reorder_level' => 100,
                'is_active' => true
            ],
            [
                'category_id' => 3,
                'name' => 'لمبة LED 12 وات',
                'description' => 'لمبة LED موفرة للطاقة - 12 وات - ضوء أبيض',
                'unit' => 'قطعة',
                'purchase_price' => 18.00,
                'sale_price' => 30.00,
                'min_stock' => 150,
                'pack_size' => 20,
                'reorder_level' => 75,
                'is_active' => true
            ],
            [
                'category_id' => 3,
                'name' => 'لمبة LED 20 وات',
                'description' => 'لمبة LED موفرة للطاقة - 20 وات - ضوء أبيض بارد',
                'unit' => 'قطعة',
                'purchase_price' => 28.00,
                'sale_price' => 45.00,
                'min_stock' => 100,
                'pack_size' => 10,
                'reorder_level' => 50,
                'is_active' => true
            ],
            
            // أفياش ومقابس
            [
                'category_id' => 4,
                'name' => 'فيش فينوس 2 فتحة',
                'description' => 'فيش حائط 2 فتحة - 13 أمبير - أبيض',
                'unit' => 'قطعة',
                'purchase_price' => 18.00,
                'sale_price' => 30.00,
                'min_stock' => 120,
                'pack_size' => 12,
                'reorder_level' => 60,
                'is_active' => true
            ],
            [
                'category_id' => 4,
                'name' => 'فيش مودرن 3 فتحات',
                'description' => 'فيش حائط كريستال 3 فتحات - LED - أسود',
                'unit' => 'قطعة',
                'purchase_price' => 35.00,
                'sale_price' => 55.00,
                'min_stock' => 60,
                'pack_size' => 6,
                'reorder_level' => 30,
                'is_active' => true
            ],
            
            // قواطع كهربائية
            [
                'category_id' => 5,
                'name' => 'قاطع كهرباء 16 أمبير',
                'description' => 'قاطع تيار أوتوماتيكي MCB - 16A - أحادي القطب',
                'unit' => 'قطعة',
                'purchase_price' => 25.00,
                'sale_price' => 40.00,
                'min_stock' => 50,
                'pack_size' => 10,
                'reorder_level' => 25,
                'is_active' => true
            ],
            [
                'category_id' => 5,
                'name' => 'قاطع كهرباء 25 أمبير',
                'description' => 'قاطع تيار أوتوماتيكي MCB - 25A - أحادي القطب',
                'unit' => 'قطعة',
                'purchase_price' => 30.00,
                'sale_price' => 48.00,
                'min_stock' => 40,
                'pack_size' => 10,
                'reorder_level' => 20,
                'is_active' => true
            ],
            [
                'category_id' => 5,
                'name' => 'قاطع كهرباء 32 أمبير',
                'description' => 'قاطع تيار أوتوماتيكي MCB - 32A - ثنائي القطب',
                'unit' => 'قطعة',
                'purchase_price' => 45.00,
                'sale_price' => 70.00,
                'min_stock' => 30,
                'pack_size' => 6,
                'reorder_level' => 15,
                'is_active' => true
            ],
            
            // لوحات توزيع
            [
                'category_id' => 5,
                'name' => 'لوحة توزيع 6 قواطع',
                'description' => 'لوحة توزيع كهرباء معدنية - 6 وحدات - مع باب',
                'unit' => 'قطعة',
                'purchase_price' => 85.00,
                'sale_price' => 130.00,
                'min_stock' => 20,
                'pack_size' => 1,
                'reorder_level' => 10,
                'is_active' => true
            ],
            [
                'category_id' => 5,
                'name' => 'لوحة توزيع 12 قاطع',
                'description' => 'لوحة توزيع كهرباء معدنية - 12 وحدة - مع باب',
                'unit' => 'قطعة',
                'purchase_price' => 135.00,
                'sale_price' => 200.00,
                'min_stock' => 15,
                'pack_size' => 1,
                'reorder_level' => 8,
                'is_active' => true
            ],
            
            // أدوات يدوية
            [
                'category_id' => 5,
                'name' => 'كماشة كهربائي 8 بوصة',
                'description' => 'كماشة عازلة للكهرباء - 8 بوصة - يد مطاطية',
                'unit' => 'قطعة',
                'purchase_price' => 55.00,
                'sale_price' => 85.00,
                'min_stock' => 30,
                'pack_size' => 12,
                'reorder_level' => 15,
                'is_active' => true
            ],
            [
                'category_id' => 5,
                'name' => 'مفك كهربائي مستقيم',
                'description' => 'مفك عازل للكهرباء - 1000 فولت - رأس مستقيم',
                'unit' => 'قطعة',
                'purchase_price' => 18.00,
                'sale_price' => 30.00,
                'min_stock' => 40,
                'pack_size' => 12,
                'reorder_level' => 20,
                'is_active' => true
            ],
            [
                'category_id' => 5,
                'name' => 'مفك كهربائي صليبة',
                'description' => 'مفك عازل للكهرباء - 1000 فولت - رأس صليبة',
                'unit' => 'قطعة',
                'purchase_price' => 18.00,
                'sale_price' => 30.00,
                'min_stock' => 40,
                'pack_size' => 12,
                'reorder_level' => 20,
                'is_active' => true
            ],
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                ['name' => $product['name']],
                array_merge($product, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }

        $this->command->info('Products seeded successfully!');
    }
}
