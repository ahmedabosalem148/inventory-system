<?php
// التحقق من كلمات مرور المخازن

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Warehouse;

echo "🔑 كلمات مرور المخازن:\n";
echo "========================\n\n";

$warehouses = Warehouse::all();

foreach ($warehouses as $warehouse) {
    echo "📦 {$warehouse->name}\n";
    echo "   ID: {$warehouse->id}\n";
    echo "   كلمة المرور: {$warehouse->password}\n";
    echo "   Hash: " . ($warehouse->password ? hash('sha256', $warehouse->password) : 'غير محدد') . "\n";
    echo "   ========================\n\n";
}

echo "💡 جرب كلمات المرور دي:\n";
foreach ($warehouses as $warehouse) {
    echo "   {$warehouse->name}: {$warehouse->password}\n";
}
?>
