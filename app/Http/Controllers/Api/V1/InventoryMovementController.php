<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InventoryMovementController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * عرض قائمة حركات المخزون مع فلترة متقدمة
     * 
     * GET /api/v1/inventory-movements
     * Query params: ?branch_id=1&product_id=2&movement_type=ISSUE&per_page=15
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryMovement::with(['product', 'branch']);

        // فلترة حسب الفرع
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // فلترة حسب المنتج
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // فلترة حسب نوع الحركة
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        // فلترة حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // الترتيب
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $movements = $query->paginate($perPage);

        return response()->json([
            'data' => $movements->items(),
            'meta' => [
                'current_page' => $movements->currentPage(),
                'total' => $movements->total(),
                'per_page' => $movements->perPage(),
                'last_page' => $movements->lastPage(),
            ]
        ]);
    }

    /**
     * إضافة منتج إلى المخزون
     * 
     * POST /api/v1/inventory-movements/add
     */
    public function addStock(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'nullable|numeric|min:0',
            'in_packs' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطأ في البيانات',
                'errors' => $validator->errors(),
            ], 422);
        }

        // التحقق من الصلاحيات
        if (!$user->hasRole('super-admin') && !$user->hasFullAccessToBranch($request->branch_id)) {
            return response()->json([
                'message' => 'ليس لديك صلاحية لإضافة مخزون في هذا الفرع',
            ], 403);
        }

        try {
            $metadata = [
                'unit_price' => $request->unit_price,
                'in_packs' => $request->boolean('in_packs'),
                'reference_type' => 'manual_addition',
            ];

            $movement = $this->inventoryService->addProduct(
                $request->product_id,
                $request->branch_id,
                $request->quantity,
                $request->notes ?? 'إضافة يدوية للمخزون',
                $metadata
            );

            return response()->json([
                'message' => 'تم إضافة المنتج للمخزون بنجاح',
                'data' => $movement->load(['product', 'branch']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في إضافة المنتج للمخزون',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * صرف منتج من المخزون
     * 
     * POST /api/v1/inventory-movements/issue
     */
    public function issueStock(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطأ في البيانات',
                'errors' => $validator->errors(),
            ], 422);
        }

        // التحقق من الصلاحيات
        if (!$user->hasRole('super-admin') && !$user->hasFullAccessToBranch($request->branch_id)) {
            return response()->json([
                'message' => 'ليس لديك صلاحية لصرف مخزون من هذا الفرع',
            ], 403);
        }

        try {
            $metadata = [
                'reference_type' => 'manual_issue',
            ];

            $movement = $this->inventoryService->issueProduct(
                $request->product_id,
                $request->branch_id,
                $request->quantity,
                $request->notes ?? 'صرف يدوي من المخزون',
                $metadata
            );

            return response()->json([
                'message' => 'تم صرف المنتج من المخزون بنجاح',
                'data' => $movement->load(['product', 'branch']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * تحويل منتج بين الفروع
     * 
     * POST /api/v1/inventory-movements/transfer
     */
    public function transferStock(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطأ في البيانات',
                'errors' => $validator->errors(),
            ], 422);
        }

        // التحقق من الصلاحيات على كلا الفرعين
        if (!$user->hasRole('super-admin')) {
            if (!$user->hasFullAccessToBranch($request->from_branch_id) || 
                !$user->hasFullAccessToBranch($request->to_branch_id)) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية لتحويل المخزون بين هذين الفرعين',
                ], 403);
            }
        }

        try {
            $movements = $this->inventoryService->transferProduct(
                $request->product_id,
                $request->from_branch_id,
                $request->to_branch_id,
                $request->quantity,
                $request->notes ?? 'تحويل بين الفروع'
            );

            return response()->json([
                'message' => 'تم تحويل المنتج بين الفروع بنجاح',
                'data' => [
                    'out_movement' => $movements['out']->load(['product', 'branch']),
                    'in_movement' => $movements['in']->load(['product', 'branch']),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * تسوية المخزون (bulk stock adjustment)
     * 
     * POST /api/v1/inventory-movements/adjust
     */
    public function adjustStock(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'adjustments' => 'required|array|min:1',
            'adjustments.*.product_id' => 'required|exists:products,id',
            'adjustments.*.branch_id' => 'required|exists:branches,id',
            'adjustments.*.new_quantity' => 'required|numeric|min:0',
            'adjustments.*.notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطأ في البيانات',
                'errors' => $validator->errors(),
            ], 422);
        }

        // التحقق من الصلاحيات لكل فرع
        $branchIds = array_unique(array_column($request->adjustments, 'branch_id'));
        if (!$user->hasRole('super-admin')) {
            foreach ($branchIds as $branchId) {
                if (!$user->hasFullAccessToBranch($branchId)) {
                    return response()->json([
                        'message' => 'ليس لديك صلاحية لتسوية المخزون في أحد الفروع المحددة',
                    ], 403);
                }
            }
        }

        try {
            $movements = $this->inventoryService->bulkStockAdjustment($request->adjustments);

            return response()->json([
                'message' => 'تم تسوية المخزون بنجاح',
                'data' => $movements,
                'adjusted_count' => count($movements),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطأ في تسوية المخزون',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * عرض تفاصيل حركة واحدة
     * 
     * GET /api/v1/inventory-movements/{id}
     */
    public function show(Request $request, InventoryMovement $inventoryMovement): JsonResponse
    {
        $user = $request->user();
        
        // التحقق من الصلاحية على الفرع
        if (!$user->hasRole('super-admin') && !$user->hasAccessToBranch($inventoryMovement->branch_id)) {
            return response()->json([
                'message' => 'ليس لديك صلاحية لعرض هذه الحركة',
            ], 403);
        }

        return response()->json([
            'data' => $inventoryMovement->load(['product', 'branch']),
        ]);
    }

    /**
     * تقرير ملخص المخزون
     * 
     * GET /api/v1/inventory-movements/reports/summary
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $branchId = $request->branch_id;
            $summary = $this->inventoryService->getInventorySummary($branchId);
            
            return response()->json([
                'data' => $summary,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in inventory summary: ' . $e->getMessage());
            return response()->json([
                'message' => 'خطأ في إنشاء التقرير',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * قائمة المنتجات المنخفضة المخزون
     * 
     * GET /api/v1/inventory-movements/reports/low-stock
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $branchId = $request->branch_id;
            
            if ($branchId) {
                // فرع محدد
                $lowStockProducts = $this->inventoryService->getProductsBelowMinQuantity($branchId);
            } else {
                // كل الفروع
                $branches = \App\Models\Branch::all();
                $lowStockProducts = collect();
                
                foreach ($branches as $branch) {
                    $branchLowStock = $this->inventoryService->getProductsBelowMinQuantity($branch->id);
                    $lowStockProducts = $lowStockProducts->merge($branchLowStock);
                }
            }
            
            return response()->json([
                'data' => $lowStockProducts,
                'count' => $lowStockProducts->count(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in low stock report: ' . $e->getMessage());
            return response()->json([
                'message' => 'خطأ في استرجاع بيانات المخزون المنخفض',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }
}