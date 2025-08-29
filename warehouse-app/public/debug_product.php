<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Warehouse;

echo "<!DOCTYPE html><html><head><title>اختبار إضافة المنتج</title>";
echo "<style>body{font-family:Arial;direction:rtl;} .form{margin:20px 0;padding:20px;border:1px solid #ccc;} input,button{margin:5px;padding:10px;}</style>";
echo "</head><body>";
echo "<h2>اختبار إضافة المنتج</h2>";

// Show available warehouses
try {
    $warehouses = Warehouse::all();
    
    echo "<h3>المخازن المتاحة:</h3>";
    echo "<ul>";
    foreach ($warehouses as $warehouse) {
        echo "<li><strong>ID:</strong> {$warehouse->id} - <strong>الاسم:</strong> {$warehouse->name} - <strong>كلمة المرور:</strong> {$warehouse->password}</li>";
        echo "<p><a href='/warehouses/{$warehouse->id}/login' target='_blank'>تسجيل دخول {$warehouse->name}</a></p>";
    }
    echo "</ul>";
    
    // Test form for first warehouse
    if ($warehouses->count() > 0) {
        $firstWarehouse = $warehouses->first();
        
        echo "<div class='form'>";
        echo "<h3>نموذج اختبار إضافة منتج لـ {$firstWarehouse->name}:</h3>";
        echo "<form method='POST' action='/warehouses/{$firstWarehouse->id}/products'>";
        echo "<input type='hidden' name='_token' value='" . csrf_token() . "'>";
        echo "<p><input type='text' name='name' placeholder='اسم المنتج' required></p>";
        echo "<p><input type='number' name='units_per_carton' placeholder='عدد الوحدات في الكرتونة' value='24' required></p>";
        echo "<p><input type='number' name='min_threshold' placeholder='الحد الأدنى' value='0'></p>";
        echo "<p><input type='number' name='cartons' placeholder='عدد الكراتين' value='1' required></p>";
        echo "<p><button type='submit'>حفظ المنتج</button></p>";
        echo "</form>";
        echo "</div>";
        
        // Session info
        echo "<h3>معلومات الجلسة:</h3>";
        echo "<ul>";
        foreach ($warehouses as $warehouse) {
            $sessionKey = "warehouse_{$warehouse->id}_auth";
            $isLoggedIn = session($sessionKey) ? 'نعم' : 'لا';
            echo "<li>مخزن {$warehouse->name} (ID: {$warehouse->id}): مسجل دخول = <strong>{$isLoggedIn}</strong></li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}

echo "<br><a href='/warehouses'>الذهاب إلى صفحة المخازن</a>";
echo "</body></html>";
?>
