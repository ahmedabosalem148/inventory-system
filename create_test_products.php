<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// First, let's check categories table
echo "=== Checking Categories ===\n";
$categories = DB::table('categories')->get();
if ($categories->isEmpty()) {
    echo "No categories found. Creating default categories...\n";
    
    $defaultCategories = [
        ['id' => 1, 'name' => 'إلكترونيات', 'description' => 'أجهزة إلكترونية وتقنية'],
        ['id' => 2, 'name' => 'ملابس', 'description' => 'ملابس رجالية ونسائية'],
        ['id' => 3, 'name' => 'مواد غذائية', 'description' => 'منتجات غذائية متنوعة'],
        ['id' => 4, 'name' => 'كتب', 'description' => 'كتب ومراجع علمية'],
        ['id' => 5, 'name' => 'أدوات', 'description' => 'أدوات يدوية وكهربائية']
    ];
    
    foreach ($defaultCategories as $category) {
        DB::table('categories')->insertOrIgnore([
            'id' => $category['id'],
            'name' => $category['name'],
            'description' => $category['description'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "✅ Created category: {$category['name']}\n";
    }
}

// Add some test products to the database
$products = [
    [
        'category_id' => 1, // Electronics
        'name' => 'لابتوب Dell XPS 13',
        'description' => 'لابتوب عالي الأداء للاستخدام المكتبي والتصميم',
        'unit' => 'قطعة',
        'purchase_price' => 45000.00,
        'sale_price' => 55000.00,
        'min_stock' => 5,
        'pack_size' => 1,
        'reorder_level' => 5,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now()
    ],
    [
        'category_id' => 2, // Clothing
        'name' => 'قميص قطني أزرق',
        'description' => 'قميص رجالي قطن 100% مقاس كبير',
        'unit' => 'قطعة',
        'purchase_price' => 150.00,
        'sale_price' => 250.00,
        'min_stock' => 10,
        'pack_size' => 1,
        'reorder_level' => 10,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now()
    ],
    [
        'category_id' => 3, // Food
        'name' => 'أرز بسمتي',
        'description' => 'أرز بسمتي هندي فاخر كيس 5 كيلو',
        'unit' => 'كيلو',
        'purchase_price' => 25.00,
        'sale_price' => 35.00,
        'min_stock' => 50,
        'pack_size' => 5,
        'reorder_level' => 50,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now()
    ],
    [
        'category_id' => 4, // Books
        'name' => 'كتاب البرمجة بـ PHP',
        'description' => 'كتاب شامل لتعلم لغة البرمجة PHP',
        'unit' => 'قطعة',
        'purchase_price' => 80.00,
        'sale_price' => 120.00,
        'min_stock' => 20,
        'pack_size' => 1,
        'reorder_level' => 20,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now()
    ],
    [
        'category_id' => 5, // Tools
        'name' => 'مفك براغي كهربائي',
        'description' => 'مفك براغي كهربائي بشاحن سريع',
        'unit' => 'قطعة',
        'purchase_price' => 200.00,
        'sale_price' => 350.00,
        'min_stock' => 8,
        'pack_size' => 1,
        'reorder_level' => 8,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now()
    ]
];

try {
    // Bootstrap Laravel application
    $app = require_once 'bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    echo "=== Adding Test Products ===\n";
    
    echo "\n=== Adding Products ===\n";
    foreach ($products as $product) {
        // Check if product already exists
        $exists = DB::table('products')->where('name', $product['name'])->exists();
        
        if (!$exists) {
            DB::table('products')->insert($product);
            echo "✅ Added: {$product['name']}\n";
        } else {
            echo "⚠️ Already exists: {$product['name']}\n";
        }
    }
    
    $totalProducts = DB::table('products')->count();
    echo "\n=== Summary ===\n";
    echo "Total Products in Database: {$totalProducts}\n";
    echo "✅ Test products setup completed!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}