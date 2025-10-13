<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing Policy Bypass...\n\n";

// Get test user
$user = App\Models\User::where('email', 'test@example.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "👤 User: {$user->name}\n";
echo "Environment: " . app()->environment() . "\n\n";

// Test Gate::before
echo "🔐 Testing Gate Authorization:\n";

// Create a fake customer
$customer = App\Models\Customer::first();

if ($customer) {
    // Test viewAny
    $canViewAny = Gate::forUser($user)->allows('viewAny', App\Models\Customer::class);
    echo "Can viewAny Customer: " . ($canViewAny ? 'YES ✅' : 'NO ❌') . "\n";
    
    // Test view
    $canView = Gate::forUser($user)->allows('view', $customer);
    echo "Can view Customer: " . ($canView ? 'YES ✅' : 'NO ❌') . "\n";
    
    // Test create
    $canCreate = Gate::forUser($user)->allows('create', App\Models\Customer::class);
    echo "Can create Customer: " . ($canCreate ? 'YES ✅' : 'NO ❌') . "\n";
}

// Test direct query
echo "\n📊 Testing Direct Query:\n";
$count = App\Models\Customer::count();
echo "Total customers in DB: {$count}\n";

// Test with policy check
echo "\n🔄 Testing Query with Authorization:\n";
try {
    // This should work now with Gate::before
    $customers = App\Models\Customer::paginate(10);
    echo "Paginated query result: {$customers->total()} customers\n";
    echo "Items in page: {$customers->count()}\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
