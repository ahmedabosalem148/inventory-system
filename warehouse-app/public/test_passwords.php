<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Warehouse;

echo "<!DOCTYPE html><html><head><title>اختبار كلمات مرور المخازن</title>";
echo "<style>body{font-family:Arial;direction:rtl;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;} .test{margin:10px 0;padding:10px;border:1px solid #ccc;}</style>";
echo "</head><body>";
echo "<h2>اختبار كلمات مرور المخازن</h2>";

if ($_POST) {
    $warehouseId = $_POST['warehouse_id'];
    $password = $_POST['password'];
    
    $warehouse = Warehouse::find($warehouseId);
    
    if ($warehouse) {
        echo "<div class='test'>";
        echo "<h3>نتيجة الاختبار:</h3>";
        echo "<p><strong>المخزن:</strong> {$warehouse->name}</p>";
        echo "<p><strong>كلمة المرور المدخلة:</strong> '{$password}'</p>";
        echo "<p><strong>كلمة المرور المخزنة:</strong> '{$warehouse->password}'</p>";
        
        if ($password === $warehouse->password) {
            echo "<p style='color:green;'><strong>✅ كلمة المرور صحيحة!</strong></p>";
        } else {
            echo "<p style='color:red;'><strong>❌ كلمة المرور خاطئة!</strong></p>";
            echo "<p><strong>التفاصيل:</strong></p>";
            echo "<p>طول كلمة المرور المدخلة: " . strlen($password) . "</p>";
            echo "<p>طول كلمة المرور المخزنة: " . strlen($warehouse->password) . "</p>";
            echo "<p>مقارنة ASCII: " . bin2hex($password) . " vs " . bin2hex($warehouse->password) . "</p>";
        }
        echo "</div>";
    }
}

try {
    $warehouses = Warehouse::all(['id', 'name', 'password']);
    
    echo "<h3>جميع المخازن:</h3>";
    echo "<table>";
    echo "<tr><th>ID</th><th>اسم المخزن</th><th>كلمة المرور</th><th>اختبار</th></tr>";
    
    foreach ($warehouses as $warehouse) {
        echo "<tr>";
        echo "<td>{$warehouse->id}</td>";
        echo "<td>{$warehouse->name}</td>";
        echo "<td><strong>{$warehouse->password}</strong></td>";
        echo "<td>";
        echo "<form method='POST' style='display:inline;'>";
        echo "<input type='hidden' name='warehouse_id' value='{$warehouse->id}'>";
        echo "<input type='text' name='password' placeholder='أدخل كلمة المرور' style='width:100px;'>";
        echo "<input type='submit' value='اختبار'>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}

echo "<br><a href='/warehouses'>الذهاب إلى صفحة المخازن</a>";
echo "</body></html>";
?>
