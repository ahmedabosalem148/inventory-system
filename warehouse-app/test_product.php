<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

try {
    $product = Product::create([
        'name_ar' => 'منتج اختبار ' . time(),
        'carton_size' => 24,
        'active' => true
    ]);
    
    echo "✅ نجح إنشاء المنتج!\n";
    echo "ID: {$product->id}\n";
    echo "الاسم: {$product->name_ar}\n";
    echo "حجم الكرتون: {$product->carton_size}\n";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
}
?>
