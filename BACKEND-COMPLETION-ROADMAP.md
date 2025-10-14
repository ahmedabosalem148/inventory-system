# 🚀 خارطة طريق إكمال الـ Backend بنسبة 100%
## Backend Completion Roadmap - Complete Plan

**تاريخ الإنشاء:** 14 أكتوبر 2025  
**المدة الإجمالية:** 2-3 أسابيع  
**الهدف:** إكمال Backend بنسبة 100% مع اختبارات شاملة  
**الحالة:** 🟢 Ready to Start

---

## 📊 ملخص تنفيذي

### الحالة الحالية:
- ✅ **Backend مكتمل:** 70% (7/10 أنظمة)
- ❌ **Backend ناقص:** 30% (3/10 أنظمة حرجة)
- ⚠️ **Tested:** 62/62 tests passing (لكن بدون Movements/Sequencing/Transfers)

### الهدف النهائي:
- 🎯 **Backend:** 100% مكتمل
- 🎯 **Unit Tests:** 200+ tests (100% passing)
- 🎯 **Integration Tests:** 50+ scenarios (100% passing)
- 🎯 **Critical Bugs:** 0
- 🎯 **Production Ready:** ✅

---

## 📋 المراحل والتاسكات

| # | Task | المدة | الأولوية | الحالة |
|---|------|-------|----------|--------|
| **PHASE 1: Core Missing Features** | | | | |
| B01 | Inventory Movements System | 3 أيام | 🔴 P0 | ⬜ Not Started |
| B02 | Sequencing System | 2 يوم | 🔴 P0 | ⬜ Not Started |
| B03 | Negative Stock Enhancement | 1 يوم | 🟠 P1 | ⬜ Not Started |
| **PHASE 2: Testing & Verification** | | | | |
| B04 | Branch Transfers Testing | 1 يوم | 🔴 P0 | ⬜ Not Started |
| B05 | Full System Integration | 2 أيام | 🔴 P0 | ⬜ Not Started |
| B06 | Performance & Load Testing | 1 يوم | 🟠 P1 | ⬜ Not Started |
| B07 | Security Hardening | 1 يوم | 🟠 P1 | ⬜ Not Started |
| **PHASE 3: Additional Features** | | | | |
| B08 | Activity Log System | 2 أيام | 🟡 P2 | ⬜ Not Started |
| B09 | Pack Size Validation | 1 يوم | 🟡 P2 | ⬜ Not Started |
| **PHASE 4: Final Integration** | | | | |
| B10 | Complete E2E Testing | 1 يوم | 🟢 P3 | ⬜ Not Started |
| B11 | Documentation | 1 يوم | 🟢 P3 | ⬜ Not Started |
| B12 | Production Checklist | 0.5 يوم | 🟢 P3 | ⬜ Not Started |

**الإجمالي:** 12 تاسك | 16.5 يوم عمل | 200+ اختبار

---

# 🔴 PHASE 1: Core Missing Features

## 📦 TASK-B01: Inventory Movements System

### 📌 نظرة عامة
- **المدة:** 3 أيام
- **الأولوية:** 🔴 P0 - Critical
- **الهدف:** تطوير نظام كامل لتتبع حركات المخزون مع رصيد متحرك

### 📋 المتطلبات الوظيفية
1. ✅ تسجيل كل حركة مخزون تلقائياً (IN, OUT, TRANSFER, RETURN, ADJUSTMENT)
2. ✅ حساب الرصيد المتحرك (Running Balance) بعد كل حركة
3. ✅ ربط كل حركة بمستندها الأصلي (IssueVoucher, ReturnVoucher, etc)
4. ✅ تقرير كارت الصنف الكامل (Product Card)
5. ✅ API للاستعلام عن الحركات

### 🗄️ Step 1: Database Design (2 ساعات)

#### 1.1 Migration - Inventory Movements Table
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
            
            // Product & Branch References
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            
            // Movement Details
            $table->enum('type', ['IN', 'OUT', 'TRANSFER_OUT', 'TRANSFER_IN', 'RETURN', 'ADJUSTMENT'])
                  ->comment('نوع الحركة');
            $table->integer('quantity')->comment('الكمية: موجب للإضافة، سالب للخصم');
            $table->integer('running_balance')->comment('الرصيد بعد الحركة');
            
            // Reference to Source Document
            $table->string('reference_type')->nullable()->comment('نوع المستند المصدر');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('معرف المستند');
            
            // Additional Information
            $table->date('movement_date')->comment('تاريخ الحركة');
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->string('batch_number')->nullable()->comment('رقم الدفعة (اختياري)');
            
            // Audit Trail
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('المستخدم المنفذ');
            $table->timestamps();
            
            // Performance Indexes
            $table->index(['product_id', 'branch_id', 'movement_date'], 'idx_product_branch_date');
            $table->index(['reference_type', 'reference_id'], 'idx_reference');
            $table->index('movement_date', 'idx_movement_date');
            $table->index(['product_id', 'branch_id'], 'idx_product_branch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
```

#### 1.2 Model - InventoryMovement
```php
// app/Models/InventoryMovement.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    use HasFactory;

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

    // ==================== Relationships ====================
    
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

    // ==================== Scopes ====================
    
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

    public function scopeIncoming($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeOutgoing($query)
    {
        return $query->where('quantity', '<', 0);
    }

    // ==================== Helpers ====================
    
    public function isIncoming(): bool
    {
        return $this->quantity > 0;
    }

    public function isOutgoing(): bool
    {
        return $this->quantity < 0;
    }

    public function getAbsoluteQuantityAttribute(): int
    {
        return abs($this->quantity);
    }

    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'IN' => 'إضافة',
            'OUT' => 'صرف',
            'TRANSFER_OUT' => 'تحويل - خارج',
            'TRANSFER_IN' => 'تحويل - داخل',
            'RETURN' => 'مرتجع',
            'ADJUSTMENT' => 'تسوية',
            default => $this->type,
        };
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
use Illuminate\Support\Collection;
use Carbon\Carbon;

class InventoryMovementService
{
    /**
     * تسجيل حركة مخزون جديدة
     * 
     * @param array $data
     * @return InventoryMovement
     * @throws \Exception
     */
    public function recordMovement(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {
            // 1. Get current stock with lock (prevent race condition)
            $stock = ProductBranchStock::where('product_id', $data['product_id'])
                ->where('branch_id', $data['branch_id'])
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                throw new \Exception("Product stock record not found for this branch");
            }

            // 2. Normalize quantity (negative for OUT movements)
            $quantity = $this->normalizeQuantity($data['type'], $data['quantity']);

            // 3. Calculate new running balance
            $runningBalance = $stock->current_stock + $quantity;

            // 4. Prevent negative stock
            if ($runningBalance < 0) {
                throw new \Exception(
                    "Insufficient stock. Available: {$stock->current_stock}, Required: " . abs($quantity)
                );
            }

            // 5. Create movement record
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

            // 6. Update current stock
            $stock->update(['current_stock' => $runningBalance]);

            return $movement->load(['product', 'branch', 'creator']);
        });
    }

    /**
     * Normalize quantity based on movement type
     */
    private function normalizeQuantity(string $type, int $quantity): int
    {
        $outgoingTypes = ['OUT', 'TRANSFER_OUT'];
        
        if (in_array($type, $outgoingTypes)) {
            return -abs($quantity); // Force negative
        }
        
        return abs($quantity); // Force positive
    }

    /**
     * تسجيل حركة صرف (Issue)
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
            'notes' => "صرف - فاتورة رقم {$reference->id}",
        ]);
    }

    /**
     * تسجيل حركة إرجاع (Return)
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
            'notes' => "مرتجع - إذن رقم {$reference->id}",
        ]);
    }

    /**
     * تسجيل تحويل بين فرعين
     */
    public function recordTransfer(
        int $productId,
        int $fromBranchId,
        int $toBranchId,
        int $quantity,
        $reference
    ): array {
        return DB::transaction(function () use ($productId, $fromBranchId, $toBranchId, $quantity, $reference) {
            // 1. Record OUT from source branch
            $movementOut = $this->recordMovement([
                'product_id' => $productId,
                'branch_id' => $fromBranchId,
                'type' => 'TRANSFER_OUT',
                'quantity' => $quantity,
                'reference_type' => get_class($reference),
                'reference_id' => $reference->id,
                'movement_date' => $reference->transfer_date ?? now()->toDateString(),
                'notes' => "تحويل إلى الفرع #{$toBranchId}",
            ]);

            // 2. Record IN to destination branch
            $movementIn = $this->recordMovement([
                'product_id' => $productId,
                'branch_id' => $toBranchId,
                'type' => 'TRANSFER_IN',
                'quantity' => $quantity,
                'reference_type' => get_class($reference),
                'reference_id' => $reference->id,
                'movement_date' => $reference->transfer_date ?? now()->toDateString(),
                'notes' => "تحويل من الفرع #{$fromBranchId}",
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
    public function getProductCard(
        int $productId,
        int $branchId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        // Build query
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

        // Calculate totals
        $totalIn = $movements->where('quantity', '>', 0)->sum('quantity');
        $totalOut = abs($movements->where('quantity', '<', 0)->sum('quantity'));
        $closingBalance = $movements->last()?->running_balance ?? $openingBalance;

        return [
            'product_id' => $productId,
            'branch_id' => $branchId,
            'date_range' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'opening_balance' => $openingBalance,
            'movements' => $movements,
            'closing_balance' => $closingBalance,
            'totals' => [
                'in' => $totalIn,
                'out' => $totalOut,
                'net' => $totalIn - $totalOut,
            ],
            'summary' => [
                'total_movements' => $movements->count(),
                'by_type' => $movements->groupBy('type')->map->count(),
            ],
        ];
    }

    /**
     * احصل على الرصيد الافتتاحي
     */
    private function getOpeningBalance(int $productId, int $branchId, ?string $asOfDate = null): int
    {
        if (!$asOfDate) {
            return 0; // No date filter, start from zero
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
    public function getMovementsSummary(
        int $branchId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $query = InventoryMovement::where('branch_id', $branchId);

        if ($fromDate && $toDate) {
            $query->whereBetween('movement_date', [$fromDate, $toDate]);
        }

        $totalIn = (clone $query)->where('quantity', '>', 0)->sum('quantity');
        $totalOut = abs((clone $query)->where('quantity', '<', 0)->sum('quantity'));

        return [
            'branch_id' => $branchId,
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'totals' => [
                'movements' => $query->count(),
                'in' => $totalIn,
                'out' => $totalOut,
                'net' => $totalIn - $totalOut,
            ],
            'by_type' => $query->selectRaw('type, COUNT(*) as count, SUM(ABS(quantity)) as total')
                ->groupBy('type')
                ->get()
                ->mapWithKeys(fn($item) => [$item->type => [
                    'count' => $item->count,
                    'total' => $item->total,
                ]]),
        ];
    }

    /**
     * احصل على المنتجات الأكثر حركة
     */
    public function getMostActiveProducts(int $branchId, int $limit = 10): Collection
    {
        return InventoryMovement::where('branch_id', $branchId)
            ->selectRaw('product_id, COUNT(*) as movement_count, SUM(ABS(quantity)) as total_quantity')
            ->groupBy('product_id')
            ->orderByDesc('movement_count')
            ->limit($limit)
            ->with('product')
            ->get();
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
use Illuminate\Http\JsonResponse;

class InventoryMovementController extends Controller
{
    public function __construct(
        private InventoryMovementService $movementService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get Product Card (كارت الصنف)
     * GET /api/v1/inventory/movements/product-card
     */
    public function getProductCard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $card = $this->movementService->getProductCard(
            $validated['product_id'],
            $validated['branch_id'],
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $card,
        ]);
    }

    /**
     * Get Movements Summary
     * GET /api/v1/inventory/movements/summary
     */
    public function getSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $summary = $this->movementService->getMovementsSummary(
            $validated['branch_id'],
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get Movements List
     * GET /api/v1/inventory/movements
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'nullable|in:IN,OUT,TRANSFER_OUT,TRANSFER_IN,RETURN,ADJUSTMENT',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = \App\Models\InventoryMovement::with(['product', 'branch', 'reference', 'creator']);

        // Apply filters
        if (!empty($validated['product_id'])) {
            $query->where('product_id', $validated['product_id']);
        }

        if (!empty($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['from_date']) && !empty($validated['to_date'])) {
            $query->whereBetween('movement_date', [$validated['from_date'], $validated['to_date']]);
        }

        // Order and paginate
        $movements = $query->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($validated['per_page'] ?? 50);

        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }

    /**
     * Get Most Active Products
     * GET /api/v1/inventory/movements/most-active
     */
    public function getMostActive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $products = $this->movementService->getMostActiveProducts(
            $validated['branch_id'],
            $validated['limit'] ?? 10
        );

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
```

#### Routes
```php
// routes/api.php (add these routes)

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::prefix('inventory/movements')->group(function () {
        Route::get('/', [InventoryMovementController::class, 'index'])
            ->name('movements.index');
        
        Route::get('/product-card', [InventoryMovementController::class, 'getProductCard'])
            ->name('movements.product-card');
        
        Route::get('/summary', [InventoryMovementController::class, 'getSummary'])
            ->name('movements.summary');
        
        Route::get('/most-active', [InventoryMovementController::class, 'getMostActive'])
            ->name('movements.most-active');
    });
});
```

---

### 🔄 Step 4: Integration with Existing Systems (3 ساعات)

**الملف كبير جداً، سأكمل في رد منفصل مع بدء التنفيذ الفعلي...**

---

## ✅ Checklist - TASK-B01

- [ ] Migration created and executed
- [ ] Model created with relationships
- [ ] Service Layer implemented
- [ ] API endpoints working
- [ ] Routes registered
- [ ] Integration with IssueVoucher
- [ ] Integration with ReturnVoucher
- [ ] Unit Tests (15 tests)
- [ ] Integration Tests (10 tests)
- [ ] Documentation updated

---

**📄 يتبع في التنفيذ المباشر...**
