<?php
// فحص مخزون العتبة

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Warehouse;
use App\Models\Product;
use App\Models\WarehouseInventory;

echo "🔍 فحص مخزن العتبة\n";
echo "==================\n\n";

// 1. البحث عن مخزن العتبة
$warehouse = Warehouse::where('name', 'العتبة')->first();
if (!$warehouse) {
    echo "❌ لم يتم العثور على مخزن العتبة!\n";
    exit;
}

echo "✅ مخزن العتبة موجود:\n";
echo "   ID: {$warehouse->id}\n";
echo "   الاسم: {$warehouse->name}\n\n";

// 2. فحص جميع المنتجات
echo "📦 جميع المنتجات:\n";
$products = Product::where('active', true)->get();
echo "   عدد المنتجات: " . $products->count() . "\n";
foreach ($products as $product) {
    echo "   - {$product->name_ar} (ID: {$product->id})\n";
}
echo "\n";

// 3. فحص مخزون العتبة
echo "📋 مخزون العتبة:\n";
$inventory = WarehouseInventory::where('warehouse_id', $warehouse->id)->get();
echo "   عدد العناصر: " . $inventory->count() . "\n";

if ($inventory->isEmpty()) {
    echo "   ❌ لا يوجد مخزون في العتبة!\n\n";
    
    // إضافة مخزون تجريبي
    echo "🔧 إضافة مخزون تجريبي...\n";
    foreach ($products as $product) {
        $inv = WarehouseInventory::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'closed_cartons' => rand(5, 15),
            'loose_units' => rand(0, ($product->carton_size ?? 12) - 1),
            'min_threshold' => rand(10, 30),
        ]);
        echo "   ✅ {$product->name_ar}: {$inv->closed_cartons} كراتين + {$inv->loose_units} وحدة\n";
    }
} else {
    foreach ($inventory as $inv) {
        $product = $inv->product;
        $totalUnits = ($inv->closed_cartons * ($product->carton_size ?? 12)) + $inv->loose_units;
        echo "   ✅ {$product->name_ar}: {$inv->closed_cartons} كراتين + {$inv->loose_units} وحدة = {$totalUnits} إجمالي\n";
    }
}

echo "\n🌐 تحقق من API:\n";
echo "   /api/warehouses/{$warehouse->id}/inventory\n";
?>
