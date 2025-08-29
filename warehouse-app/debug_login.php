<?php
// صفحة تشخيص المشاكل

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Warehouse;

echo "🔧 تشخيص مشاكل تسجيل الدخول\n";
echo "============================\n\n";

// 1. التحقق من المخازن
echo "1️⃣ المخازن الموجودة:\n";
$warehouses = Warehouse::all();
foreach ($warehouses as $warehouse) {
    echo "   📦 {$warehouse->name} (ID: {$warehouse->id})\n";
}
echo "\n";

// 2. التحقق من كلمات المرور
echo "2️⃣ اختبار كلمات المرور:\n";
$passwords = [
    'العتبة' => 'ataba123',
    'امبابة' => 'imbaba123', 
    'المصنع' => 'factory123',
    'test' => '1234'
];

foreach ($warehouses as $warehouse) {
    echo "   📦 {$warehouse->name}:\n";
    foreach ($passwords as $label => $password) {
        $match = password_verify($password, $warehouse->password);
        $status = $match ? "✅" : "❌";
        echo "      {$status} {$password} ({$label})\n";
    }
    echo "\n";
}

// 3. التحقق من الـ session
echo "3️⃣ حالة الـ session:\n";
session_start();
echo "   Session ID: " . session_id() . "\n";
echo "   Session Data: " . print_r($_SESSION, true) . "\n\n";

// 4. اختبار تشفير جديد
echo "4️⃣ اختبار تشفير 1234:\n";
$hash1234 = password_hash('1234', PASSWORD_DEFAULT);
echo "   Hash جديد: {$hash1234}\n";
echo "   التحقق: " . (password_verify('1234', $hash1234) ? "✅ صحيح" : "❌ خطأ") . "\n\n";

echo "💡 كلمات المرور الصحيحة:\n";
echo "   العتبة: ataba123\n";
echo "   امبابة: imbaba123\n";
echo "   المصنع: factory123\n";
?>
