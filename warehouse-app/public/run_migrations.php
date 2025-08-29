<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

echo "<!DOCTYPE html><html><head><title>تشغيل Migrations</title>";
echo "<style>body{font-family:Arial;direction:rtl;} .success{color:green;background:#d4edda;padding:15px;margin:10px 0;border-radius:5px;} .error{color:red;background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;} .info{background:#d1ecf1;padding:15px;margin:10px 0;border-radius:5px;}</style>";
echo "</head><body>";
echo "<h2>🔧 تشغيل Migrations</h2>";

try {
    // Check existing tables
    $tables = DB::select("SHOW TABLES");
    $tableNames = array_map(function($table) {
        return array_values((array)$table)[0];
    }, $tables);
    
    echo "<div class='info'>";
    echo "<h3>📋 الجداول الموجودة:</h3>";
    echo "<ul>";
    foreach ($tableNames as $table) {
        echo "<li>{$table}</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    $hasInventoryMovements = in_array('inventory_movements', $tableNames);
    
    if (isset($_POST['run_migrations'])) {
        echo "<div class='info'><h3>🔧 تشغيل Migrations...</h3></div>";
        
        // Create inventory_movements table if it doesn't exist
        if (!$hasInventoryMovements) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
                $table->enum('movement_type', ['add', 'withdraw', 'adjust']);
                $table->integer('quantity');
                $table->integer('cartons')->nullable();
                $table->text('notes')->nullable();
                $table->string('created_by')->nullable();
                $table->timestamps();
                
                $table->index(['product_id', 'warehouse_id']);
                $table->index(['movement_type']);
                $table->index(['created_at']);
            });
            echo "<div class='success'>✅ تم إنشاء جدول inventory_movements</div>";
        } else {
            echo "<div class='info'>ℹ️ جدول inventory_movements موجود بالفعل</div>";
        }
        
        echo "<div class='success'><h3>🎉 تم تشغيل جميع المigrations بنجاح!</h3></div>";
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
    }
    
    if (!$hasInventoryMovements) {
        echo "<form method='POST'>";
        echo "<input type='hidden' name='run_migrations' value='1'>";
        echo "<button type='submit' style='background:blue;color:white;padding:15px;font-size:16px;'>🚀 تشغيل Migrations الناقصة</button>";
        echo "</form>";
    } else {
        echo "<div class='success'><h3>✅ جميع الجداول موجودة!</h3></div>";
        echo "<p><a href='/fix_products_table.php' style='background:orange;color:white;padding:15px;text-decoration:none;border-radius:5px;'>إصلاح جدول المنتجات</a></p>";
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
