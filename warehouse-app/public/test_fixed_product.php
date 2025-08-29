<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Warehouse;
use App\Models\Product;

echo "<!DOCTYPE html><html><head><title>اختبار إصلاح إضافة المنتج</title>";
echo "<style>body{font-family:Arial;direction:rtl;} .success{color:green;background:#d4edda;padding:15px;margin:10px 0;border-radius:5px;} .error{color:red;background:#f8d7da;padding:15px;margin:10px 0;border-radius:5px;} .info{background:#d1ecf1;padding:15px;margin:10px 0;border-radius:5px;} input,button{margin:5px;padding:10px;}</style>";
echo "</head><body>";
echo "<h2>🔧 اختبار إصلاح إضافة المنتج</h2>";

if ($_POST && isset($_POST['test_create'])) {
    echo "<div class='info'><h3>🧪 اختبار إنشاء منتج مباشر:</h3></div>";
    
    try {
        $productData = [
            'name_ar' => $_POST['name'],
            'carton_size' => (int)$_POST['units_per_carton'],
            'active' => true
        ];
        
        echo "<p><strong>البيانات:</strong> " . json_encode($productData, JSON_UNESCAPED_UNICODE) . "</p>";
        
        $product = Product::create($productData);
        
        echo "<div class='success'>";
        echo "<h4>✅ نجح إنشاء المنتج!</h4>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> {$product->id}</li>";
        echo "<li><strong>الاسم (name_ar):</strong> {$product->name_ar}</li>";
        echo "<li><strong>الاسم (accessor):</strong> {$product->name}</li>";
        echo "<li><strong>حجم الكرتون:</strong> {$product->carton_size}</li>";
        echo "<li><strong>وحدات/كرتون (accessor):</strong> {$product->units_per_carton}</li>";
        echo "</ul>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h4>❌ فشل إنشاء المنتج:</h4>";
        echo "<p><strong>الخطأ:</strong> " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}

$warehouses = Warehouse::all();

echo "<div class='info'>";
echo "<h3>📋 حالة النظام:</h3>";
echo "<ul>";
echo "<li><strong>عدد المخازن:</strong> " . $warehouses->count() . "</li>";
echo "<li><strong>عدد المنتجات:</strong> " . Product::count() . "</li>";
echo "</ul>";
echo "</div>";

// Test form for direct product creation
echo "<h3>🧪 اختبار إنشاء منتج مباشر:</h3>";
echo "<form method='POST'>";
echo "<input type='hidden' name='test_create' value='1'>";
echo "<p>اسم المنتج: <input type='text' name='name' value='منتج اختبار " . time() . "' required></p>";
echo "<p>وحدات/كرتونة: <input type='number' name='units_per_carton' value='24' required></p>";
echo "<p><button type='submit' style='background:green;color:white;padding:15px;'>إنشاء منتج للاختبار</button></p>";
echo "</form>";

// Test form for warehouse product creation
if ($warehouses->count() > 0) {
    $warehouse = $warehouses->first();
    
    echo "<h3>🏪 اختبار إضافة منتج للمخزن:</h3>";
    echo "<p><strong>المخزن:</strong> {$warehouse->name} (كلمة المرور: {$warehouse->password})</p>";
    
    // Check if logged in
    $isLoggedIn = session("warehouse_{$warehouse->id}_auth");
    if (!$isLoggedIn) {
        echo "<div class='error'>";
        echo "<p>❌ غير مسجل دخول في هذا المخزن</p>";
        echo "<form method='POST' action='/warehouses/{$warehouse->id}/login' style='display:inline;'>";
        echo "<input type='hidden' name='_token' value='" . csrf_token() . "'>";
        echo "<input type='hidden' name='password' value='{$warehouse->password}'>";
        echo "<button type='submit' style='background:blue;color:white;padding:10px;'>تسجيل دخول {$warehouse->name}</button>";
        echo "</form>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<p>✅ مسجل دخول في المخزن</p>";
        echo "<form method='POST' action='/warehouses/{$warehouse->id}/products'>";
        echo "<input type='hidden' name='_token' value='" . csrf_token() . "'>";
        echo "<p>اسم المنتج: <input type='text' name='name' value='منتج مخزن " . time() . "' required></p>";
        echo "<p>وحدات/كرتونة: <input type='number' name='units_per_carton' value='24' required></p>";
        echo "<p>الحد الأدنى: <input type='number' name='min_threshold' value='10'></p>";
        echo "<p>عدد الكراتين: <input type='number' name='cartons' value='2' required></p>";
        echo "<p><button type='submit' style='background:green;color:white;padding:15px;'>إضافة للمخزن (الطريقة الأصلية)</button></p>";
        echo "</form>";
        echo "</div>";
    }
}

// Show existing products
echo "<h3>📦 المنتجات الموجودة:</h3>";
$products = Product::orderBy('id', 'desc')->take(10)->get();
if ($products->count() > 0) {
    echo "<table border='1' style='border-collapse:collapse;width:100%;'>";
    echo "<tr style='background:#f8f9fa;'><th>ID</th><th>الاسم</th><th>حجم الكرتون</th><th>تاريخ الإنشاء</th></tr>";
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>{$product->id}</td>";
        echo "<td>{$product->name_ar}</td>";
        echo "<td>{$product->carton_size}</td>";
        echo "<td>{$product->created_at->format('Y-m-d H:i')}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>لا توجد منتجات</p>";
}

echo "<p><a href='/debug_warehouse_auth.php'>تشخيص المخازن</a> | <a href='/view_logs.php'>عرض السجلات</a> | <a href='/warehouses'>الذهاب للمخازن</a></p>";
echo "</body></html>";
?>
