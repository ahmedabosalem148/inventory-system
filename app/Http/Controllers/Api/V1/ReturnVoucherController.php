<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReturnVoucherResource;
use App\Models\ReturnVoucher;
use App\Services\InventoryService;
use App\Services\LedgerService;
use App\Services\SequencerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnVoucherController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
        private LedgerService $ledgerService,
        private SequencerService $sequencerService
    ) {}

    /**
     * عرض قائمة أذونات المرتجع
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = ReturnVoucher::with(['customer', 'branch', 'items.product']);

        // Super admin و manager و accounting يرون كل شيء
        if (!$user->hasRole(['super-admin', 'manager', 'accounting', 'accountant'])) {
            // باقي المستخدمين يرون فرعهم فقط
            $activeBranch = $user->getActiveBranch();
            if (!$activeBranch) {
                return response()->json([
                    'message' => 'لم يتم تعيين فرع للمستخدم'
                ], 403);
            }
            $query->where('branch_id', $activeBranch->id);
        } elseif ($request->filled('branch_id')) {
            // Admin و manager و accounting يمكنهم الفلترة حسب الفرع
            $query->where('branch_id', $request->branch_id);
        }

        // البحث برقم الإذن
        if ($request->filled('search')) {
            $query->where('voucher_number', 'like', "%{$request->search}%");
        }

        // فلترة بالعميل
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // فلترة بالحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة بالتاريخ
        if ($request->filled('from_date')) {
            $query->whereDate('return_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('return_date', '<=', $request->to_date);
        }

        // الترتيب
        $sortField = $request->input('sort_by', 'return_date');
        $sortDirection = $request->input('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 100);
        $vouchers = $query->paginate($perPage);

        return ReturnVoucherResource::collection($vouchers);
    }

    /**
     * إنشاء إذن مرتجع جديد
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required_without:customer_id|string|max:100',
            'branch_id' => 'required|exists:branches,id',
            'return_date' => 'required|date',
            'notes' => 'nullable|string',
            'reason' => 'required|string|max:500',
            'reason_category' => 'nullable|in:damaged,defective,customer_request,wrong_item,other',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Check permissions: regular users need full_access on the branch
        if (!$user->hasRole(['super-admin', 'manager'])) {
            $branchId = $validated['branch_id'];
            
            if (!$user->hasFullAccessToBranch($branchId)) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية كاملة لإنشاء أذونات مرتجع في هذا الفرع'
                ], 403);
            }
        }

        try {
            DB::beginTransaction();

            // توليد رقم الإذن
            $voucherNumber = $this->sequencerService->getNextSequence('return_vouchers');

            // حساب الإجمالي
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            // إنشاء الإذن
            $voucher = ReturnVoucher::create([
                'voucher_number' => $voucherNumber,
                'customer_id' => $validated['customer_id'] ?? null,
                'customer_name' => $validated['customer_name'],
                'branch_id' => $validated['branch_id'],
                'return_date' => $validated['return_date'],
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'status' => 'completed',
                'created_by' => auth()->id(),
            ]);

            // إضافة الأصناف وإرجاعها للمخزون
            foreach ($validated['items'] as $itemData) {
                $item = $voucher->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total' => $itemData['quantity'] * $itemData['unit_price'],
                ]);

                // إرجاع للمخزون
                $this->inventoryService->returnProduct(
                    productId: $item->product_id,
                    branchId: $voucher->branch_id,
                    quantity: $item->quantity,
                    reference: "إذن مرتجع {$voucherNumber}",
                    userId: auth()->id(),
                    voucherId: $voucher->id
                );
            }

            // تسجيل في الحسابات (خصم من حساب العميل)
            if ($voucher->customer_id) {
                $this->ledgerService->recordCredit(
                    customerId: $voucher->customer_id,
                    amount: $voucher->total_amount,
                    description: "إذن مرتجع رقم {$voucherNumber}",
                    date: $voucher->return_date,
                    voucherId: $voucher->id,
                    voucherType: 'return'
                );
            }

            DB::commit();

            return new ReturnVoucherResource($voucher->load(['customer', 'branch', 'items.product']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل إنشاء إذن المرتجع: ' . $e->getMessage(),
            ], 500);
        }
    }

        /**
     * عرض تفاصيل إذن مرتجع واحد
     */
    public function show(Request $request, ReturnVoucher $returnVoucher)
    {
        $user = $request->user();

        // Check access: admin sees all, regular users need access to branch
        if (!$user->hasRole(['super-admin', 'manager'])) {
            if (!$user->canAccessBranch($returnVoucher->branch_id)) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية لعرض هذا الإذن'
                ], 403);
            }
        }

        return response()->json([
            'data' => IssueVoucherResource::make($returnVoucher->load([
                'customer', 
                'branch', 
                'items.product', 
                'creator'
            ]))
        ]);
    }

    /**
     * طباعة إذن مرتجع كـ PDF
     */
    public function print(Request $request, ReturnVoucher $returnVoucher)
    {
        $user = $request->user();

        // Check access: admin sees all, regular users need access to branch
        if (!$user->hasRole(['super-admin', 'manager'])) {
            if (!$user->canAccessBranch($returnVoucher->branch_id)) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية لطباعة هذا الإذن'
                ], 403);
            }
        }

        $returnVoucher->load(['customer', 'branch', 'items.product', 'creator']);

        $pdf = Pdf::loadView('pdf.return-voucher', [
            'voucher' => $returnVoucher
        ]);

        return $pdf->download("return-voucher-{$returnVoucher->voucher_number}.pdf");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'message' => 'لا يمكن تعديل أذونات المرتجع، يمكن الإلغاء وإنشاء إذن جديد',
        ], 422);
    }

    /**
     * إلغاء إذن مرتجع
     * Only users with full_access can cancel vouchers (or admin)
     */
    public function destroy(Request $request, ReturnVoucher $returnVoucher)
    {
        $user = $request->user();

        // Check permissions: regular users need full_access on the branch
        if (!$user->hasRole(['super-admin', 'manager'])) {
            if (!$user->hasFullAccessToBranch($returnVoucher->branch_id)) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية كاملة لإلغاء أذونات المرتجع في هذا الفرع'
                ], 403);
            }
        }

        if ($returnVoucher->status === 'cancelled') {
            return response()->json([
                'message' => 'الإذن ملغي بالفعل',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // خصم الكميات من المخزون مرة أخرى
            foreach ($returnVoucher->items as $item) {
                $this->inventoryService->issueProduct(
                    productId: $item->product_id,
                    branchId: $returnVoucher->branch_id,
                    quantity: $item->quantity,
                    reference: "إلغاء إذن مرتجع {$returnVoucher->voucher_number}",
                    userId: auth()->id(),
                    voucherId: $returnVoucher->id
                );
            }

            // إلغاء القيد من الحسابات (إضافة للحساب مرة أخرى)
            if ($returnVoucher->customer_id) {
                $this->ledgerService->recordDebit(
                    customerId: $returnVoucher->customer_id,
                    amount: $returnVoucher->total_amount,
                    description: "إلغاء إذن مرتجع رقم {$returnVoucher->voucher_number}",
                    date: now(),
                    voucherId: $returnVoucher->id,
                    voucherType: 'return_cancellation'
                );
            }

            // تغيير الحالة إلى ملغي
            $returnVoucher->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json([
                'message' => 'تم إلغاء إذن المرتجع بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل إلغاء إذن المرتجع: ' . $e->getMessage(),
            ], 500);
        }
    }
}
