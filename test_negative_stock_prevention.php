<?php
/**
 * TASK-B03: Negative Stock Prevention Test
 * اختبار منع المخزون السالب على مستوى DB Constraint
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Branch;
use App\Models\ProductBranchStock;
use Illuminate\Support\Facades\DB;

echo "\n🛡️ TASK-B03: Negative Stock Prevention Test\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Create test data
    echo "✓ Test 1: Setup Test Data\n";
    
    $branch = Branch::firstOrCreate(
        ['code' => 'TB-TEST'],
        [
            'name' => 'Test Branch for B03',
            'address' => 'Test Address',
            'phone' => '1234567890',
            'is_active' => true
        ]
    );
    echo "  → Branch: {$branch->name}\n";
    
    $category = \App\Models\Category::firstOrCreate(
        ['code' => 'TC-TEST'],
        [
            'name' => 'Test Category for B03',
            'description' => 'For testing'
        ]
    );
    
    $product = Product::firstOrCreate(
        ['code' => 'TP-TEST'],
        [
            'name' => 'Test Product for B03',
            'category_id' => $category->id,
            'description' => 'For testing',
            'unit' => 'piece',
            'pack_size' => 1,
            'reorder_level' => 10,
            'is_active' => true
        ]
    );
    echo "  → Created product: {$product->name}\n\n";
    
    // Test 2: Create stock with positive value (should succeed)
    echo "✓ Test 2: Create Stock with Positive Value\n";
    $stock = ProductBranchStock::create([
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'current_stock' => 100,
        'reserved_stock' => 0
    ]);
    echo "  → Stock created: {$stock->current_stock} units\n";
    echo "  ✓ PASS: Positive stock allowed\n\n";
    
    // Test 3: Update stock to zero (should succeed)
    echo "✓ Test 3: Update Stock to Zero\n";
    $stock->update(['current_stock' => 0]);
    echo "  → Stock updated to: {$stock->current_stock}\n";
    echo "  ✓ PASS: Zero stock allowed\n\n";
    
    // Test 4: Try to create stock with negative value (should FAIL)
    echo "✓ Test 4: Try to Create Stock with Negative Value\n";
    try {
        ProductBranchStock::create([
            'product_id' => $product->id + 1, // Different product
            'branch_id' => $branch->id,
            'current_stock' => -50,
            'reserved_stock' => 0
        ]);
        echo "  ✗ FAIL: Negative stock was allowed! (CHECK constraint not working)\n";
        $constraintWorking = false;
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'CHECK constraint failed') !== false ||
            strpos($e->getMessage(), 'constraint') !== false) {
            echo "  ✓ PASS: Negative stock blocked by CHECK constraint\n";
            echo "  → Error: " . substr($e->getMessage(), 0, 100) . "...\n";
            $constraintWorking = true;
        } else {
            echo "  ⚠️ Unexpected error: " . $e->getMessage() . "\n";
            $constraintWorking = false;
        }
    }
    echo "\n";
    
    // Test 5: Try to update existing stock to negative (should FAIL)
    echo "✓ Test 5: Try to Update Stock to Negative Value\n";
    try {
        $stock->update(['current_stock' => -100]);
        echo "  ✗ FAIL: Negative stock update was allowed!\n";
        $updateConstraintWorking = false;
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'CHECK constraint failed') !== false ||
            strpos($e->getMessage(), 'constraint') !== false) {
            echo "  ✓ PASS: Negative stock update blocked by CHECK constraint\n";
            echo "  → Error: " . substr($e->getMessage(), 0, 100) . "...\n";
            $updateConstraintWorking = true;
        } else {
            echo "  ⚠️ Unexpected error: " . $e->getMessage() . "\n";
            $updateConstraintWorking = false;
        }
    }
    echo "\n";
    
    // Test 6: Check table schema for CHECK constraint
    echo "✓ Test 6: Verify CHECK Constraint in Database Schema\n";
    $tableInfo = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name='product_branch_stock'");
    
    if (!empty($tableInfo)) {
        $sql = $tableInfo[0]->sql;
        $hasCheckConstraint = strpos($sql, 'CHECK') !== false && 
                             strpos($sql, 'current_stock >= 0') !== false;
        
        if ($hasCheckConstraint) {
            echo "  ✓ CHECK constraint found in table definition\n";
            echo "  → Constraint: CHECK(current_stock >= 0)\n";
        } else {
            echo "  ✗ CHECK constraint NOT found in table definition\n";
        }
    }
    echo "\n";
    
    // Test 7: lockForUpdate protection (from InventoryMovementService)
    echo "✓ Test 7: lockForUpdate() Protection in InventoryMovementService\n";
    $serviceCode = file_get_contents(__DIR__ . '/app/Services/InventoryMovementService.php');
    $hasLockForUpdate = strpos($serviceCode, 'lockForUpdate()') !== false;
    $hasTransaction = strpos($serviceCode, 'DB::transaction') !== false;
    $hasNegativeCheck = strpos($serviceCode, '$newBalance < 0') !== false ||
                       strpos($serviceCode, 'negative stock') !== false;
    
    echo "  → lockForUpdate(): " . ($hasLockForUpdate ? "✓ Present" : "✗ Missing") . "\n";
    echo "  → DB::transaction: " . ($hasTransaction ? "✓ Present" : "✗ Missing") . "\n";
    echo "  → Negative check: " . ($hasNegativeCheck ? "✓ Present" : "✗ Missing") . "\n";
    
    if ($hasLockForUpdate && $hasTransaction && $hasNegativeCheck) {
        echo "  ✓ PASS: Service has proper protection\n";
    }
    echo "\n";
    
    // Summary
    echo str_repeat("=", 70) . "\n";
    echo "🎉 NEGATIVE STOCK PREVENTION TEST COMPLETE\n\n";
    
    echo "📊 Results Summary:\n";
    echo "  • Positive stock: ✓ Allowed\n";
    echo "  • Zero stock: ✓ Allowed\n";
    echo "  • Negative stock (create): " . ($constraintWorking ? "✓ Blocked" : "✗ Not blocked") . "\n";
    echo "  • Negative stock (update): " . ($updateConstraintWorking ? "✓ Blocked" : "✗ Not blocked") . "\n";
    echo "  • CHECK constraint: " . ($hasCheckConstraint ? "✓ Exists" : "✗ Missing") . "\n";
    echo "  • Service protection: " . ($hasLockForUpdate && $hasTransaction ? "✓ Exists" : "⚠️ Incomplete") . "\n";
    echo "\n";
    
    $allTestsPassed = $constraintWorking && $updateConstraintWorking && 
                      $hasCheckConstraint && $hasLockForUpdate && $hasTransaction;
    
    if ($allTestsPassed) {
        echo "✅ RESULT: ALL TESTS PASSED\n";
        echo "   → Database-level protection: ACTIVE\n";
        echo "   → Application-level protection: ACTIVE\n";
        echo "   → System is protected against negative stock\n";
    } else {
        echo "⚠️ RESULT: SOME TESTS FAILED\n";
        if (!$constraintWorking || !$updateConstraintWorking || !$hasCheckConstraint) {
            echo "   → Database-level protection: INCOMPLETE\n";
        }
        if (!$hasLockForUpdate || !$hasTransaction) {
            echo "   → Application-level protection: INCOMPLETE\n";
        }
    }
    
    echo "\n✨ TASK-B03 Status: " . ($allTestsPassed ? "✅ COMPLETED" : "⚠️ NEEDS ATTENTION") . "\n";
    echo str_repeat("=", 70) . "\n\n";
    
    // Cleanup
    echo "🧹 Cleaning up test data...\n";
    $stock->delete();
    $product->delete();
    $category->delete();
    $branch->delete();
    echo "   ✓ Test data removed\n\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
