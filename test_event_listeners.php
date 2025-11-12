<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Event Listeners ===\n\n";

// Get first user (manager role)
$user = App\Models\User::whereHas('roles', function ($query) {
    $query->where('name', 'manager');
})->first();

if (!$user) {
    echo "No manager user found!\n";
    exit(1);
}

// Get a branch
$branch = App\Models\Branch::first();
if (!$branch) {
    echo "No branch found!\n";
    exit(1);
}

// Get a product
$product = App\Models\Product::first();
if (!$product) {
    echo "No product found!\n";
    exit(1);
}

echo "User: {$user->name}\n";
echo "Branch: {$branch->name}\n";
echo "Product: {$product->name}\n\n";

// Count notifications before
$beforeCount = App\Models\Notification::count();
echo "Notifications before: {$beforeCount}\n\n";

// TEST 1: Create issue voucher (should trigger new order notification)
echo "TEST 1: Creating issue voucher (sale)...\n";
try {
    $controller = new App\Http\Controllers\Api\V1\IssueVoucherController(
        new App\Services\InventoryService(),
        new App\Services\LedgerService(),
        new App\Services\CustomerLedgerService(),
        new App\Services\SequencerService()
    );
    
    $request = new Illuminate\Http\Request([
        'branch_id' => $branch->id,
        'issue_date' => date('Y-m-d'),
        'issue_type' => 'SALE',
        'payment_type' => 'CASH',
        'customer_name' => 'عميل اختبار',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 100,
            ]
        ],
    ]);
    
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    $response = $controller->store($request);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['data'])) {
        echo "✓ Issue voucher created: {$data['data']['voucher_number']}\n";
    } else {
        echo "✗ Failed to create issue voucher\n";
        print_r($data);
    }
} catch (\Exception $e) {
    echo "✗ Error: {$e->getMessage()}\n";
}

// Count notifications after
$afterCount = App\Models\Notification::count();
$newNotifications = $afterCount - $beforeCount;
echo "Notifications after: {$afterCount}\n";
echo "New notifications: {$newNotifications}\n\n";

if ($newNotifications > 0) {
    echo "✓ Event listener worked! New notifications created:\n";
    $recent = App\Models\Notification::orderBy('created_at', 'desc')
        ->limit($newNotifications)
        ->get();
    
    foreach ($recent as $notif) {
        echo "  • [{$notif->type}] {$notif->title}\n";
    }
} else {
    echo "⚠ No new notifications created. Event listener may not be triggered.\n";
}

echo "\n✓ Test completed!\n";
