<?php
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Inventory;
use App\Models\Warehouse;

// Find warehouse 4
$warehouse = Warehouse::find(4);
if (!$warehouse) {
    echo "Warehouse 4 not found!\n";
    exit;
}

echo "Adding test products to warehouse: {$warehouse->name}\n";

// Clear existing inventory
Inventory::where('warehouse_id', 4)->delete();
echo "Cleared existing inventory\n";

// Add test products
$products = [
    [
        'name_ar' => 'شاي ليبتون',
        'carton_size' => 24,
        'cartons' => 5,
        'units' => 12,
        'min_threshold' => 50
    ],
    [
        'name_ar' => 'قهوة نسكافيه',
        'carton_size' => 12,
        'cartons' => 3,
        'units' => 8,
        'min_threshold' => 30
    ],
    [
        'name_ar' => 'سكر فاخر',
        'carton_size' => 20,
        'cartons' => 2,
        'units' => 5,
        'min_threshold' => 40
    ]
];

foreach ($products as $productData) {
    // Create or find product
    $product = Product::firstOrCreate(
        ['name_ar' => $productData['name_ar']],
        ['carton_size' => $productData['carton_size']]
    );
    
    // Create inventory
    Inventory::create([
        'warehouse_id' => 4,
        'product_id' => $product->id,
        'closed_cartons' => $productData['cartons'],
        'loose_units' => $productData['units'],
        'min_threshold' => $productData['min_threshold']
    ]);
    
    echo "Added: {$product->name_ar} ({$productData['cartons']} cartons, {$productData['units']} units)\n";
}

echo "\nInventory summary for warehouse 4:\n";
$inventory = Inventory::where('warehouse_id', 4)->with('product')->get();
foreach ($inventory as $item) {
    $totalUnits = ($item->closed_cartons * $item->product->carton_size) + $item->loose_units;
    echo "- {$item->product->name_ar}: {$totalUnits} total units\n";
}

echo "\nTest products added successfully!\n";
