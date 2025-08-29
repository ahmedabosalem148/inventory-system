<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Warehouse;

echo "<!DOCTYPE html><html><head><title>اختبار CSRF</title>";
echo "<style>body{font-family:Arial;direction:rtl;} .form{margin:20px 0;padding:20px;border:1px solid #ccc;} input,button{margin:5px;padding:10px;}</style>";
echo "</head><body>";
echo "<h2>اختبار مشكلة CSRF</h2>";

if ($_POST) {
    echo "<div style='color:green;'>";
    echo "<h3>✅ تم استقبال البيانات بنجاح!</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    echo "</div>";
}

$warehouses = Warehouse::all();

echo "<h3>معلومات CSRF:</h3>";
echo "<ul>";
echo "<li><strong>CSRF Token الحالي:</strong> " . csrf_token() . "</li>";
echo "<li><strong>Session ID:</strong> " . session()->getId() . "</li>";
echo "<li><strong>Session Driver:</strong> " . config('session.driver') . "</li>";
echo "</ul>";

echo "<h3>اختبار بدون CSRF (للتشخيص فقط):</h3>";
echo "<div class='form'>";
echo "<form method='POST'>";
echo "<p>اسم المنتج: <input type='text' name='name' value='اختبار' required></p>";
echo "<p>وحدات/كرتونة: <input type='number' name='units_per_carton' value='24' required></p>";
echo "<p>الحد الأدنى: <input type='number' name='min_threshold' value='0'></p>";
echo "<p>عدد الكراتين: <input type='number' name='cartons' value='1' required></p>";
echo "<p><button type='submit'>إرسال بدون CSRF</button></p>";
echo "</form>";
echo "</div>";

echo "<h3>اختبار مع CSRF:</h3>";
echo "<div class='form'>";
echo "<form method='POST'>";
echo "<input type='hidden' name='_token' value='" . csrf_token() . "'>";
echo "<p>اسم المنتج: <input type='text' name='name' value='اختبار مع CSRF' required></p>";
echo "<p>وحدات/كرتونة: <input type='number' name='units_per_carton' value='24' required></p>";
echo "<p>الحد الأدنى: <input type='number' name='min_threshold' value='0'></p>";
echo "<p>عدد الكراتين: <input type='number' name='cartons' value='1' required></p>";
echo "<p><button type='submit'>إرسال مع CSRF</button></p>";
echo "</form>";
echo "</div>";

// Test session functionality
echo "<h3>اختبار Session:</h3>";
$testKey = 'test_session_' . time();
session([$testKey => 'قيمة اختبار']);
$sessionValue = session($testKey);
echo "<ul>";
echo "<li><strong>تم حفظ في Session:</strong> $testKey = 'قيمة اختبار'</li>";
echo "<li><strong>تم قراءة من Session:</strong> $sessionValue</li>";
echo "<li><strong>حالة Session:</strong> " . (session()->isStarted() ? 'مفعل' : 'غير مفعل') . "</li>";
echo "</ul>";

echo "<p><a href='/warehouses'>الذهاب للمخازن</a></p>";
echo "</body></html>";
?>
