<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\IssueVoucherResource;
use App\Models\IssueVoucher;
use App\Services\InventoryService;
use App\Services\LedgerService;
use App\Services\SequencerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IssueVoucherController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
        private LedgerService $ledgerService,
        private SequencerService $sequencerService
    ) {}

    /**
     * عرض قائمة أذونات الصرف مع الفلترة والترتيب
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = IssueVoucher::with(['customer', 'branch', 'items.product']);

        // Admin can see all or filter by branch, regular users see only their branch
        if (!$user->hasRole('super-admin')) {
            $activeBranch = $user->getActiveBranch();
            if (!$activeBranch) {
                return response()->json([
                    'message' => 'لم يتم تعيين فرع للمستخدم'
                ], 403);
            }
            $query->where('branch_id', $activeBranch->id);
        } elseif ($request->filled('branch_id')) {
            // Admin can optionally filter by branch
            $query->where('branch_id', $request->branch_id);
        }

        // البحث برقم الإذن
        if ($request->filled('search')) {
            $query->searchByNumber($request->search);
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
            $query->whereDate('issue_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('issue_date', '<=', $request->to_date);
        }

        // الترتيب
        $sortField = $request->input('sort_by', 'issue_date');
        $sortDirection = $request->input('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 100);
        $vouchers = $query->paginate($perPage);

        return IssueVoucherResource::collection($vouchers);
    }

    /**
     * إنشاء إذن صرف جديد
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required_without:customer_id|string|max:100',
            'branch_id' => 'required|exists:branches,id',
            'issue_date' => 'required|date',
            'notes' => 'nullable|string',
            'discount_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'discount_value' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        // Check permissions: regular users need full_access on the branch
        if (!$user->hasRole('super-admin')) {
            $branchId = $validated['branch_id'];
            
            if (!$user->hasFullAccessToBranch($branchId)) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية كاملة لإنشاء أذونات صرف في هذا الفرع'
                ], 403);
            }
        }

        try {
            DB::beginTransaction();

            // توليد رقم الإذن
            $voucherNumber = $this->sequencerService->getNextSequence('issue_vouchers');

            // حساب الإجماليات
            $calculations = $this->calculateVoucherTotals($validated);

            // إنشاء الإذن
            $voucher = IssueVoucher::create([
                'voucher_number' => $voucherNumber,
                'customer_id' => $validated['customer_id'] ?? null,
                'customer_name' => $validated['customer_name'],
                'branch_id' => $validated['branch_id'],
                'issue_date' => $validated['issue_date'],
                'notes' => $validated['notes'] ?? null,
                'discount_type' => $validated['discount_type'] ?? 'none',
                'discount_value' => $validated['discount_value'] ?? 0,
                ...$calculations,
                'status' => 'completed',
                'created_by' => auth()->id(),
            ]);

            // إضافة الأصناف وتحديث المخزون
            foreach ($validated['items'] as $itemData) {
                $item = $voucher->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_amount' => $itemData['discount_amount'] ?? 0,
                    'total' => ($itemData['quantity'] * $itemData['unit_price']) - ($itemData['discount_amount'] ?? 0),
                ]);

                // خصم من المخزون
                $this->inventoryService->issueProduct(
                    productId: $item->product_id,
                    branchId: $voucher->branch_id,
                    quantity: $item->quantity,
                    notes: "إذن صرف {$voucherNumber}",
                    metadata: [
                        'voucher_id' => $voucher->id,
                        'user_id' => auth()->id(),
                    ]
                );
            }

            // تسجيل في الحسابات (إذا كان العميل مسجل)
            if ($voucher->customer_id) {
                $this->ledgerService->recordDebit(
                    customerId: $voucher->customer_id,
                    amount: $voucher->net_total,
                    description: "إذن صرف رقم {$voucherNumber}",
                    referenceType: 'issue_voucher',
                    referenceId: $voucher->id
                );
            }

            DB::commit();

            return new IssueVoucherResource($voucher->load(['customer', 'branch', 'items.product']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل إنشاء إذن الصرف: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض تفاصيل إذن صرف واحد
     */
    public function show(Request $request, IssueVoucher $issueVoucher)
    {
        $user = $request->user();

        // Check access: admin sees all, regular users need access to branch
        if (!$user->hasRole('super-admin')) {
            if (!$user->canAccessBranch($issueVoucher->branch_id)) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية لعرض هذا الإذن'
                ], 403);
            }
        }

        $issueVoucher->load(['customer', 'branch', 'items.product', 'creator']);
        return new IssueVoucherResource($issueVoucher);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'message' => 'لا يمكن تعديل أذونات الصرف، يمكن الإلغاء وإنشاء إذن جديد',
        ], 422);
    }

    /**
     * إحصائيات أذونات الصرف
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $branchId = null;

        // Determine branch context
        if (!$user->hasRole('super-admin')) {
            $activeBranch = $user->getActiveBranch();
            if (!$activeBranch) {
                return response()->json([
                    'message' => 'لا يوجد فرع نشط للمستخدم'
                ], 403);
            }
            $branchId = $activeBranch->id;
        } elseif ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
        }

        $query = IssueVoucher::query();
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Total vouchers
        $totalVouchers = $query->count();

        // Today's vouchers
        $todayVouchers = (clone $query)->whereDate('issue_date', today())->count();

        // Total amount
        $totalAmount = $query->sum('net_total') ?? 0;

        // Pending vouchers
        $pendingVouchers = (clone $query)->where('status', 'pending')->count();

        // This month stats
        $thisMonthVouchers = (clone $query)
            ->whereYear('issue_date', now()->year)
            ->whereMonth('issue_date', now()->month)
            ->count();

        $thisMonthAmount = (clone $query)
            ->whereYear('issue_date', now()->year)
            ->whereMonth('issue_date', now()->month)
            ->sum('net_total') ?? 0;

        return response()->json([
            'data' => [
                'totalVouchers' => $totalVouchers,
                'todayVouchers' => $todayVouchers,
                'totalAmount' => $totalAmount,
                'pendingVouchers' => $pendingVouchers,
                'thisMonthVouchers' => $thisMonthVouchers,
                'thisMonthAmount' => $thisMonthAmount,
                'averageVoucherValue' => $totalVouchers > 0 ? $totalAmount / $totalVouchers : 0,
                'branch_id' => $branchId
            ]
        ]);
    }

    /**
     * حذف (إلغاء) إذن صرف
     * Only users with full_access can cancel vouchers (or admin)
     */
    public function destroy(Request $request, IssueVoucher $issueVoucher)
    {
        $user = $request->user();

        // Check permissions: regular users need full_access on the branch
        if (!$user->hasRole('super-admin')) {
            if (!$user->hasFullAccessToBranch($issueVoucher->branch_id)) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية كاملة لإلغاء أذونات الصرف في هذا الفرع'
                ], 403);
            }
        }

        if ($issueVoucher->status === 'cancelled') {
            return response()->json([
                'message' => 'الإذن ملغي بالفعل',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // إرجاع الكميات للمخزون
            foreach ($issueVoucher->items as $item) {
                $this->inventoryService->returnProduct(
                    productId: $item->product_id,
                    branchId: $issueVoucher->branch_id,
                    quantity: $item->quantity,
                    reference: "إلغاء إذن صرف {$issueVoucher->voucher_number}",
                    userId: auth()->id(),
                    voucherId: $issueVoucher->id
                );
            }

            // إلغاء القيد من الحسابات
            if ($issueVoucher->customer_id) {
                $this->ledgerService->recordCredit(
                    customerId: $issueVoucher->customer_id,
                    amount: $issueVoucher->net_total,
                    description: "إلغاء إذن صرف رقم {$issueVoucher->voucher_number}",
                    date: now(),
                    voucherId: $issueVoucher->id,
                    voucherType: 'issue_cancellation'
                );
            }

            // تغيير الحالة إلى ملغي
            $issueVoucher->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json([
                'message' => 'تم إلغاء إذن الصرف بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل إلغاء إذن الصرف: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حساب إجماليات الإذن
     */
    private function calculateVoucherTotals(array $data): array
    {
        $subtotal = 0;

        foreach ($data['items'] as $item) {
            $itemTotal = ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0);
            $subtotal += $itemTotal;
        }

        $discountAmount = 0;
        if (isset($data['discount_type']) && isset($data['discount_value'])) {
            if ($data['discount_type'] === 'fixed') {
                $discountAmount = $data['discount_value'];
            } elseif ($data['discount_type'] === 'percentage') {
                $discountAmount = ($subtotal * $data['discount_value']) / 100;
            }
        }

        $netTotal = $subtotal - $discountAmount;

        return [
            'total_amount' => $subtotal,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'net_total' => $netTotal,
        ];
    }
}
