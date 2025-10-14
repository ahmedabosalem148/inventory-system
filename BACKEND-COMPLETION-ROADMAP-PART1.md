# 🚀 Backend Completion Roadmap - Part 1
## خارطة طريق إكمال الـ Backend بنسبة 100%

**تاريخ الإنشاء:** 14 أكتوبر 2025  
**المدة الإجمالية:** 2-3 أسابيع  
**الهدف:** إكمال Backend بنسبة 100% مع اختبارات شاملة

---

## 📊 ملخص تنفيذي

### الحالة الحالية:
- ✅ **Backend مكتمل:** 70% (7/10 أنظمة)
- ❌ **Backend ناقص:** 30% (3/10 أنظمة)
- ⚠️ **Tested Features:** 62/62 tests passing
- ❌ **Untested Features:** Transfers, Inventory Movements, Sequencing

### الهدف النهائي:
- 🎯 **Backend:** 100% مكتمل
- 🎯 **Unit Tests:** 150+ tests (100% passing)
- 🎯 **Integration Tests:** 50+ scenarios (100% passing)
- 🎯 **Critical Bugs:** 0
- 🎯 **Production Ready:** ✅

---

## 📋 المراحل الرئيسية

| Phase | المرحلة | التاسكات | المدة | الأولوية |
|-------|---------|----------|-------|----------|
| **Phase 1** | Core Missing Features | 3 tasks | أسبوع | 🔴 P0 |
| **Phase 2** | Testing & Verification | 4 tasks | أسبوع | 🔴 P0 |
| **Phase 3** | Additional Features | 2 tasks | 3 أيام | 🟠 P1 |
| **Phase 4** | Final Integration | 3 tasks | 2 يوم | 🟢 P2 |

---

# 🔴 PHASE 1: Core Missing Features

## TASK-B01: Inventory Movements System 📦

### 📌 Overview
**المدة:** 3 أيام  
**الأولوية:** 🔴 P0 - Critical  
**الحالة:** ❌ Not Started

### 🎯 Objective
تطوير نظام كامل لتتبع حركات المخزون مع رصيد متحرك لكل منتج/فرع

### 📋 Requirements

#### Functional Requirements:
1. تسجيل كل حركة مخزون تلقائياً
2. حساب الرصيد المتحرك (Running Balance)
3. ربط كل حركة بمستندها الأصلي
4. تقرير كارت الصنف الكامل
5. دعم أنواع الحركات: IN, OUT, TRANSFER_OUT, TRANSFER_IN, RETURN, ADJUSTMENT

#### Technical Requirements:
- جدول `inventory_movements`
- Service Layer: `InventoryMovementService`
- Repository Pattern
- Observer Pattern للتسجيل التلقائي
- API Endpoints

---

### 🗄️ Step 1: Database Design (2 ساعات)

#### Migration File
```php
// database/migrations/2025_10_15_000001_create_inventory_movements_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            
            // Product & Branch
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            
            // Movement Details
            $table->enum('type', ['IN', 'OUT', 'TRANSFER_OUT', 'TRANSFER_IN', 'RETURN', 'ADJUSTMENT']);
            $table->integer('quantity'); // Positive for IN, Negative for OUT
            $table->integer('running_balance'); // الرصيد بعد الحركة
            
            // Reference to Source Document
            $table->string('reference_type')->nullable(); // IssueVoucher, ReturnVoucher, etc
            $table->unsignedBigInteger('reference_id')->nullable();
            
            // Additional Info
            $table->date('movement_date');
            $table->text('notes')->nullable();
            $table->string('batch_number')->nullable(); // للمنتجات ذات الأرقام التسلسلية
            
            // Audit Trail
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            // Indexes for Performance
            $table->index(['product_id', 'branch_id', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('movement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
```

#### Model
```php
// app/Models/InventoryMovement.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'product_id',
        'branch_id',
        'type',
        'quantity',
        'running_balance',
        'reference_type',
        'reference_id',
        'movement_date',
        'notes',
        'batch_number',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'quantity' => 'integer',
        'running_balance' => 'integer',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('movement_date', [$from, $to]);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
```

---

### 🏗️ Step 2: Service Layer (4 ساعات)

```php
// app/Services/InventoryMovementService.php
<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\ProductBranchStock;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryMovementService
{
    /**
     * سجل حركة مخزون جديدة
     */
    public function recordMovement(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {
            // Get current stock
            $stock = ProductBranchStock::where('product_id', $data['product_id'])
                ->where('branch_id', $data['branch_id'])
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                throw new \Exception("Product stock not found for branch");
            }

            // Calculate new running balance
            $quantity = $data['quantity'];
            if (in_array($data['type'], ['OUT', 'TRANSFER_OUT'])) {
                $quantity = -abs($quantity); // Force negative
            } else {
                $quantity = abs($quantity); // Force positive
            }

            $runningBalance = $stock->current_stock + $quantity;

            // Prevent negative stock
            if ($runningBalance < 0) {
                throw new \Exception("Insufficient stock. Available: {$stock->current_stock}, Required: " . abs($quantity));
            }

            // Create movement record
            $movement = InventoryMovement::create([
                'product_id' => $data['product_id'],
                'branch_id' => $data['branch_id'],
                'type' => $data['type'],
                'quantity' => $quantity,
                'running_balance' => $runningBalance,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'movement_date' => $data['movement_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'batch_number' => $data['batch_number'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Update current stock
            $stock->update(['current_stock' => $runningBalance]);

            return $movement->load(['product', 'branch', 'creator']);
        });
    }

    /**
     * سجل حركة صرف (Issue)
     */
    public function recordIssue(int $productId, int $branchId, int $quantity, $reference): InventoryMovement
    {
        return $this->recordMovement([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'type' => 'OUT',
            'quantity' => $quantity,
            'reference_type' => get_class($reference),
            'reference_id' => $reference->id,
            'movement_date' => $reference->issue_date ?? now()->toDateString(),
        ]);
    }

    /**
     * سجل حركة إرجاع (Return)
     */
    public function recordReturn(int $productId, int $branchId, int $quantity, $reference): InventoryMovement
    {
        return $this->recordMovement([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'type' => 'RETURN',
            'quantity' => $quantity,
            'reference_type' => get_class($reference),
            'reference_id' => $reference->id,
            'movement_date' => $reference->return_date ?? now()->toDateString(),
        ]);
    }

    /**
     * سجل تحويل بين فرعين
     */
    public function recordTransfer(
        int $productId,
        int $fromBranchId,
        int $toBranchId,
        int $quantity,
        $reference
    ): array {
        return DB::transaction(function () use ($productId, $fromBranchId, $toBranchId, $quantity, $reference) {
            // Record OUT from source branch
            $movementOut = $this->recordMovement([
                'product_id' => $productId,
                'branch_id' => $fromBranchId,
                'type' => 'TRANSFER_OUT',
                'quantity' => $quantity,
                'reference_type' => get_class($reference),
                'reference_id' => $reference->id,
                'movement_date' => $reference->transfer_date ?? now()->toDateString(),
                'notes' => "Transfer to Branch #{$toBranchId}",
            ]);

            // Record IN to destination branch
            $movementIn = $this->recordMovement([
                'product_id' => $productId,
                'branch_id' => $toBranchId,
                'type' => 'TRANSFER_IN',
                'quantity' => $quantity,
                'reference_type' => get_class($reference),
                'reference_id' => $reference->id,
                'movement_date' => $reference->transfer_date ?? now()->toDateString(),
                'notes' => "Transfer from Branch #{$fromBranchId}",
            ]);

            return [
                'out' => $movementOut,
                'in' => $movementIn,
            ];
        });
    }

    /**
     * احصل على كارت الصنف (Product Card)
     */
    public function getProductCard(int $productId, int $branchId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = InventoryMovement::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->orderBy('movement_date')
            ->orderBy('created_at');

        if ($fromDate) {
            $query->where('movement_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('movement_date', '<=', $toDate);
        }

        $movements = $query->with(['reference', 'creator'])->get();

        // Get opening balance
        $openingBalance = $this->getOpeningBalance($productId, $branchId, $fromDate);

        return [
            'product_id' => $productId,
            'branch_id' => $branchId,
            'opening_balance' => $openingBalance,
            'movements' => $movements,
            'closing_balance' => $movements->last()?->running_balance ?? $openingBalance,
            'total_in' => $movements->where('quantity', '>', 0)->sum('quantity'),
            'total_out' => abs($movements->where('quantity', '<', 0)->sum('quantity')),
        ];
    }

    /**
     * احصل على الرصيد الافتتاحي
     */
    private function getOpeningBalance(int $productId, int $branchId, ?string $asOfDate = null): int
    {
        if (!$asOfDate) {
            // No movements yet, so opening balance is 0
            return 0;
        }

        // Get the last movement before the from_date
        $lastMovement = InventoryMovement::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('movement_date', '<', $asOfDate)
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastMovement?->running_balance ?? 0;
    }

    /**
     * احصل على ملخص الحركات
     */
    public function getMovementsSummary(int $branchId, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = InventoryMovement::where('branch_id', $branchId);

        if ($fromDate && $toDate) {
            $query->whereBetween('movement_date', [$fromDate, $toDate]);
        }

        return [
            'total_movements' => $query->count(),
            'total_in' => $query->where('quantity', '>', 0)->sum('quantity'),
            'total_out' => abs($query->where('quantity', '<', 0)->sum('quantity')),
            'by_type' => $query->selectRaw('type, COUNT(*) as count, SUM(ABS(quantity)) as total')
                ->groupBy('type')
                ->get(),
        ];
    }
}
```

---

### 🎮 Step 3: API Layer (2 ساعات)

```php
// app/Http/Controllers/Api/V1/InventoryMovementController.php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InventoryMovementService;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    public function __construct(
        private InventoryMovementService $movementService
    ) {}

    /**
     * Get Product Card (كارت الصنف)
     * GET /api/v1/inventory/movements/product-card
     */
    public function getProductCard(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $card = $this->movementService->getProductCard(
            $request->product_id,
            $request->branch_id,
            $request->from_date,
            $request->to_date
        );

        return response()->json($card);
    }

    /**
     * Get Movements Summary
     * GET /api/v1/inventory/movements/summary
     */
    public function getSummary(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $summary = $this->movementService->getMovementsSummary(
            $request->branch_id,
            $request->from_date,
            $request->to_date
        );

        return response()->json($summary);
    }

    /**
     * Get Movements List
     * GET /api/v1/inventory/movements
     */
    public function index(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'nullable|in:IN,OUT,TRANSFER_OUT,TRANSFER_IN,RETURN,ADJUSTMENT',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = \App\Models\InventoryMovement::with(['product', 'branch', 'reference', 'creator']);

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('movement_date', [$request->from_date, $request->to_date]);
        }

        $movements = $query->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($movements);
    }
}
```

#### Routes
```php
// routes/api.php
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::prefix('inventory/movements')->group(function () {
        Route::get('/', [InventoryMovementController::class, 'index']);
        Route::get('/product-card', [InventoryMovementController::class, 'getProductCard']);
        Route::get('/summary', [InventoryMovementController::class, 'getSummary']);
    });
});
```

---

### 🔄 Step 4: Integration with Existing Systems (3 ساعات)

#### Update IssueVoucherService
```php
// app/Services/IssueVoucherService.php

use App\Services\InventoryMovementService;

class IssueVoucherService
{
    public function __construct(
        private InventoryMovementService $movementService
    ) {}

    public function approve(IssueVoucher $voucher): IssueVoucher
    {
        return DB::transaction(function () use ($voucher) {
            // ... existing approval logic ...

            // Record inventory movements for each item
            foreach ($voucher->items as $item) {
                $this->movementService->recordIssue(
                    $item->product_id,
                    $voucher->branch_id,
                    $item->quantity,
                    $voucher
                );
            }

            // ... rest of approval logic ...

            return $voucher;
        });
    }
}
```

#### Update ReturnVoucherService
```php
// app/Services/ReturnVoucherService.php

public function approve(ReturnVoucher $voucher): ReturnVoucher
{
    return DB::transaction(function () use ($voucher) {
        // ... existing approval logic ...

        // Record inventory movements
        foreach ($voucher->items as $item) {
            $this->movementService->recordReturn(
                $item->product_id,
                $voucher->branch_id,
                $item->quantity,
                $voucher
            );
        }

        // ... rest of logic ...

        return $voucher;
    });
}
```

---

### 🧪 Step 5: Unit Testing (4 ساعات)

```php
// tests/Unit/Services/InventoryMovementServiceTest.php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\InventoryMovementService;
use App\Models\{Product, Branch, ProductBranchStock, InventoryMovement};
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryMovementServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryMovementService $service;
    private Product $product;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryMovementService();
        
        $this->product = Product::factory()->create();
        $this->branch = Branch::factory()->create();
        
        ProductBranchStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 100,
        ]);
    }

    /** @test */
    public function it_records_in_movement()
    {
        $movement = $this->service->recordMovement([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'type' => 'IN',
            'quantity' => 50,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'type' => 'IN',
            'quantity' => 50,
            'running_balance' => 150,
        ]);

        $this->assertEquals(150, $this->product->fresh()->stock()->first()->current_stock);
    }

    /** @test */
    public function it_records_out_movement()
    {
        $movement = $this->service->recordMovement([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'type' => 'OUT',
            'quantity' => 30,
        ]);

        $this->assertEquals(-30, $movement->quantity);
        $this->assertEquals(70, $movement->running_balance);
    }

    /** @test */
    public function it_prevents_negative_stock()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->service->recordMovement([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'type' => 'OUT',
            'quantity' => 150, // More than available (100)
        ]);
    }

    /** @test */
    public function it_calculates_running_balance_correctly()
    {
        // Initial: 100
        $this->service->recordMovement([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'type' => 'IN',
            'quantity' => 50, // +50 = 150
        ]);

        $this->service->recordMovement([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'type' => 'OUT',
            'quantity' => 30, // -30 = 120
        ]);

        $this->service->recordMovement([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'type' => 'RETURN',
            'quantity' => 10, // +10 = 130
        ]);

        $lastMovement = InventoryMovement::latest()->first();
        $this->assertEquals(130, $lastMovement->running_balance);
    }

    /** @test */
    public function it_records_transfer_between_branches()
    {
        $branch2 = Branch::factory()->create();
        ProductBranchStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $branch2->id,
            'current_stock' => 50,
        ]);

        $reference = new \stdClass();
        $reference->id = 1;

        $movements = $this->service->recordTransfer(
            $this->product->id,
            $this->branch->id,
            $branch2->id,
            20,
            $reference
        );

        $this->assertArrayHasKey('out', $movements);
        $this->assertArrayHasKey('in', $movements);
        
        // Check branch 1 (OUT)
        $this->assertEquals(80, ProductBranchStock::where('branch_id', $this->branch->id)->first()->current_stock);
        
        // Check branch 2 (IN)
        $this->assertEquals(70, ProductBranchStock::where('branch_id', $branch2->id)->first()->current_stock);
    }

    /** @test */
    public function it_gets_product_card()
    {
        // Create multiple movements
        $this->service->recordMovement([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'type' => 'IN',
            'quantity' => 50,
        ]);

        $this->service->recordMovement([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'type' => 'OUT',
            'quantity' => 30,
        ]);

        $card = $this->service->getProductCard(
            $this->product->id,
            $this->branch->id
        );

        $this->assertEquals(100, $card['opening_balance']);
        $this->assertEquals(120, $card['closing_balance']); // 100 + 50 - 30
        $this->assertEquals(50, $card['total_in']);
        $this->assertEquals(30, $card['total_out']);
        $this->assertCount(2, $card['movements']);
    }
}
```

**عدد الاختبارات:** 15+ unit tests

---

### 🔗 Step 6: Integration Testing (3 ساعات)

```php
// tests/Integration/InventoryMovementsIntegrationTest.php
<?php

// Will continue in Part 2...
```

---

## ✅ Success Criteria - TASK-B01

- [ ] جدول `inventory_movements` created and migrated
- [ ] `InventoryMovement` Model working
- [ ] `InventoryMovementService` implemented
- [ ] API endpoints working
- [ ] Integration with IssueVoucher ✅
- [ ] Integration with ReturnVoucher ✅
- [ ] Unit Tests: 15/15 passing
- [ ] Integration Tests: 10/10 passing
- [ ] Running Balance accurate
- [ ] Product Card API working
- [ ] No negative stock possible
- [ ] Performance: < 50ms per movement

---

**📄 الجزء الثاني في الملف التالي...**
