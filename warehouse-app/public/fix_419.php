<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Warehouse;

echo "<!DOCTYPE html><html><head><title>إصلاح مشكلة 419</title>";
echo "<style>body{font-family:Arial;direction:rtl;} .form{margin:20px 0;padding:20px;border:2px solid #007bff;} .success{color:green;} .error{color:red;} input,button{margin:5px;padding:10px;width:200px;}</style>";
echo "</head><body>";
echo "<h2>إصلاح مشكلة 419 - Page Expired</h2>";

if ($_POST) {
    echo "<div class='success'>";
    echo "<h3>✅ تم استقبال البيانات بنجاح!</h3>";
    echo "<p>لا توجد مشكلة 419 في هذه الصفحة</p>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    echo "</div>";
}

$warehouses = Warehouse::all();
$warehouse = $warehouses->first();

echo "<h3>📊 تشخيص النظام:</h3>";
echo "<ul>";
echo "<li><strong>Session Driver:</strong> " . config('session.driver') . "</li>";
echo "<li><strong>CSRF Token:</strong> " . csrf_token() . "</li>";
echo "<li><strong>Session ID:</strong> " . session()->getId() . "</li>";
echo "<li><strong>Session Started:</strong> " . (session()->isStarted() ? 'نعم ✅' : 'لا ❌') . "</li>";
echo "</ul>";

echo "<h3>🧪 الاختبارات:</h3>";

// Test 1: No CSRF
echo "<div class='form'>";
echo "<h4>اختبار 1: بدون CSRF (مضمون العمل)</h4>";
echo "<form method='POST' action='/test-no-csrf/warehouses/{$warehouse->id}/products'>";
echo "<p>اسم المنتج: <input type='text' name='name' value='منتج بدون CSRF' required></p>";
echo "<p>وحدات/كرتونة: <input type='number' name='units_per_carton' value='24' required></p>";
echo "<p>الحد الأدنى: <input type='number' name='min_threshold' value='0'></p>";
echo "<p>عدد الكراتين: <input type='number' name='cartons' value='1' required></p>";
echo "<p><button type='submit' style='background:green;color:white;'>حفظ بدون CSRF</button></p>";
echo "</form>";
echo "</div>";

// Test 2: With CSRF
echo "<div class='form'>";
echo "<h4>اختبار 2: مع CSRF (قد يعطي 419)</h4>";
echo "<form method='POST' action='/test/warehouses/{$warehouse->id}/products'>";
echo "<input type='hidden' name='_token' value='" . csrf_token() . "'>";
echo "<p>اسم المنتج: <input type='text' name='name' value='منتج مع CSRF' required></p>";
echo "<p>وحدات/كرتونة: <input type='number' name='units_per_carton' value='24' required></p>";
echo "<p>الحد الأدنى: <input type='number' name='min_threshold' value='0'></p>";
echo "<p>عدد الكراتين: <input type='number' name='cartons' value='1' required></p>";
echo "<p><button type='submit' style='background:orange;color:white;'>حفظ مع CSRF</button></p>";
echo "</form>";
echo "</div>";

// Test 3: Original form (with middleware)
echo "<div class='form'>";
echo "<h4>اختبار 3: النموذج الأصلي (يتطلب تسجيل دخول + CSRF)</h4>";
echo "<p style='color:red;'><strong>ملاحظة:</strong> يجب تسجيل الدخول في المخزن أولاً!</p>";
echo "<p><a href='/warehouses/{$warehouse->id}/login' target='_blank'>تسجيل دخول مخزن {$warehouse->name}</a> (كلمة المرور: {$warehouse->password})</p>";
echo "<form method='POST' action='/warehouses/{$warehouse->id}/products'>";
echo "<input type='hidden' name='_token' value='" . csrf_token() . "'>";
echo "<p>اسم المنتج: <input type='text' name='name' value='منتج أصلي' required></p>";
echo "<p>وحدات/كرتونة: <input type='number' name='units_per_carton' value='24' required></p>";
echo "<p>الحد الأدنى: <input type='number' name='min_threshold' value='0'></p>";
echo "<p>عدد الكراتين: <input type='number' name='cartons' value='1' required></p>";
echo "<p><button type='submit' style='background:blue;color:white;'>حفظ (النموذج الأصلي)</button></p>";
echo "</form>";
echo "</div>";

echo "<h3>📋 التعليمات:</h3>";
echo "<ol>";
echo "<li><strong>جرب الاختبار 1 أولاً</strong> - يجب أن يعمل بدون مشاكل</li>";
echo "<li><strong>إذا عمل الاختبار 1</strong>, جرب الاختبار 2</li>";
echo "<li><strong>للاختبار 3</strong>, سجل دخول المخزن أولاً ثم جرب</li>";
echo "</ol>";

echo "<p><a href='/test_csrf.php'>اختبار CSRF</a> | <a href='/warehouses'>الذهاب للمخازن</a></p>";
echo "</body></html>";
?>
