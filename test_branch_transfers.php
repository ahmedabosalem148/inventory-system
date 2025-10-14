<?php

/**
 * TASK-B04: Branch Transfers Integration Testing
 * 
 * Tests 5 critical scenarios for branch transfers:
 * 1. Simple transfer with sufficient stock ✅
 * 2. Transfer with insufficient stock ❌
 * 3. Concurrent transfers (race condition) 🔒
 * 4. Transfer rollback on failure 🔄
 * 5. Transfer chain (A→B→C) 🔗
 * 
 * @author Inventory System Team
 * @version 1.0
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Category;
use App\Models\ProductBranchStock;
use App\Models\InventoryMovement;
use App\Services\InventoryService;
use App\Services\TransferService;
use App\Models\IssueVoucher;
use App\Models\User;

// تشغيل Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   TASK-B04: Branch Transfers Integration Testing 🔄\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// إحصائيات الاختبارات
$stats = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'start_time' => microtime(true),
];

/**
 * مساعد: طباعة نتيجة الاختبار
 */
function testResult(string $testName, bool $passed, string $message = ''): void
{
    global $stats;
    $stats['total']++;
    
    if ($passed) {
        $stats['passed']++;
        echo "✅ PASS: {$testName}\n";
        if ($message) {
            echo "   └─ {$message}\n";
        }
    } else {
        $stats['failed']++;
        echo "❌ FAIL: {$testName}\n";
        if ($message) {
            echo "   └─ ERROR: {$message}\n";
        }
    }
    echo "\n";
}

/**
 * مساعد: تنظيف البيانات وإعداد بيئة الاختبار
 */
function setupTestEnvironment(): array
{
    echo "🔧 Setting up test environment...\n";
    
    DB::beginTransaction();
    
    try {
        // إنشاء تصنيف للاختبار
        $category = Category::firstOrCreate(
            ['name' => 'TEST_CATEGORY'],
            ['description' => 'Test category for transfer tests']
        );
        
        // إنشاء 3 فروع
        $branch1 = Branch::firstOrCreate(
            ['code' => 'TEST_A'],
            [
                'name' => 'TEST_BRANCH_A',
                'address' => 'Test Address A',
                'phone' => '1111111111'
            ]
        );
        
        $branch2 = Branch::firstOrCreate(
            ['code' => 'TEST_B'],
            [
                'name' => 'TEST_BRANCH_B',
                'address' => 'Test Address B',
                'phone' => '2222222222'
            ]
        );
        
        $branch3 = Branch::firstOrCreate(
            ['code' => 'TEST_C'],
            [
                'name' => 'TEST_BRANCH_C',
                'address' => 'Test Address C',
                'phone' => '3333333333'
            ]
        );
        
        // إنشاء منتجات اختبار
        $product1 = Product::firstOrCreate(
            ['name' => 'Transfer Test Product 1'],
            [
                'category_id' => $category->id,
                'description' => 'Test product for transfer testing',
                'unit' => 'piece',
                'pack_size' => 12,
                'purchase_price' => 80,
                'sale_price' => 100,
                'min_stock' => 10,
                'is_active' => true,
            ]
        );
        
        $product2 = Product::firstOrCreate(
            ['name' => 'Transfer Test Product 2'],
            [
                'category_id' => $category->id,
                'description' => 'Test product 2 for transfer testing',
                'unit' => 'piece',
                'pack_size' => 24,
                'purchase_price' => 40,
                'sale_price' => 50,
                'min_stock' => 5,
                'is_active' => true,
            ]
        );
        
        // تنظيف الحركات والأرصدة القديمة
        InventoryMovement::where('product_id', $product1->id)->delete();
        InventoryMovement::where('product_id', $product2->id)->delete();
        ProductBranchStock::where('product_id', $product1->id)->delete();
        ProductBranchStock::where('product_id', $product2->id)->delete();
        
        // إعداد مخزون أولي في الفرع A
        ProductBranchStock::create([
            'product_id' => $product1->id,
            'branch_id' => $branch1->id,
            'current_stock' => 100, // كمية كافية
            'reserved_stock' => 0,
            'min_qty' => 10,
        ]);
        
        ProductBranchStock::create([
            'product_id' => $product2->id,
            'branch_id' => $branch1->id,
            'current_stock' => 5, // كمية قليلة
            'reserved_stock' => 0,
            'min_qty' => 10,
        ]);
        
        // إنشاء مستخدم للاختبار
        $user = User::firstOrCreate(
            ['email' => 'test@transfer.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'branch_id' => $branch1->id,
            ]
        );
        
        DB::commit();
        
        echo "   ✅ 3 branches created\n";
        echo "   ✅ 2 products created\n";
        echo "   ✅ Initial stock set (Branch A: Product1=100, Product2=5)\n";
        echo "\n";
        
        return [
            'branches' => compact('branch1', 'branch2', 'branch3'),
            'products' => compact('product1', 'product2'),
            'user' => $user,
        ];
        
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

/**
 * Scenario 1: Simple Transfer with Sufficient Stock ✅
 */
function testSimpleTransfer(array $testData): void
{
    echo "─────────────────────────────────────────────────────────────\n";
    echo "📦 SCENARIO 1: Simple Transfer (Sufficient Stock)\n";
    echo "─────────────────────────────────────────────────────────────\n\n";
    
    $branch1 = $testData['branches']['branch1'];
    $branch2 = $testData['branches']['branch2'];
    $product1 = $testData['products']['product1'];
    
    try {
        $inventoryService = app(InventoryService::class);
        
        // الرصيد الأولي
        $initialStock = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch1->id)
            ->first();
        
        echo "📊 Initial State:\n";
        echo "   Branch A (Source): {$initialStock->current_stock} units\n";
        echo "   Branch B (Target): 0 units\n\n";
        
        // تنفيذ التحويل: 30 وحدة من A → B
        $transferQty = 30;
        echo "🔄 Transferring {$transferQty} units from Branch A → Branch B...\n\n";
        
        $movements = $inventoryService->transferProduct(
            $product1->id,
            $branch1->id,
            $branch2->id,
            $transferQty,
            'Test transfer - Simple scenario'
        );
        
        // التحقق من النتائج
        $sourceStock = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch1->id)
            ->first();
        
        $targetStock = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch2->id)
            ->first();
        
        echo "📊 Final State:\n";
        echo "   Branch A (Source): {$sourceStock->current_stock} units\n";
        echo "   Branch B (Target): {$targetStock->current_stock} units\n\n";
        
        // الاختبارات
        $expectedSourceStock = $initialStock->current_stock - $transferQty;
        testResult(
            "S1.1: Source stock decreased correctly",
            $sourceStock->current_stock == $expectedSourceStock,
            "Expected: {$expectedSourceStock}, Got: {$sourceStock->current_stock}"
        );
        
        testResult(
            "S1.2: Target stock increased correctly",
            $targetStock->current_stock == $transferQty,
            "Expected: {$transferQty}, Got: {$targetStock->current_stock}"
        );
        
        // التحقق من حركات المخزون
        $outMovement = InventoryMovement::where('product_id', $product1->id)
            ->where('branch_id', $branch1->id)
            ->where('movement_type', 'TRANSFER_OUT')
            ->latest()
            ->first();
        
        $inMovement = InventoryMovement::where('product_id', $product1->id)
            ->where('branch_id', $branch2->id)
            ->where('movement_type', 'TRANSFER_IN')
            ->latest()
            ->first();
        
        testResult(
            "S1.3: TRANSFER_OUT movement created",
            $outMovement !== null && $outMovement->qty_units == $transferQty,
            "Quantity: {$transferQty}"
        );
        
        testResult(
            "S1.4: TRANSFER_IN movement created",
            $inMovement !== null && $inMovement->qty_units == $transferQty,
            "Quantity: {$transferQty}"
        );
        
    } catch (\Exception $e) {
        testResult("S1: Simple Transfer", false, $e->getMessage());
    }
}

/**
 * Scenario 2: Transfer with Insufficient Stock ❌
 */
function testInsufficientStock(array $testData): void
{
    echo "─────────────────────────────────────────────────────────────\n";
    echo "🚫 SCENARIO 2: Transfer with Insufficient Stock\n";
    echo "─────────────────────────────────────────────────────────────\n\n";
    
    $branch1 = $testData['branches']['branch1'];
    $branch2 = $testData['branches']['branch2'];
    $product2 = $testData['products']['product2'];
    
    try {
        $inventoryService = app(InventoryService::class);
        
        // الرصيد الحالي (5 وحدات فقط)
        $currentStock = ProductBranchStock::where('product_id', $product2->id)
            ->where('branch_id', $branch1->id)
            ->first();
        
        echo "📊 Current State:\n";
        echo "   Branch A (Source): {$currentStock->current_stock} units\n";
        echo "   Requested: 50 units\n\n";
        
        // محاولة تحويل 50 وحدة (أكثر من المتاح)
        $transferQty = 50;
        echo "🔄 Attempting to transfer {$transferQty} units (MORE than available)...\n\n";
        
        $exceptionCaught = false;
        try {
            $movements = $inventoryService->transferProduct(
                $product2->id,
                $branch1->id,
                $branch2->id,
                $transferQty,
                'Test transfer - Insufficient stock scenario'
            );
        } catch (\Exception $e) {
            $exceptionCaught = true;
            echo "🛑 Exception caught: " . $e->getMessage() . "\n\n";
        }
        
        // التحقق من رفض العملية
        testResult(
            "S2.1: Transfer rejected due to insufficient stock",
            $exceptionCaught === true,
            "System correctly prevented negative stock"
        );
        
        // التحقق من عدم تغيير الرصيد
        $afterStock = ProductBranchStock::where('product_id', $product2->id)
            ->where('branch_id', $branch1->id)
            ->first();
        
        testResult(
            "S2.2: Stock unchanged after failed transfer",
            $afterStock->current_stock == $currentStock->current_stock,
            "Stock remains: {$afterStock->current_stock}"
        );
        
    } catch (\Exception $e) {
        testResult("S2: Insufficient Stock", false, $e->getMessage());
    }
}

/**
 * Scenario 3: Concurrent Transfers (Race Condition) 🔒
 */
function testConcurrentTransfers(array $testData): void
{
    echo "─────────────────────────────────────────────────────────────\n";
    echo "🔒 SCENARIO 3: Concurrent Transfers (Race Condition)\n";
    echo "─────────────────────────────────────────────────────────────\n\n";
    
    $branch1 = $testData['branches']['branch1'];
    $branch2 = $testData['branches']['branch2'];
    $branch3 = $testData['branches']['branch3'];
    $product1 = $testData['products']['product1'];
    
    echo "⚠️  Note: True concurrency testing requires separate processes.\n";
    echo "    This test simulates sequential operations to verify transaction safety.\n\n";
    
    try {
        $inventoryService = app(InventoryService::class);
        
        // الرصيد الحالي
        $initialStock = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch1->id)
            ->lockForUpdate()
            ->first();
        
        echo "📊 Initial Stock: {$initialStock->current_stock} units in Branch A\n\n";
        
        // تحويل 1: A → B (20 وحدة)
        echo "🔄 Transfer 1: A → B (20 units)\n";
        $movements1 = $inventoryService->transferProduct(
            $product1->id,
            $branch1->id,
            $branch2->id,
            20,
            'Concurrent test - Transfer 1'
        );
        
        // تحويل 2: A → C (15 وحدة)
        echo "🔄 Transfer 2: A → C (15 units)\n\n";
        $movements2 = $inventoryService->transferProduct(
            $product1->id,
            $branch1->id,
            $branch3->id,
            15,
            'Concurrent test - Transfer 2'
        );
        
        // التحقق من الأرصدة النهائية
        $finalSourceStock = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch1->id)
            ->first();
        
        $finalStockB = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch2->id)
            ->first();
        
        $finalStockC = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch3->id)
            ->first();
        
        echo "📊 Final State:\n";
        echo "   Branch A: {$finalSourceStock->current_stock} units\n";
        echo "   Branch B: {$finalStockB->current_stock} units\n";
        echo "   Branch C: {$finalStockC->current_stock} units\n\n";
        
        // التحقق من صحة الحسابات
        $expectedSourceStock = $initialStock->current_stock - 20 - 15;
        testResult(
            "S3.1: Source stock calculation correct",
            $finalSourceStock->current_stock == $expectedSourceStock,
            "Expected: {$expectedSourceStock}, Got: {$finalSourceStock->current_stock}"
        );
        
        // التحقق: الكمية المحولة من A يجب أن تساوي 20 + 15 = 35
        $expectedTransferred = 20 + 15; // Transfer 1 + Transfer 2
        $actualTransferred = $initialStock->current_stock - $finalSourceStock->current_stock;
        
        testResult(
            "S3.2: Transferred quantity matches expected (20+15=35)",
            $actualTransferred == $expectedTransferred,
            "Expected: {$expectedTransferred}, Got: {$actualTransferred}"
        );
        
    } catch (\Exception $e) {
        testResult("S3: Concurrent Transfers", false, $e->getMessage());
    }
}

/**
 * Scenario 4: Transfer Rollback on Failure 🔄
 */
function testTransferRollback(array $testData): void
{
    echo "─────────────────────────────────────────────────────────────\n";
    echo "🔄 SCENARIO 4: Transfer Rollback on Failure\n";
    echo "─────────────────────────────────────────────────────────────\n\n";
    
    $branch1 = $testData['branches']['branch1'];
    $branch2 = $testData['branches']['branch2'];
    $product1 = $testData['products']['product1'];
    
    try {
        // الرصيد قبل العملية
        $beforeStock = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch1->id)
            ->first();
        
        echo "📊 Stock before operation: {$beforeStock->current_stock} units\n\n";
        
        // محاولة تحويل إلى فرع غير موجود (سيفشل)
        echo "🔄 Attempting transfer to INVALID branch (should rollback)...\n\n";
        
        $exceptionCaught = false;
        try {
            DB::transaction(function () use ($product1, $branch1) {
                $inventoryService = app(InventoryService::class);
                
                // تحويل عادي أولاً
                $inventoryService->transferProduct(
                    $product1->id,
                    $branch1->id,
                    999999, // فرع غير موجود
                    10,
                    'Rollback test'
                );
            });
        } catch (\Exception $e) {
            $exceptionCaught = true;
            echo "🛑 Transaction rolled back: " . $e->getMessage() . "\n\n";
        }
        
        testResult(
            "S4.1: Exception caught on invalid branch",
            $exceptionCaught === true,
            "System detected invalid operation"
        );
        
        // التحقق من عدم تغيير الرصيد
        $afterStock = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch1->id)
            ->first();
        
        testResult(
            "S4.2: Stock unchanged after rollback",
            $afterStock->current_stock == $beforeStock->current_stock,
            "Stock preserved: {$afterStock->current_stock}"
        );
        
        // التحقق من عدم إنشاء حركات مخزون
        $movementsCount = InventoryMovement::where('product_id', $product1->id)
            ->where('notes', 'LIKE', '%Rollback test%')
            ->count();
        
        testResult(
            "S4.3: No inventory movements created",
            $movementsCount == 0,
            "Transaction fully rolled back"
        );
        
    } catch (\Exception $e) {
        testResult("S4: Transfer Rollback", false, $e->getMessage());
    }
}

/**
 * Scenario 5: Transfer Chain (A → B → C) 🔗
 */
function testTransferChain(array $testData): void
{
    echo "─────────────────────────────────────────────────────────────\n";
    echo "🔗 SCENARIO 5: Transfer Chain (A → B → C)\n";
    echo "─────────────────────────────────────────────────────────────\n\n";
    
    $branch1 = $testData['branches']['branch1'];
    $branch2 = $testData['branches']['branch2'];
    $branch3 = $testData['branches']['branch3'];
    $product1 = $testData['products']['product1'];
    
    try {
        $inventoryService = app(InventoryService::class);
        
        // الحالة الأولية
        $initialA = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch1->id)
            ->first();
        
        $initialB = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch2->id)
            ->first();
        
        $initialC = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch3->id)
            ->first();
        
        echo "📊 Initial State:\n";
        echo "   Branch A: {$initialA->current_stock} units\n";
        echo "   Branch B: " . ($initialB->current_stock ?? 0) . " units\n";
        echo "   Branch C: " . ($initialC->current_stock ?? 0) . " units\n\n";
        
        // المرحلة 1: A → B (25 وحدة)
        echo "🔗 Step 1: A → B (25 units)\n";
        $inventoryService->transferProduct(
            $product1->id,
            $branch1->id,
            $branch2->id,
            25,
            'Chain test - Step 1'
        );
        
        $stockA_after1 = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch1->id)
            ->first();
        $stockB_after1 = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch2->id)
            ->first();
        
        echo "   A: {$stockA_after1->current_stock}, B: {$stockB_after1->current_stock}\n\n";
        
        // المرحلة 2: B → C (10 وحدات)
        echo "🔗 Step 2: B → C (10 units)\n";
        $inventoryService->transferProduct(
            $product1->id,
            $branch2->id,
            $branch3->id,
            10,
            'Chain test - Step 2'
        );
        
        $stockA_final = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch1->id)
            ->first();
        $stockB_final = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch2->id)
            ->first();
        $stockC_final = ProductBranchStock::where('product_id', $product1->id)
            ->where('branch_id', $branch3->id)
            ->first();
        
        echo "   A: {$stockA_final->current_stock}, B: {$stockB_final->current_stock}, C: {$stockC_final->current_stock}\n\n";
        
        // الاختبارات
        testResult(
            "S5.1: Branch A stock correct after chain",
            $stockA_final->current_stock == ($initialA->current_stock - 25),
            "A: {$stockA_final->current_stock}"
        );
        
        // الكميات المتوقعة (مع مراعاة الأرصدة السابقة من Scenario 1 & 3)
        $initialB_qty = $initialB->current_stock ?? 0;
        $initialC_qty = $initialC->current_stock ?? 0;
        
        $expectedB = $initialB_qty + 25 - 10; // +25 من Step1, -10 في Step2
        $expectedC = $initialC_qty + 10; // +10 من Step2
        
        testResult(
            "S5.2: Branch B stock correct after chain",
            $stockB_final->current_stock == $expectedB,
            "Expected: {$expectedB}, Got: {$stockB_final->current_stock}"
        );
        
        testResult(
            "S5.3: Branch C stock correct after chain",
            $stockC_final->current_stock == $expectedC,
            "Expected: {$expectedC}, Got: {$stockC_final->current_stock}"
        );
        
        // التحقق من حفظ الكمية المحولة فقط (مش الإجمالي الكلي)
        $totalTransferred = 25; // A → B
        $totalReceived = ($stockB_final->current_stock - $initialB_qty) + 
                        ($stockC_final->current_stock - $initialC_qty);
        
        testResult(
            "S5.4: Total transferred equals total received",
            $totalTransferred == $totalReceived,
            "Transferred: {$totalTransferred}, Received: {$totalReceived}"
        );
        
        // التحقق من إنشاء 4 حركات (2 OUT + 2 IN)
        $movementsCount = InventoryMovement::where('product_id', $product1->id)
            ->where('notes', 'LIKE', '%Chain test%')
            ->count();
        
        testResult(
            "S5.5: Correct number of movements created",
            $movementsCount == 4, // 2 transfers × 2 movements each
            "Movements: {$movementsCount}"
        );
        
    } catch (\Exception $e) {
        testResult("S5: Transfer Chain", false, $e->getMessage());
    }
}

/**
 * طباعة التقرير النهائي
 */
function printFinalReport(array $stats): void
{
    $duration = round(microtime(true) - $stats['start_time'], 2);
    $passRate = $stats['total'] > 0 ? round(($stats['passed'] / $stats['total']) * 100, 1) : 0;
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "   📊 FINAL TEST REPORT\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    echo "Total Tests:    {$stats['total']}\n";
    echo "Passed:         {$stats['passed']} ✅\n";
    echo "Failed:         {$stats['failed']} ❌\n";
    echo "Pass Rate:      {$passRate}%\n";
    echo "Duration:       {$duration}s\n\n";
    
    if ($stats['failed'] == 0) {
        echo "🎉 ALL TESTS PASSED! 🎉\n";
        echo "Branch transfer system is PRODUCTION READY! ✅\n";
    } else {
        echo "⚠️  Some tests failed. Please review the issues above.\n";
    }
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
}

// ═══════════════════════════════════════════════════════════════
// MAIN EXECUTION
// ═══════════════════════════════════════════════════════════════

try {
    // إعداد البيئة
    $testData = setupTestEnvironment();
    
    // تنفيذ السيناريوهات الخمسة
    testSimpleTransfer($testData);
    testInsufficientStock($testData);
    testConcurrentTransfers($testData);
    testTransferRollback($testData);
    testTransferChain($testData);
    
    // طباعة التقرير النهائي
    printFinalReport($stats);
    
    exit($stats['failed'] > 0 ? 1 : 0);
    
} catch (\Exception $e) {
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "❌ FATAL ERROR\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo $e->getMessage() . "\n";
    echo "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    echo "\n";
    exit(1);
}
