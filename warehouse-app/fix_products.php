use App\Models\Product;
use App\Models\WarehouseInventory;
use App\Models\Warehouse;

// Find warehouse 4
$warehouse = Warehouse::find(4);
echo "Warehouse: " . $warehouse->name . "\n";

// Clear existing inventory
WarehouseInventory::where('warehouse_id', 4)->delete();
echo "Cleared existing inventory\n";

// Delete test products
Product::whereIn('name_ar', ['شاي ليبتون', 'قهوة نسكافيه', 'سكر فاخر'])->delete();
echo "Deleted old products\n";

// Add real products with proper Arabic names
$products = [
    ['name_ar' => 'شاي ليبتون أحمر', 'carton_size' => 24, 'cartons' => 5, 'units' => 12, 'min_threshold' => 50],
    ['name_ar' => 'قهوة نسكافيه كلاسيك', 'carton_size' => 12, 'cartons' => 3, 'units' => 8, 'min_threshold' => 30],
    ['name_ar' => 'سكر أبيض فاخر', 'carton_size' => 20, 'cartons' => 2, 'units' => 5, 'min_threshold' => 40],
    ['name_ar' => 'أرز مصري', 'carton_size' => 10, 'cartons' => 4, 'units' => 3, 'min_threshold' => 25],
    ['name_ar' => 'زيت عباد الشمس', 'carton_size' => 12, 'cartons' => 6, 'units' => 2, 'min_threshold' => 20]
];

foreach ($products as $productData) {
    $product = Product::create([
        'name_ar' => $productData['name_ar'],
        'carton_size' => $productData['carton_size'],
        'active' => true
    ]);
    
    WarehouseInventory::create([
        'warehouse_id' => 4,
        'product_id' => $product->id,
        'closed_cartons' => $productData['cartons'],
        'loose_units' => $productData['units'],
        'min_threshold' => $productData['min_threshold']
    ]);
    
    echo "Added: " . $product->name_ar . "\n";
}

echo "Done!\n";
