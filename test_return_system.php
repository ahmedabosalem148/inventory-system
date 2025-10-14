<?php

/**
 * Return System Comprehensive Test
 * Tests the complete return voucher workflow
 * Usage: php test_return_system.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ReturnVoucher;
use App\Models\ReturnVoucherItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\User;
use App\Models\InventoryMovement;
use App\Models\ProductBranchStock;
use App\Models\CustomerLedgerEntry;
use App\Services\ReturnService;
use Illuminate\Support\Facades\DB;

echo "\n" . str_repeat("=", 70) . "\n";
echo "🧪 RETURN SYSTEM COMPREHENSIVE TEST\n";
echo str_repeat("=", 70) . "\n\n";

$testsPassed = 0;
$testsFailed = 0;

/**
 * Helper function to run a test
 */
$runTest = function (string $testName, callable $testFunction) use (&$testsPassed, &$testsFailed) {
    echo "▶ Testing: {$testName}...\n";
    
    try {
        $result = $testFunction();
        
        if ($result === true) {
            echo "  ✅ PASS\n\n";
            $testsPassed++;
        } else {
            echo "  ❌ FAIL: {$result}\n\n";
            $testsFailed++;
        }
    } catch (Exception $e) {
        echo "  ❌ FAIL: " . $e->getMessage() . "\n\n";
        $testsFailed++;
    }
};

// Cleanup any existing test data
echo "🧹 Cleaning up test data...\n\n";
DB::table('return_voucher_items')->where('return_voucher_id', '>', 0)->delete();
DB::table('return_vouchers')->where('voucher_number', 'like', 'TEST-%')->delete();
DB::table('inventory_movements')->where('notes', 'like', '%TEST%')->delete();
DB::table('customer_ledger_entries')->where('description', 'like', '%TEST%')->delete();

// Get test data
$user = User::first();
if (!$user) {
    die("❌ Error: No users found in database. Please seed users first.\n\n");
}

$customer = Customer::first();
if (!$customer) {
    die("❌ Error: No customers found. Please create a customer first.\n\n");
}

$branch = Branch::first();
if (!$branch) {
    die("❌ Error: No branches found. Please create a branch first.\n\n");
}

$product = Product::first();

if (!$product) {
    die("❌ Error: No products found. Please create a product first.\n\n");
}

// Ensure product has stock in branch
$stock = ProductBranchStock::firstOrCreate(
    ['product_id' => $product->id, 'branch_id' => $branch->id],
    ['current_stock' => 0, 'min_stock_level' => 10]
);

// Set initial stock for testing
$initialStock = 100;
$stock->current_stock = $initialStock;
$stock->save();

echo "📊 Test Environment Setup:\n";
echo "   User: {$user->name}\n";
echo "   Customer: {$customer->name}\n";
echo "   Branch: {$branch->name}\n";
echo "   Product: {$product->name}\n";
echo "   Initial Stock: {$initialStock} {$product->unit}\n\n";

echo str_repeat("-", 70) . "\n\n";

// TEST 1: Create Return Voucher
$runTest("Create return voucher", function () use ($customer, $branch, $product, $user) {
    $returnVoucher = ReturnVoucher::create([
        'voucher_number' => 'TEST-RETURN-001', // Temporary, will be replaced on approval
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'return_date' => now(),
        'total_amount' => 0,
        'status' => 'completed',
        'reason' => 'TEST: منتج معيب',
        'created_by' => $user->id,
    ]);

    ReturnVoucherItem::create([
        'return_voucher_id' => $returnVoucher->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_price' => 100,
        'total_price' => 1000,
    ]);

    $returnVoucher->total_amount = 1000;
    $returnVoucher->save();

    return ReturnVoucher::find($returnVoucher->id) !== null;
});

// TEST 2: Return voucher has items
$runTest("Return voucher has items", function () {
    $returnVoucher = ReturnVoucher::where('voucher_number', 'TEST-RETURN-001')->first();
    return $returnVoucher && $returnVoucher->items()->count() === 1;
});

// TEST 3: Return voucher not approved initially
$runTest("Return voucher not approved initially", function () {
    $returnVoucher = ReturnVoucher::where('voucher_number', 'TEST-RETURN-001')->first();
    return $returnVoucher && !$returnVoucher->isApproved();
});

// TEST 4: Get initial stock before return
$stockBeforeReturn = ProductBranchStock::where('product_id', $product->id)
    ->where('branch_id', $branch->id)
    ->first()->current_stock;

$runTest("Initial stock recorded", function () use ($stockBeforeReturn, $initialStock) {
    return $stockBeforeReturn == $initialStock;
});

// TEST 5: Get initial customer balance
$initialBalance = app(\App\Services\CustomerLedgerService::class)->calculateBalance($customer->id);

$runTest("Initial customer balance calculated", function () use ($initialBalance) {
    return is_numeric($initialBalance);
});

// TEST 6: Approve return voucher
$runTest("Approve return voucher", function () use ($user) {
    $returnVoucher = ReturnVoucher::where('voucher_number', 'TEST-RETURN-001')->first();
    $returnVoucher->approve($user);
    
    return $returnVoucher->isApproved() && $returnVoucher->approved_by === $user->id;
});

// TEST 7: Return voucher gets special number range (100001-125000)
$runTest("Return voucher gets special number range", function () {
    $returnVoucher = ReturnVoucher::whereNotNull('approved_at')
        ->latest('approved_at')
        ->first();
    
    if (!$returnVoucher) {
        return "No approved return voucher found";
    }
    
    // Extract number from format like "2025/100001"
    $parts = explode('/', $returnVoucher->voucher_number);
    $voucherNumber = (int) end($parts);
    
    return $voucherNumber >= 100001 && $voucherNumber <= 125000;
});

// TEST 8: RETURN inventory movement created
$runTest("RETURN inventory movement created", function () use ($customer, $branch) {
    $returnVoucher = ReturnVoucher::where('customer_id', $customer->id)
        ->whereNotNull('approved_at')
        ->latest('approved_at')
        ->first();
    
    $movement = InventoryMovement::where('ref_table', 'return_vouchers')
        ->where('ref_id', $returnVoucher->id)
        ->where('movement_type', 'RETURN')
        ->where('branch_id', $branch->id)
        ->first();
    
    return $movement !== null;
});

// TEST 9: RETURN movement has positive quantity
$runTest("RETURN movement has positive quantity", function () use ($customer, $branch) {
    $returnVoucher = ReturnVoucher::where('customer_id', $customer->id)
        ->whereNotNull('approved_at')
        ->latest('approved_at')
        ->first();
    
    $movement = InventoryMovement::where('ref_table', 'return_vouchers')
        ->where('ref_id', $returnVoucher->id)
        ->where('movement_type', 'RETURN')
        ->first();
    
    return $movement && $movement->qty_units > 0;
});

// TEST 10: Stock increased after return
$runTest("Stock increased after return", function () use ($product, $branch, $stockBeforeReturn) {
    $stockAfterReturn = ProductBranchStock::where('product_id', $product->id)
        ->where('branch_id', $branch->id)
        ->first()->current_stock;
    
    return $stockAfterReturn > $stockBeforeReturn;
});

// TEST 11: Stock increased by correct amount (10 units)
$runTest("Stock increased by correct amount", function () use ($product, $branch, $stockBeforeReturn) {
    $stockAfterReturn = ProductBranchStock::where('product_id', $product->id)
        ->where('branch_id', $branch->id)
        ->first()->current_stock;
    
    $expectedStock = $stockBeforeReturn + 10; // We returned 10 units
    return $stockAfterReturn == $expectedStock;
});

// TEST 12: Customer ledger entry created (له - credit)
$runTest("Customer ledger entry created (له - credit)", function () use ($customer) {
    $returnVoucher = ReturnVoucher::where('customer_id', $customer->id)
        ->whereNotNull('approved_at')
        ->latest('approved_at')
        ->first();
    
    $entry = CustomerLedgerEntry::where('customer_id', $customer->id)
        ->where('ref_table', 'return_vouchers')
        ->where('ref_id', $returnVoucher->id)
        ->first();
    
    return $entry !== null;
});

// TEST 13: Ledger entry is credit (له)
$runTest("Ledger entry is credit (له)", function () use ($customer) {
    $returnVoucher = ReturnVoucher::where('customer_id', $customer->id)
        ->whereNotNull('approved_at')
        ->latest('approved_at')
        ->first();
    
    $entry = CustomerLedgerEntry::where('customer_id', $customer->id)
        ->where('ref_table', 'return_vouchers')
        ->where('ref_id', $returnVoucher->id)
        ->first();
    
    return $entry && $entry->credit_lah == 1000 && $entry->debit_aliah == 0;
});

// TEST 14: Customer balance decreased (debt reduced)
$runTest("Customer balance decreased (debt reduced)", function () use ($customer, $initialBalance) {
    $newBalance = app(\App\Services\CustomerLedgerService::class)->calculateBalance($customer->id);
    return $newBalance < $initialBalance; // Return reduces debt (balance decreases)
});

// TEST 15: ReturnService statistics work
$runTest("ReturnService statistics work", function () use ($branch) {
    $returnService = app(\App\Services\ReturnService::class);
    $stats = $returnService->getReturnStatistics($branch->id);
    
    return isset($stats['total_returns']) && $stats['total_returns'] > 0;
});

// TEST 16: Most returned products query works
$runTest("Most returned products query works", function () use ($branch) {
    $returnService = app(\App\Services\ReturnService::class);
    $products = $returnService->getMostReturnedProducts($branch->id);
    
    return $products->count() > 0;
});

// TEST 17: Cannot approve already approved return
$runTest("Cannot approve already approved return", function () use ($user, $customer) {
    $returnVoucher = ReturnVoucher::where('customer_id', $customer->id)
        ->whereNotNull('approved_at')
        ->latest('approved_at')
        ->first();
    
    try {
        $returnVoucher->approve($user);
        return "Should have thrown exception";
    } catch (Exception $e) {
        return true; // Expected to fail
    }
});

// TEST 18: Return validation works
$runTest("Return validation works", function () {
    $returnService = app(\App\Services\ReturnService::class);
    
    // Invalid items
    $result = $returnService->validateReturn([
        ['product_id' => 99999, 'quantity' => 10, 'unit_price' => 100],
    ]);
    
    return !$result['valid'] && !empty($result['errors']);
});

// TEST 19: Valid return passes validation
$runTest("Valid return passes validation", function () use ($product) {
    $returnService = app(\App\Services\ReturnService::class);
    
    $result = $returnService->validateReturn([
        ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100],
    ]);
    
    return $result['valid'] && empty($result['errors']);
});

// TEST 20: Cancel unapproved return
$runTest("Cancel unapproved return", function () use ($customer, $branch, $product, $user) {
    $returnService = app(\App\Services\ReturnService::class);
    
    $returnVoucher = ReturnVoucher::create([
        'voucher_number' => 'TEST-CANCEL-001',
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'return_date' => now(),
        'total_amount' => 500,
        'status' => 'completed',
        'reason' => 'TEST: للإلغاء',
        'created_by' => $user->id,
    ]);
    
    $cancelled = $returnService->cancelReturn($returnVoucher, $user, 'TEST: تم الإلغاء للاختبار');
    
    return $cancelled->status === 'cancelled';
});

// Display results summary
echo str_repeat("=", 70) . "\n";
echo "📊 TEST RESULTS SUMMARY\n";
echo str_repeat("=", 70) . "\n\n";

$totalTests = $testsPassed + $testsFailed;
$successRate = $totalTests > 0 ? ($testsPassed / $totalTests) * 100 : 0;

echo "Total Tests: {$totalTests}\n";
echo "✅ Passed: {$testsPassed}\n";
echo "❌ Failed: {$testsFailed}\n";
echo "Success Rate: " . number_format($successRate, 2) . "%\n\n";

if ($testsFailed === 0) {
    echo "🎉 ALL TESTS PASSED! Return system is working perfectly!\n\n";
} else {
    echo "⚠️  Some tests failed. Please review the errors above.\n\n";
}

// Cleanup test data
echo "🧹 Cleaning up test data...\n";
DB::table('return_voucher_items')->whereIn('return_voucher_id', function($query) {
    $query->select('id')->from('return_vouchers')->where('reason', 'like', 'TEST:%');
})->delete();
DB::table('return_vouchers')->where('reason', 'like', 'TEST:%')->delete();
DB::table('inventory_movements')->where('notes', 'like', '%TEST%')->delete();
DB::table('customer_ledger_entries')->where('notes', 'like', 'TEST:%')->delete();

echo "✅ Cleanup complete!\n\n";

echo str_repeat("=", 70) . "\n\n";

exit($testsFailed > 0 ? 1 : 0);
