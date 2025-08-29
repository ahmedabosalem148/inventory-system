<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

echo "<!DOCTYPE html><html><head><title>إصلاح جدول المنتجات</title>";
echo "<style>body{font-family:Arial;direction:rtl;} .success{color:green;background:#d4edda;padding:15px;margin:10px 0;border-radius:5px;} .error{color:red;background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;} .info{background:#d1ecf1;padding:15px;margin:10px 0;border-radius:5px;}</style>";
echo "</head><body>";
echo "<h2>🔧 إصلاح جدول المنتجات</h2>";

try {
    // Check current table structure
    $columns = DB::select("DESCRIBE products");
    
    echo "<div class='info'>";
    echo "<h3>📋 هيكل الجدول الحالي:</h3>";
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>العمود</th><th>النوع</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column->Field}</td>";
        echo "<td>{$column->Type}</td>";
        echo "<td>{$column->Null}</td>";
        echo "<td>{$column->Key}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    $hasNameAr = collect($columns)->contains('Field', 'name_ar');
    $hasName = collect($columns)->contains('Field', 'name');
    $hasCartonSize = collect($columns)->contains('Field', 'carton_size');
    $hasUnitsPerCarton = collect($columns)->contains('Field', 'units_per_carton');
    
    echo "<div class='info'>";
    echo "<h3>🔍 تحليل الأعمدة:</h3>";
    echo "<ul>";
    echo "<li>name_ar موجود: " . ($hasNameAr ? "✅" : "❌") . "</li>";
    echo "<li>name موجود: " . ($hasName ? "✅" : "❌") . "</li>";
    echo "<li>carton_size موجود: " . ($hasCartonSize ? "✅" : "❌") . "</li>";
    echo "<li>units_per_carton موجود: " . ($hasUnitsPerCarton ? "✅" : "❌") . "</li>";
    echo "</ul>";
    echo "</div>";
    
    if (isset($_POST['fix_table'])) {
        echo "<div class='info'><h3>🔧 تطبيق الإصلاحات...</h3></div>";
        
        // Step 1: Rename name to name_ar if needed
        if ($hasName && !$hasNameAr) {
            DB::statement("ALTER TABLE products CHANGE name name_ar VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL");
            echo "<div class='success'>✅ تم تغيير name إلى name_ar</div>";
        }
        
        // Step 2: Rename units_per_carton to carton_size if needed  
        if ($hasUnitsPerCarton && !$hasCartonSize) {
            DB::statement("ALTER TABLE products CHANGE units_per_carton carton_size INT UNSIGNED NOT NULL");
            echo "<div class='success'>✅ تم تغيير units_per_carton إلى carton_size</div>";
        }
        
        // Step 3: Add missing columns
        if (!$hasNameAr && !$hasName) {
            DB::statement("ALTER TABLE products ADD name_ar VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL AFTER id");
            echo "<div class='success'>✅ تم إضافة عمود name_ar</div>";
        }
        
        if (!$hasCartonSize && !$hasUnitsPerCarton) {
            DB::statement("ALTER TABLE products ADD carton_size INT UNSIGNED NOT NULL AFTER name_ar");
            echo "<div class='success'>✅ تم إضافة عمود carton_size</div>";
        }
        
        echo "<div class='success'><h3>🎉 تم إصلاح الجدول بنجاح!</h3></div>";
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
    }
    
    if (!$hasNameAr || !$hasCartonSize) {
        echo "<form method='POST'>";
        echo "<input type='hidden' name='fix_table' value='1'>";
        echo "<button type='submit' style='background:red;color:white;padding:15px;font-size:16px;'>🔧 إصلاح الجدول الآن</button>";
        echo "</form>";
    } else {
        echo "<div class='success'><h3>✅ الجدول صحيح بالفعل!</h3></div>";
        echo "<p><a href='/test_fixed_product.php' style='background:green;color:white;padding:15px;text-decoration:none;border-radius:5px;'>اختبار إضافة المنتج</a></p>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ خطأ:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
