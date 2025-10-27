<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductBranchStock;
use Illuminate\Support\Facades\DB;

echo "Updating stock quantities to realistic values...\n";

// تحديث الكميات بقيم واقعية
$stockRecords = [
    // لمبة LED 7 وات
    ['product_id' => 1, 'branch_id' => 1, 'current_stock' => 45, 'min_qty' => 50],  // low
    ['product_id' => 1, 'branch_id' => 2, 'current_stock' => 15, 'min_qty' => 50],  // critical
    ['product_id' => 1, 'branch_id' => 3, 'current_stock' => 0, 'min_qty' => 50],   // out of stock
    
    // لمبة LED 12 وات
    ['product_id' => 2, 'branch_id' => 1, 'current_stock' => 55, 'min_qty' => 40],  // normal
    ['product_id' => 2, 'branch_id' => 2, 'current_stock' => 10, 'min_qty' => 40],  // critical
    ['product_id' => 2, 'branch_id' => 3, 'current_stock' => 35, 'min_qty' => 40],  // low
    
    // مفتاح إضاءة مفرد
    ['product_id' => 3, 'branch_id' => 1, 'current_stock' => 120, 'min_qty' => 100], // normal
    ['product_id' => 3, 'branch_id' => 2, 'current_stock' => 40, 'min_qty' => 100],  // critical
    ['product_id' => 3, 'branch_id' => 3, 'current_stock' => 85, 'min_qty' => 100],  // low
    
    // مفتاح إضاءة مزدوج
    ['product_id' => 4, 'branch_id' => 1, 'current_stock' => 0, 'min_qty' => 80],   // out of stock
    ['product_id' => 4, 'branch_id' => 2, 'current_stock' => 95, 'min_qty' => 80],  // normal
    ['product_id' => 4, 'branch_id' => 3, 'current_stock' => 25, 'min_qty' => 80],  // critical
    
    // سلك كهرباء 1.5 ملم
    ['product_id' => 5, 'branch_id' => 1, 'current_stock' => 180, 'min_qty' => 200], // low
    ['product_id' => 5, 'branch_id' => 2, 'current_stock' => 250, 'min_qty' => 200], // normal
    ['product_id' => 5, 'branch_id' => 3, 'current_stock' => 50, 'min_qty' => 200],  // critical
    
    // سلك كهرباء 2.5 ملم
    ['product_id' => 6, 'branch_id' => 1, 'current_stock' => 0, 'min_qty' => 150],   // out of stock
    ['product_id' => 6, 'branch_id' => 2, 'current_stock' => 140, 'min_qty' => 150], // low
    ['product_id' => 6, 'branch_id' => 3, 'current_stock' => 200, 'min_qty' => 150], // normal
    
    // قاطع كهربائي 16 أمبير
    ['product_id' => 7, 'branch_id' => 1, 'current_stock' => 28, 'min_qty' => 30],  // low
    ['product_id' => 7, 'branch_id' => 2, 'current_stock' => 45, 'min_qty' => 30],  // normal
    ['product_id' => 7, 'branch_id' => 3, 'current_stock' => 12, 'min_qty' => 30],  // critical
    
    // قاطع كهربائي 32 أمبير
    ['product_id' => 8, 'branch_id' => 1, 'current_stock' => 30, 'min_qty' => 25],  // normal
    ['product_id' => 8, 'branch_id' => 2, 'current_stock' => 0, 'min_qty' => 25],   // out of stock
    ['product_id' => 8, 'branch_id' => 3, 'current_stock' => 22, 'min_qty' => 25],  // low
];

foreach ($stockRecords as $record) {
    ProductBranchStock::where('product_id', $record['product_id'])
        ->where('branch_id', $record['branch_id'])
        ->update([
            'current_stock' => $record['current_stock'],
            'min_qty' => $record['min_qty']
        ]);
    
    echo "✓ Updated Product {$record['product_id']} - Branch {$record['branch_id']}: {$record['current_stock']}\n";
}

echo "\n✅ Stock updated successfully!\n";

// عرض الإحصائيات
$summary = ProductBranchStock::selectRaw("
    SUM(CASE WHEN current_stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
    SUM(CASE WHEN current_stock > 0 AND current_stock < min_qty * 0.5 THEN 1 ELSE 0 END) as critical,
    SUM(CASE WHEN current_stock >= min_qty * 0.5 AND current_stock <= min_qty THEN 1 ELSE 0 END) as low,
    SUM(CASE WHEN current_stock > min_qty THEN 1 ELSE 0 END) as normal
")->first();

echo "\n📊 Stock Summary:\n";
echo "   Out of Stock: {$summary->out_of_stock}\n";
echo "   Critical: {$summary->critical}\n";
echo "   Low: {$summary->low}\n";
echo "   Normal: {$summary->normal}\n";
