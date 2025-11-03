<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Http\Resources\Api\V1\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    /**
     * عرض قائمة الفروع
     * 
     * GET /api/v1/branches
     * Query: ?is_active=1&search=مصنع
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Branch::withCount('productStocks');

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or code
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $branches = $query->latest()->get();

        return BranchResource::collection($branches);
    }

    /**
     * إنشاء فرع جديد
     * 
     * POST /api/v1/branches
     */
    public function store(StoreBranchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $branch = Branch::create($validated);

            return response()->json([
                'message' => 'تم إنشاء الفرع بنجاح',
                'data' => BranchResource::make($branch),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء الفرع',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * عرض تفاصيل فرع واحد
     * 
     * GET /api/v1/branches/{id}
     */
    public function show(Branch $branch): JsonResponse
    {
        $branch->loadCount('productStocks');
        $branch->load('productStocks.product');

        return response()->json([
            'data' => BranchResource::make($branch),
        ], 200);
    }

    /**
     * تحديث بيانات فرع
     * 
     * PUT/PATCH /api/v1/branches/{id}
     */
    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $validated = $request->validated();

        try {
            $branch->update($validated);

            return response()->json([
                'message' => 'تم تحديث الفرع بنجاح',
                'data' => BranchResource::make($branch->fresh()),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء تحديث الفرع',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }

    /**
     * حذف فرع
     * 
     * DELETE /api/v1/branches/{id}
     */
    public function destroy(Branch $branch): JsonResponse
    {
        try {
            // منع حذف الفروع الأساسية
            if (in_array($branch->code, ['FAC', 'ATB', 'IMB'])) {
                return response()->json([
                    'message' => 'لا يمكن حذف الفروع الأساسية (المصنع، العتبة، إمبابة)',
                ], 422);
            }

            // التحقق من وجود مخزون
            $hasStock = $branch->productStocks()
                ->where('current_stock', '>', 0)
                ->exists();

            if ($hasStock) {
                return response()->json([
                    'message' => 'لا يمكن حذف الفرع. يوجد مخزون في هذا الفرع',
                ], 422);
            }

            // التحقق من وجود حركات مخزنية
            $hasMovements = DB::table('inventory_movements')
                ->where(function ($query) use ($branch) {
                    $query->where('from_branch_id', $branch->id)
                          ->orWhere('to_branch_id', $branch->id);
                })
                ->exists();

            if ($hasMovements) {
                return response()->json([
                    'message' => 'لا يمكن حذف الفرع. يوجد حركات مخزنية مسجلة على هذا الفرع',
                ], 422);
            }

            $branch->delete();

            return response()->json([
                'message' => 'تم حذف الفرع بنجاح',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف الفرع',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم',
            ], 500);
        }
    }
}
