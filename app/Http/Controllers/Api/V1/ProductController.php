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

            // إضافة المخزون الأولي
            if ($request->filled('initial_stock')) {
                foreach ($request->initial_stock as $stock) {
                    // التحقق من صلاحية المستخدم على كل مخزن
                    if (!$user->hasRole('super-admin') && !$user->hasFullAccessToBranch($stock['branch_id'])) {
                        DB::rollBack();
                        $branch = \App\Models\Branch::find($stock['branch_id']);
                        return response()->json([
                            'message' => 'ليس لديك صلاحية كاملة لإضافة مخزون في الفرع: ' . ($branch ? $branch->name : $stock['branch_id']),
                        ], 403);
                    }
                    
                    $product->branchStocks()->create([
                        'branch_id' => $stock['branch_id'],
                        'current_stock' => $stock['quantity'],
                    ]);
                }
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

        $product->update($validated);

        return response()->json([
            'message' => __('product.messages.updated'),
            'data' => ProductResource::make($product->fresh(['category', 'branchStocks.branch'])),
        ], 200);
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
}
