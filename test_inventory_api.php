<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing Inventory Summary...\n";
    $service = new \App\Services\InventoryService();
    $summary = $service->getInventorySummary();
    echo "Summary: " . json_encode($summary, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "Testing Low Stock...\n";
    $branches = \App\Models\Branch::all();
    foreach ($branches as $branch) {
        $lowStock = $service->getProductsBelowMinQuantity($branch->id);
        echo "Branch {$branch->name}: {$lowStock->count()} low stock items\n";
    }
    
    echo "\n✅ All tests passed!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
