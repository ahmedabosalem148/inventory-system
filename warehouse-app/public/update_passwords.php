<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Warehouse;

echo "<!DOCTYPE html><html><head><title>تحديث كلمات مرور المخازن</title></head><body>";
echo "<h2>تحديث كلمات مرور المخازن</h2>";

try {
    // Update warehouse passwords
    $warehousePasswords = [
        'مخزن العتبة' => '1234',
        'مخزن امبابة' => '2345', 
        'مخزن المصنع' => '3456'
    ];

    echo "<h3>تحديث كلمات المرور:</h3>";
    echo "<ul>";
    
    foreach ($warehousePasswords as $warehouseName => $password) {
        $warehouse = Warehouse::where('name', $warehouseName)->first();
        
        if ($warehouse) {
            $warehouse->update(['password' => $password]);
            echo "<li>✅ تم تحديث كلمة مرور {$warehouseName} إلى: <strong>{$password}</strong></li>";
        } else {
            echo "<li>❌ المخزن '{$warehouseName}' غير موجود</li>";
        }
    }
    
    echo "</ul>";
    
    // Show all warehouses with their passwords
    echo "<h3>جميع المخازن وكلمات المرور:</h3>";
    echo "<table border='1' style='border-collapse: collapse; padding: 5px;'>";
    echo "<tr><th>ID</th><th>اسم المخزن</th><th>كلمة المرور</th></tr>";
    
    $warehouses = Warehouse::all(['id', 'name', 'password']);
    foreach ($warehouses as $warehouse) {
        echo "<tr>";
        echo "<td>{$warehouse->id}</td>";
        echo "<td>{$warehouse->name}</td>";
        echo "<td><strong>{$warehouse->password}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-weight: bold;'>✅ تم تحديث كلمات المرور بنجاح!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ خطأ: " . $e->getMessage() . "</p>";
}

echo "<br><a href='/warehouses'>الذهاب إلى صفحة المخازن</a>";
echo "</body></html>";
?>
