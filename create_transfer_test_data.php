<?php

/**
 * Create Test Data for Transfer Testing
 * Run this to ensure you have products with stock for testing
 * Usage: php create_transfer_test_data.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Branch;
use App\Models\ProductBranchStock;

echo "\n" . str_repeat("=", 60) . "\n";
echo "🔧 Creating Test Data for Transfer System\n";
echo str_repeat("=", 60) . "\n\n";

// Get or create branches
$mainBranch = Branch::firstOrCreate(
    ['name' => 'الفرع الرئيسي'],
    ['code' => 'MAIN', 'is_active' => true]
);

$secondBranch = Branch::firstOrCreate(
    ['name' => 'الفرع الفرعي'],
    ['code' => 'SUB', 'is_active' => true]
);

echo "✅ Branches Ready:\n";
echo "   ID: {$mainBranch->id} - {$mainBranch->name}\n";
echo "   ID: {$secondBranch->id} - {$secondBranch->name}\n\n";

// Create test products with stock
$products = [
    [
        'name' => 'منتج تجريبي للتحويل 1',
        'sku' => 'TEST-TRANSFER-001',
        'unit' => 'قطعة',
        'purchase_price' => 50,
        'selling_price' => 100,
        'stock' => 100
    ],
    [
        'name' => 'منتج تجريبي للتحويل 2',
        'sku' => 'TEST-TRANSFER-002',
        'unit' => 'كرتونة',
        'purchase_price' => 200,
        'selling_price' => 300,
        'stock' => 50
    ],
    [
        'name' => 'منتج تجريبي للتحويل 3',
        'sku' => 'TEST-TRANSFER-003',
        'unit' => 'كيلو',
        'purchase_price' => 30,
        'selling_price' => 60,
        'stock' => 200
    ]
];

echo "📦 Creating Test Products:\n\n";

foreach ($products as $productData) {
    $product = Product::firstOrCreate(
        ['sku' => $productData['sku']],
        [
            'name' => $productData['name'],
            'unit' => $productData['unit'],
            'purchase_price' => $productData['purchase_price'],
            'selling_price' => $productData['selling_price'],
            'is_active' => true
        ]
    );

    // Add stock to main branch
    $stock = ProductBranchStock::firstOrCreate(
        [
            'product_id' => $product->id,
            'branch_id' => $mainBranch->id
        ],
        [
            'current_stock' => 0,
            'min_stock_level' => 10
        ]
    );

    // Update stock if needed
    if ($stock->current_stock < $productData['stock']) {
        $stock->current_stock = $productData['stock'];
        $stock->save();
    }

    // Ensure second branch record exists (can be empty)
    ProductBranchStock::firstOrCreate(
        [
            'product_id' => $product->id,
            'branch_id' => $secondBranch->id
        ],
        [
            'current_stock' => 0,
            'min_stock_level' => 10
        ]
    );

    echo "   ✅ {$product->name}\n";
    echo "      SKU: {$product->sku}\n";
    echo "      Stock in {$mainBranch->name}: {$stock->current_stock} {$product->unit}\n";
    echo "      Price: {$product->selling_price} EGP\n\n";
}

echo str_repeat("=", 60) . "\n";
echo "🎯 Test Data Created Successfully!\n";
echo str_repeat("=", 60) . "\n\n";

echo "📝 Next Steps:\n";
echo "   1. Open browser: http://localhost:3001\n";
echo "   2. Go to Issue Vouchers page\n";
echo "   3. Click 'إذن صرف جديد'\n";
echo "   4. Enable 'تحويل بين فروع' checkbox\n";
echo "   5. Select Target Branch: {$secondBranch->name}\n";
echo "   6. Add products from the list above\n";
echo "   7. Save and then Approve the transfer\n";
echo "   8. Run: php test_transfer_manual.php\n\n";

echo "📊 Current Stock Summary:\n";
echo str_repeat("-", 60) . "\n";

$allStock = ProductBranchStock::with(['product', 'branch'])
    ->where('current_stock', '>', 0)
    ->orderBy('branch_id')
    ->get();

$currentBranchId = null;
foreach ($allStock as $stock) {
    if ($currentBranchId !== $stock->branch_id) {
        echo "\n   {$stock->branch->name}:\n";
        $currentBranchId = $stock->branch_id;
    }
    echo "      {$stock->product->name}: {$stock->current_stock} {$stock->product->unit}\n";
}

echo "\n" . str_repeat("=", 60) . "\n\n";
