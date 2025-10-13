<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking Customer branch assignments:\n\n";

$customers = App\Models\Customer::select('id', 'name', 'branch_id')->limit(10)->get();

foreach ($customers as $customer) {
    echo "Customer {$customer->id} ({$customer->name}): branch_id = " . ($customer->branch_id ?? 'NULL') . "\n";
}

echo "\n📊 Branch Statistics:\n";
echo "Total customers: " . App\Models\Customer::count() . "\n";
echo "Customers with branch_id = 1: " . App\Models\Customer::where('branch_id', 1)->count() . "\n";
echo "Customers with branch_id = NULL: " . App\Models\Customer::whereNull('branch_id')->count() . "\n";
echo "Customers with branch_id != 1 (or not NULL): " . App\Models\Customer::where('branch_id', '!=', 1)->orWhereNotNull('branch_id')->where('branch_id', '!=', 1)->count() . "\n";

// Check if branch_id column exists in customers table
echo "\n🗃️ Table Structure:\n";
$columns = DB::select("PRAGMA table_info(customers)");
$hasbranchId = false;
foreach ($columns as $column) {
    if ($column->name === 'branch_id') {
        $hasbranchId = true;
        echo "✅ branch_id column exists (type: {$column->type})\n";
        break;
    }
}
if (!$hasbranchId) {
    echo "❌ branch_id column does NOT exist in customers table!\n";
}