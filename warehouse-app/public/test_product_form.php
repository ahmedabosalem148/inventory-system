<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Warehouse;

echo "<!DOCTYPE html><html><head><title>اختبار نموذج إضافة المنتج</title>";
echo "<style>body{font-family:Arial;direction:rtl;} form{margin:20px 0;padding:20px;border:1px solid #ccc;} input,select,button{margin:5px;padding:10px;width:200px;}</style>";
echo "</head><body>";
echo "<h2>اختبار نموذج إضافة المنتج</h2>";

if ($_POST) {
    echo "<h3>البيانات المُرسلة:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
}

$warehouses = Warehouse::all();

echo "<h3>النموذج الأصلي (مع middleware):</h3>";
if ($warehouses->count() > 0) {
    $warehouse = $warehouses->first();
    echo "<form method='POST' action='/warehouses/{$warehouse->id}/products'>";
    echo "<input type='hidden' name='_token' value='" . csrf_token() . "'>";
    echo "<p>المخزن: {$warehouse->name}</p>";
    echo "<p><input type='text' name='name' placeholder='اسم المنتج' value='منتج تجريبي' required></p>";
    echo "<p><input type='number' name='units_per_carton' placeholder='وحدات/كرتونة' value='24' required></p>";
    echo "<p><input type='number' name='min_threshold' placeholder='الحد الأدنى' value='0'></p>";
    echo "<p><input type='number' name='cartons' placeholder='عدد الكراتين' value='1' required></p>";
    echo "<p><button type='submit'>حفظ المنتج (الطريقة الأصلية)</button></p>";
    echo "</form>";
}

echo "<h3>النموذج التجريبي (بدون middleware):</h3>";
if ($warehouses->count() > 0) {
    $warehouse = $warehouses->first();
    echo "<form method='POST' action='/test/warehouses/{$warehouse->id}/products'>";
    echo "<input type='hidden' name='_token' value='" . csrf_token() . "'>";
    echo "<p>المخزن: {$warehouse->name}</p>";
    echo "<p><input type='text' name='name' placeholder='اسم المنتج' value='منتج تجريبي 2' required></p>";
    echo "<p><input type='number' name='units_per_carton' placeholder='وحدات/كرتونة' value='24' required></p>";
    echo "<p><input type='number' name='min_threshold' placeholder='الحد الأدنى' value='0'></p>";
    echo "<p><input type='number' name='cartons' placeholder='عدد الكراتين' value='1' required></p>";
    echo "<p><button type='submit'>حفظ المنتج (تجريبي)</button></p>";
    echo "</form>";
}

// Show session info
echo "<h3>معلومات الجلسة:</h3>";
echo "<ul>";
foreach ($warehouses as $warehouse) {
    $sessionKey = "warehouse_{$warehouse->id}_auth";
    $isAuth = session($sessionKey) ? 'نعم' : 'لا';
    echo "<li>{$warehouse->name}: مسجل دخول = <strong>{$isAuth}</strong></li>";
}
echo "</ul>";

echo "<p><a href='/warehouses'>الذهاب للمخازن</a> | <a href='/update_passwords.php'>تحديث كلمات المرور</a></p>";
echo "</body></html>";
?>
