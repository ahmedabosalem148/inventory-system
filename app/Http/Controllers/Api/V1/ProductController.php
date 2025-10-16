<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * عرض قائمة المنتجات مع فلترة وبحث متقدم
     * 
     * GET /api/v1/products
     * Query params: ?search=led&category_id=1&is_active=1&per_page=15
     */
    public function index(Request $request): AnonymousResourceCollection
    {
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
     * 
     * Note: يحتاج full_access على المخزن
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // التحقق من الصلاحيات
        if (!$user->hasRole('super-admin')) {
            $activeBranch = $user->getActiveBranch();
            
            if (!$activeBranch || !$user->hasFullAccessToBranch($activeBranch->id)) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية كاملة لإضافة منتجات في هذا الفرع',
                ], 403);
            }
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:200|unique:products,name',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'pack_size' => 'nullable|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0|gte:purchase_price',
            'min_stock' => 'required|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'initial_stock' => 'nullable|array',
            'initial_stock.*.branch_id' => 'required|exists:branches,id',
            'initial_stock.*.quantity' => 'required|integer|min:0',
            'branch_min_qty_factory' => 'nullable|integer|min:0',
            'branch_min_qty_ataba' => 'nullable|integer|min:0',
            'branch_min_qty_imbaba' => 'nullable|integer|min:0',
        ], [
            'category_id.required' => __('product.validation.category_id.required'),
            'name.required' => __('product.validation.name.required'),
            'name.unique' => 'اسم المنتج موجود بالفعل',
            'unit.required' => __('product.validation.unit.required'),
            'purchase_price.required' => __('product.validation.purchase_price.required'),
            'sale_price.required' => __('product.validation.sale_price.required'),
            'sale_price.gte' => 'سعر البيع يجب أن يكون أكبر من أو يساوي سعر الشراء',
            'min_stock.required' => __('product.validation.min_stock.required'),
        ]);

        DB::beginTransaction();
        try {
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
     * عرض تفاصيل منتج واحد
     * 
     * GET /api/v1/products/{id}
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'branchStocks.branch']);
        
        return response()->json([
            'data' => ProductResource::make($product),
        ], 200);
    }

    /**
     * تحديث بيانات منتج
     * 
     * PUT/PATCH /api/v1/products/{id}
     * 
     * Note: يحتاج full_access
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();
        
        // التحقق من الصلاحيات
        if (!$user->hasRole('super-admin')) {
            $activeBranch = $user->getActiveBranch();
            
            if (!$activeBranch || !$user->hasFullAccessToBranch($activeBranch->id)) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية تعديل المنتجات',
                ], 403);
            }
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:200|unique:products,name,' . $product->id,
            'description' => 'nullable|string',
            'unit' => 'sometimes|string|max:50',
            'pack_size' => 'nullable|integer|min:1',
            'purchase_price' => 'sometimes|numeric|min:0',
            'sale_price' => 'sometimes|numeric|min:0',
            'min_stock' => 'sometimes|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'branch_min_qty_factory' => 'nullable|integer|min:0',
            'branch_min_qty_ataba' => 'nullable|integer|min:0',
            'branch_min_qty_imbaba' => 'nullable|integer|min:0',
        ], [
            'category_id.exists' => 'التصنيف غير موجود',
            'name.unique' => 'اسم المنتج موجود بالفعل',
            'sale_price.gte' => 'سعر البيع يجب أن يكون أكبر من أو يساوي سعر الشراء',
        ]);

        // التحقق من سعر البيع vs سعر الشراء
        if (isset($validated['sale_price']) && isset($validated['purchase_price'])) {
            if ($validated['sale_price'] < $validated['purchase_price']) {
                return response()->json([
                    'message' => 'خطأ في التحقق',
                    'errors' => [
                        'sale_price' => ['سعر البيع يجب أن يكون أكبر من أو يساوي سعر الشراء']
                    ]
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // تحديث بيانات المنتج الأساسية
            $productData = collect($validated)->except([
                'branch_min_qty_factory', 
                'branch_min_qty_ataba', 
                'branch_min_qty_imbaba'
            ])->toArray();
            
            $product->update($productData);

            // تحديث الحدود الدنيا للفروع إذا تم إرسالها
            if ($request->hasAny(['branch_min_qty_factory', 'branch_min_qty_ataba', 'branch_min_qty_imbaba'])) {
                $branchMinData = [
                    'FAC' => $request->branch_min_qty_factory,
                    'ATB' => $request->branch_min_qty_ataba,
                    'IMB' => $request->branch_min_qty_imbaba,
                ];

                $branches = \App\Models\Branch::all();
                foreach ($branches as $branch) {
                    if (isset($branchMinData[$branch->code]) && $branchMinData[$branch->code] !== null) {
                        $product->branchStocks()
                            ->where('branch_id', $branch->id)
                            ->update(['min_qty' => $branchMinData[$branch->code]]);
                    }
                }
            }

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
     * 
     * Note: يحتاج full_access + Admin فقط
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();
        
        // فقط Admin يقدر يحذف منتجات
        if (!$user->hasRole('super-admin')) {
            return response()->json([
                'message' => 'فقط المدير يمكنه حذف المنتجات',
            ], 403);
        }

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
