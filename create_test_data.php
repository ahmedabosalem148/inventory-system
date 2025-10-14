<?php

/**
 * Quick Test Data Creator
 * Creates minimal test data for return system testing
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Branch;
use App\Models\ProductBranchStock;
use App\Models\Sequence;

echo "🔧 Creating test data...\n\n";

// Create category
$category = Category::firstOrCreate(
    ['name' => 'عام'],
    ['is_active' => true]
);
echo "✅ Category: {$category->name}\n";

// Create customer
$customer = Customer::firstOrCreate(
    ['code' => 'CUST-001'],
    [
        'name' => 'عميل تجريبي',
        'phone' => '01234567890',
        'address' => 'القاهرة',
        'is_active' => true
    ]
);
echo "✅ Customer: {$customer->name}\n";

// Create branch
$branch = Branch::firstOrCreate(
    ['code' => 'MAIN'],
    [
        'name' => 'الفرع الرئيسي',
        'is_active' => true
    ]
);
echo "✅ Branch: {$branch->name}\n";

// Create product
$product = Product::firstOrCreate(
    ['sku' => 'TEST-001'],
    [
        'name' => 'منتج تجريبي',
        'unit' => 'قطعة',
        'category_id' => $category->id,
        'purchase_price' => 50,
        'selling_price' => 100,
        'is_active' => true
    ]
);
echo "✅ Product: {$product->name}\n";

// Create product stock
$stock = ProductBranchStock::firstOrCreate(
    [
        'product_id' => $product->id,
        'branch_id' => $branch->id
    ],
    [
        'current_stock' => 100,
        'min_stock_level' => 10
    ]
);
echo "✅ Stock: {$stock->current_stock} {$product->unit}\n";

// Initialize sequences if needed
Sequence::firstOrCreate(
    ['entity_type' => 'return_vouchers', 'year' => date('Y')],
    [
        'last_number' => 100000,
        'prefix' => '',
        'min_value' => 100001,
        'max_value' => 125000,
        'increment_by' => 1,
        'auto_reset' => false
    ]
);
echo "✅ Sequences initialized\n";

echo "\n✅ Test data created successfully!\n\n";
