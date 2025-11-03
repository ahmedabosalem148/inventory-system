<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    /**
     * قائمة الموردين مع الفلترة
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        // البحث
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // فلترة بالحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // الترتيب
        $sortField = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_dir', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 100);
        $suppliers = $query->paginate($perPage);

        return response()->json($suppliers);
    }

    /**
     * عرض تفاصيل مورد واحد
     */
    public function show(Supplier $supplier)
    {
        $supplier->load(['purchaseOrders' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return response()->json([
            'data' => $supplier,
        ]);
    }

    /**
     * إنشاء مورد جديد
     */
    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();

        $supplier = Supplier::create($validated);

        return response()->json([
            'message' => 'تم إنشاء المورد بنجاح',
            'data' => $supplier,
        ], 201);
    }

    /**
     * تحديث بيانات المورد
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $validated = $request->validated();

        $supplier->update($validated);

        return response()->json([
            'message' => 'تم تحديث بيانات المورد بنجاح',
            'data' => $supplier->fresh(),
        ]);
    }

    /**
     * حذف المورد
     */
    public function destroy(Supplier $supplier)
    {
        // تحقق من وجود أوامر شراء مرتبطة
        if ($supplier->purchaseOrders()->exists()) {
            return response()->json([
                'message' => 'لا يمكن حذف المورد لوجود أوامر شراء مرتبطة به',
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'message' => 'تم حذف المورد بنجاح',
        ]);
    }

    /**
     * الحصول على إحصائيات الموردين
     */
    public function statistics(Request $request)
    {
        $stats = [
            'total' => Supplier::count(),
            'active' => Supplier::where('status', 'ACTIVE')->count(),
            'inactive' => Supplier::where('status', 'INACTIVE')->count(),
            'total_balance' => Supplier::sum('current_balance'),
        ];

        return response()->json($stats);
    }
}
