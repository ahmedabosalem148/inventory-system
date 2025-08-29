<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Warehouse;

echo "<!DOCTYPE html><html><head><title>تشخيص مشكلة إضافة المنتج من المخزن</title>";
echo "<style>body{font-family:Arial;direction:rtl;} .box{margin:20px 0;padding:20px;border:2px solid #007bff;} .success{color:green;} .error{color:red;} .warning{color:orange;} input,button{margin:5px;padding:10px;}</style>";
echo "</head><body>";
echo "<h2>تشخيص مشكلة إضافة المنتج من المخزن</h2>";

$warehouses = Warehouse::all();

echo "<h3>📊 حالة المخازن والجلسات:</h3>";
echo "<div class='box'>";
foreach ($warehouses as $warehouse) {
    $sessionKey = "warehouse_{$warehouse->id}_auth";
    $isAuth = session($sessionKey);
    $statusColor = $isAuth ? 'success' : 'error';
    $statusText = $isAuth ? '✅ مسجل دخول' : '❌ غير مسجل دخول';
    
    echo "<div>";
    echo "<h4>{$warehouse->name} (ID: {$warehouse->id})</h4>";
    echo "<ul>";
    echo "<li><strong>كلمة المرور:</strong> {$warehouse->password}</li>";
    echo "<li><strong>Session Key:</strong> {$sessionKey}</li>";
    echo "<li><strong>حالة التسجيل:</strong> <span class='{$statusColor}'>{$statusText}</span></li>";
    echo "<li><strong>Session Value:</strong> " . ($isAuth ? 'true' : 'false') . "</li>";
    echo "</ul>";
    
    if (!$isAuth) {
        echo "<p class='warning'>⚠️ يجب تسجيل الدخول أولاً!</p>";
        echo "<p><a href='/warehouses/{$warehouse->id}/login' target='_blank' style='background:blue;color:white;padding:10px;text-decoration:none;'>تسجيل دخول {$warehouse->name}</a></p>";
    } else {
        echo "<p class='success'>✅ يمكن إضافة منتج الآن</p>";
        echo "<p><a href='/warehouses/{$warehouse->id}/products/create' target='_blank' style='background:green;color:white;padding:10px;text-decoration:none;'>إضافة منتج لـ {$warehouse->name}</a></p>";
    }
    echo "<hr>";
    echo "</div>";
}
echo "</div>";

// Test form for authenticated warehouse
echo "<h3>🧪 اختبار إضافة منتج:</h3>";
$authWarehouse = null;
foreach ($warehouses as $warehouse) {
    if (session("warehouse_{$warehouse->id}_auth")) {
        $authWarehouse = $warehouse;
        break;
    }
}

if ($authWarehouse) {
    echo "<div class='box' style='border-color:green;'>";
    echo "<h4>المخزن المُسجل دخوله: {$authWarehouse->name}</h4>";
    echo "<form method='POST' action='/warehouses/{$authWarehouse->id}/products'>";
    echo "<input type='hidden' name='_token' value='" . csrf_token() . "'>";
    echo "<p>اسم المنتج: <input type='text' name='name' value='منتج تجريبي' required></p>";
    echo "<p>وحدات/كرتونة: <input type='number' name='units_per_carton' value='24' required></p>";
    echo "<p>الحد الأدنى: <input type='number' name='min_threshold' value='10'></p>";
    echo "<p>عدد الكراتين: <input type='number' name='cartons' value='2' required></p>";
    echo "<p><button type='submit' style='background:green;color:white;padding:15px;'>حفظ المنتج (الطريقة الأصلية)</button></p>";
    echo "</form>";
    echo "</div>";
    
    // Test without middleware
    echo "<div class='box' style='border-color:orange;'>";
    echo "<h4>اختبار بدون middleware (للمقارنة):</h4>";
    echo "<form method='POST' action='/test-no-csrf/warehouses/{$authWarehouse->id}/products'>";
    echo "<p>اسم المنتج: <input type='text' name='name' value='منتج بدون middleware' required></p>";
    echo "<p>وحدات/كرتونة: <input type='number' name='units_per_carton' value='24' required></p>";
    echo "<p>الحد الأدنى: <input type='number' name='min_threshold' value='10'></p>";
    echo "<p>عدد الكراتين: <input type='number' name='cartons' value='2' required></p>";
    echo "<p><button type='submit' style='background:orange;color:white;padding:15px;'>حفظ بدون middleware</button></p>";
    echo "</form>";
    echo "</div>";
    
} else {
    echo "<div class='box' style='border-color:red;'>";
    echo "<p class='error'>❌ لا توجد مخازن مُسجل دخولها حالياً</p>";
    echo "<p>يجب تسجيل الدخول في أحد المخازن أولاً لاختبار إضافة المنتج</p>";
    echo "</div>";
}

// Manual login form
echo "<h3>🔐 تسجيل دخول سريع:</h3>";
echo "<div class='box'>";
foreach ($warehouses as $warehouse) {
    echo "<div style='margin:10px 0;'>";
    echo "<form method='POST' action='/warehouses/{$warehouse->id}/login' style='display:inline;'>";
    echo "<input type='hidden' name='_token' value='" . csrf_token() . "'>";
    echo "<input type='hidden' name='password' value='{$warehouse->password}'>";
    echo "<button type='submit' style='background:blue;color:white;padding:10px;'>تسجيل دخول {$warehouse->name}</button>";
    echo "</form>";
    echo " (كلمة المرور: {$warehouse->password})";
    echo "</div>";
}
echo "</div>";

echo "<h3>📋 الخطوات:</h3>";
echo "<ol>";
echo "<li><strong>سجل دخول</strong> في أحد المخازن أعلاه</li>";
echo "<li><strong>حدث الصفحة</strong> لرؤية النماذج</li>";
echo "<li><strong>جرب إضافة المنتج</strong> باستخدام النموذج الأصلي</li>";
echo "<li><strong>إذا لم يعمل</strong>, جرب النموذج بدون middleware للمقارنة</li>";
echo "</ol>";

echo "<p><a href='/warehouses'>الذهاب للمخازن</a> | <a href='/fix_419.php'>اختبار 419</a></p>";
echo "</body></html>";
?>
