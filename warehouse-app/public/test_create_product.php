<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Warehouse;
use App\Models\Product;
use App\Models\WarehouseInventory;

echo "<!DOCTYPE html><html><head><title>اختبار إنشاء منتج مباشر</title>";
echo "<style>body{font-family:Arial;direction:rtl;} .success{color:green;} .error{color:red;}</style>";
echo "</head><body>";
echo "<h2>اختبار إنشاء منتج مباشر</h2>";

if ($_POST) {
    try {
        $warehouseId = $_POST['warehouse_id'];
        $productName = $_POST['name'];
        $unitsPerCarton = $_POST['units_per_carton'];
        $minThreshold = $_POST['min_threshold'] ?? 0;
        $cartons = $_POST['cartons'];
        
        echo "<h3>البيانات المُدخلة:</h3>";
        echo "<ul>";
        echo "<li>مخزن ID: $warehouseId</li>";
        echo "<li>اسم المنتج: $productName</li>";
        echo "<li>وحدات في الكرتونة: $unitsPerCarton</li>";
        echo "<li>الحد الأدنى: $minThreshold</li>";
        echo "<li>عدد الكراتين: $cartons</li>";
        echo "</ul>";
        
        // Check warehouse exists
        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse) {
            throw new Exception("المخزن غير موجود");
        }
        
        echo "<p class='success'>✅ المخزن موجود: {$warehouse->name}</p>";
        
        // Create product
        $product = Product::create([
            'name' => $productName,
            'sku' => null,
            'description' => null,
            'units_per_carton' => $unitsPerCarton,
            'active' => true,
        ]);
        
        echo "<p class='success'>✅ تم إنشاء المنتج بنجاح: ID = {$product->id}</p>";
        
        // Create inventory
        $inventory = WarehouseInventory::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $product->id,
            'closed_cartons' => $cartons,
            'loose_units' => 0,
            'min_threshold' => $minThreshold,
        ]);
        
        echo "<p class='success'>✅ تم إنشاء سجل المخزون بنجاح: ID = {$inventory->id}</p>";
        
        $totalUnits = $cartons * $unitsPerCarton;
        echo "<p class='success'>✅ إجمالي الوحدات: {$totalUnits}</p>";
        
        echo "<p><a href='/warehouses/{$warehouseId}'>الذهاب إلى المخزن</a></p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ خطأ: " . $e->getMessage() . "</p>";
        echo "<p class='error'>تفاصيل: " . $e->getTraceAsString() . "</p>";
    }
}

// Show form
$warehouses = Warehouse::all();
echo "<h3>إضافة منتج جديد:</h3>";
echo "<form method='POST'>";
echo "<p>المخزن: <select name='warehouse_id'>";
foreach ($warehouses as $warehouse) {
    echo "<option value='{$warehouse->id}'>{$warehouse->name}</option>";
}
echo "</select></p>";
echo "<p>اسم المنتج: <input type='text' name='name' required></p>";
echo "<p>وحدات في الكرتونة: <input type='number' name='units_per_carton' value='24' required></p>";
echo "<p>الحد الأدنى: <input type='number' name='min_threshold' value='0'></p>";
echo "<p>عدد الكراتين: <input type='number' name='cartons' value='1' required></p>";
echo "<p><button type='submit'>إنشاء المنتج</button></p>";
echo "</form>";

// Show existing products
echo "<h3>المنتجات الموجودة:</h3>";
$products = Product::with('warehouses')->get();
if ($products->count() > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>الاسم</th><th>وحدات/كرتونة</th><th>المخازن</th></tr>";
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>{$product->id}</td>";
        echo "<td>{$product->name}</td>";
        echo "<td>{$product->units_per_carton}</td>";
        echo "<td>";
        foreach ($product->warehouses as $warehouseInventory) {
            echo $warehouseInventory->warehouse->name . " ";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>لا توجد منتجات</p>";
}

echo "</body></html>";
?>
