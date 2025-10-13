<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing Customer Query...\n\n";

// Test 1: Direct count
$totalCustomers = App\Models\Customer::count();
echo "✅ Total customers in database: {$totalCustomers}\n\n";

// Test 2: Get all customers
$customers = App\Models\Customer::all();
echo "📋 Customer List:\n";
foreach ($customers->take(5) as $customer) {
    echo "  - ID: {$customer->id}, Name: {$customer->name}, Active: " . ($customer->is_active ? 'Yes' : 'No') . "\n";
}

// Test 3: Paginated query (like API)
echo "\n📄 Paginated Query (like API):\n";
$paginated = App\Models\Customer::orderBy('name', 'asc')->paginate(15);
echo "  Total: {$paginated->total()}\n";
echo "  Per Page: {$paginated->perPage()}\n";
echo "  Current Page: {$paginated->currentPage()}\n";
echo "  Items in current page: {$paginated->count()}\n";

// Test 4: Check CustomerResource
echo "\n🔄 Testing CustomerResource:\n";
$firstCustomer = App\Models\Customer::first();
if ($firstCustomer) {
    $resource = new App\Http\Resources\Api\V1\CustomerResource($firstCustomer);
    $array = $resource->toArray(request());
    echo "  Resource fields: " . implode(', ', array_keys($array)) . "\n";
    echo "  Sample data: " . json_encode($array, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
}

// Test 5: Simulate API request
echo "\n🌐 Simulating API Request:\n";
$request = Illuminate\Http\Request::create('/api/v1/customers', 'GET', [
    'page' => 1,
    'per_page' => 10,
    'sort_by' => 'name',
    'sort_order' => 'asc'
]);

$query = App\Models\Customer::query();
$query->orderBy('name', 'asc');
$result = $query->paginate(10);

echo "  Query Result Total: {$result->total()}\n";
echo "  Query Result Items: {$result->count()}\n";

if ($result->total() === 0) {
    echo "\n⚠️ WARNING: Query returns 0 results!\n";
    echo "  This suggests a global scope or middleware is filtering the data.\n";
}
