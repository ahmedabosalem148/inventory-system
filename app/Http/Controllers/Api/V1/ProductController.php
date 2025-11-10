<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use AuthorizesRequests;
    /**
     * عرض قائمة المنتجات مع فلترة وبحث متقدم
     * 
     * GET /api/v1/products
     * Query params: ?search=led&category_id=1&product_classification=parts&is_active=1&per_page=15
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::with(['category']);

        // البحث بالاسم
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        // فلترة حسب التصنيف
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // فلترة حسب نوع المنتج (classification)
        if ($request->filled('product_classification')) {
            $query->byClassification($request->product_classification);
        }

        // فلترة حسب الحالة
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // الترتيب
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100); // max 100
        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    /**
     * حفظ منتج جديد
     * 
     * POST /api/v1/products
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Auto-generate SKU based on classification
            $classification = $validated['product_classification'];
            $prefix = Product::CLASSIFICATION_SKU_PREFIXES[$classification];
            
            // Get next number from last SKU of same classification
            $lastProduct = Product::where('product_classification', $classification)
                ->where('sku', 'like', $prefix . '-%')
                ->orderBy('id', 'desc')
                ->first();
            
            if ($lastProduct && $lastProduct->sku) {
                $lastNumber = (int) substr($lastProduct->sku, strlen($prefix) + 1);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }
            
            $validated['sku'] = $prefix . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            
            // إنشاء المنتج
            $product = Product::create($validated);

            // إضافة المخزون الأولي والحدود الدنيا للفروع
            $branches = \App\Models\Branch::all();
            $branchMinData = [
                'FAC' => $request->branch_min_qty_factory ?? 0,
                'ATB' => $request->branch_min_qty_ataba ?? 0,
                'IMB' => $request->branch_min_qty_imbaba ?? 0,
            ];

            foreach ($branches as $branch) {
                // التحقق من صلاحية المستخدم على كل مخزن
                if (!$user->hasRole('super-admin') && !$user->hasFullAccessToBranch($branch->id)) {
                    // للمستخدمين غير super-admin، يجب أن يكون للفرع صلاحية full_access
                    // وإلا نتحقق من initial_stock - إن كان يحاول إضافة مخزون لفرع غير مصرح له
                    $hasInitialStockForBranch = false;
                    if ($request->filled('initial_stock')) {
                        foreach ($request->initial_stock as $stock) {
                            if ($stock['branch_id'] == $branch->id && $stock['quantity'] > 0) {
                                $hasInitialStockForBranch = true;
                                break;
                            }
                        }
                    }
                    
                    // إذا حاول إضافة مخزون لفرع غير مصرح له، نرفض الطلب
                    if ($hasInitialStockForBranch) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'ليس لديك صلاحية لإضافة مخزون في الفرع: ' . $branch->name,
                        ], 403);
                    }
                    
                    continue; // تجاهل الفروع التي لا يملك المستخدم صلاحية عليها
                }

                // البحث عن المخزون الأولي لهذا الفرع
                $initialStock = 0;
                if ($request->filled('initial_stock')) {
                    foreach ($request->initial_stock as $stock) {
                        if ($stock['branch_id'] == $branch->id) {
                            $initialStock = $stock['quantity'];
                            break;
                        }
                    }
                }

                // إنشاء سجل المخزون للفرع مع الحد الأدنى
                $product->branchStocks()->create([
                    'branch_id' => $branch->id,
                    'current_stock' => $initialStock,
                    'min_qty' => $branchMinData[$branch->code] ?? 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => __('product.messages.created'),
                'data' => ProductResource::make($product->load(['category', 'branchStocks.branch'])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => __('product.messages.create_error', ['error' => $e->getMessage()]),
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

        /**
     * عرض منتج واحد مع رصيد الفروع
     * 
     * GET /api/v1/products/{id}
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);
        
        return response()->json([
            'data' => ProductResource::make($product),
        ], 200);
    }

    /**
     * تحديث بيانات منتج
     * 
     * PUT/PATCH /api/v1/products/{id}
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Note: SKU لا يتغير عند التحديث (auto-generated once at creation)
            unset($validated['sku']);
            
            // تحديث بيانات المنتج
            $product->update($validated);

            DB::commit();

            return response()->json([
                'message' => __('product.messages.updated'),
                'data' => ProductResource::make($product->fresh(['category', 'branchStocks.branch'])),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'خطأ في تحديث المنتج',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * حذف منتج
     * 
     * DELETE /api/v1/products/{id}
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        try {
            // التحقق من وجود رصيد
            $totalStock = $product->branchStocks()->sum('current_stock');
            if ($totalStock > 0) {
                return response()->json([
                    'message' => __('product.messages.delete_stock', ['qty' => $totalStock]),
                ], 422);
            }

            // التحقق من وجود حركات مخزنية
            $hasMovements = DB::table('inventory_movements')
                ->where('product_id', $product->id)
                ->exists();
            
            if ($hasMovements) {
                return response()->json([
                    'message' => __('product.messages.delete_movements'),
                ], 422);
            }

            $product->delete();

            return response()->json([
                'message' => __('product.messages.deleted'),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => __('product.messages.delete_error', ['error' => $e->getMessage()]),
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * Get minimum stock levels for a product across all branches
     * 
     * GET /api/v1/products/{id}/branch-min-stock
     */
    public function getBranchMinStock(Product $product): JsonResponse
    {
        $branchStocks = $product->branches()
            ->withPivot('current_stock', 'min_qty')
            ->get()
            ->map(function ($branch) {
                return [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'current_stock' => $branch->pivot->current_stock,
                    'min_qty' => $branch->pivot->min_qty,
                    'is_low' => $branch->pivot->current_stock < $branch->pivot->min_qty,
                ];
            });

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
            ],
            'branch_stocks' => $branchStocks,
        ]);
    }

    /**
     * Update minimum stock level for a product in a specific branch
     * 
     * PUT /api/v1/products/{id}/branch-min-stock
     * Body: { "branch_id": 1, "min_qty": 10 }
     */
    public function updateBranchMinStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'min_qty' => 'required|integer|min:0',
        ]);

        try {
            // Update or create the pivot record
            $product->branches()->syncWithoutDetaching([
                $validated['branch_id'] => [
                    'min_qty' => $validated['min_qty'],
                ],
            ]);

            return response()->json([
                'message' => 'تم تحديث الحد الأدنى بنجاح',
                'min_qty' => $validated['min_qty'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل تحديث الحد الأدنى',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }
}
