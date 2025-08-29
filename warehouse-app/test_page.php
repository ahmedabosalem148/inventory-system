<?php
// اختبار صفحة المخزن مباشرة
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// محاكاة session
session_start();
$_SESSION['warehouse_4_auth'] = true;

// محاكاة HTTP request للصفحة
$request = \Illuminate\Http\Request::create('/warehouses/4', 'GET');
$request->setLaravelSession(app('session.store'));

$response = $kernel->handle($request);

echo "HTTP Status: " . $response->getStatusCode() . "\n";
echo "Content Length: " . strlen($response->getContent()) . " bytes\n";

// فحص إذا كان في JavaScript errors
$content = $response->getContent();
if (preg_match('/warehouseId\s*=\s*(\d+)/', $content, $matches)) {
    echo "Warehouse ID في JavaScript: " . $matches[1] . "\n";
} else {
    echo "❌ warehouseId غير موجود في JavaScript\n";
}

if (strpos($content, 'loadInventory()') !== false) {
    echo "✅ loadInventory() موجود\n";
} else {
    echo "❌ loadInventory() غير موجود\n";
}

if (strpos($content, 'inventory-container') !== false) {
    echo "✅ inventory-container موجود\n";
} else {
    echo "❌ inventory-container غير موجود\n";
}
?>
