<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateIssueVoucherRequest;
use App\Http\Resources\Api\V1\IssueVoucherResource;
use App\Models\IssueVoucher;
use App\Models\Product;
use App\Rules\CustomerCreditLimitCheck;
use App\Rules\MaxDiscountValue;
use App\Rules\SufficientStock;
use App\Services\InventoryService;
use App\Services\LedgerService;
use App\Services\SequencerService;
use Barryvdh\DomPDF\Facade\Pdf;
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
            
            // Transfer fields
            'issue_type' => ['required', Rule::in(['SALE', 'TRANSFER'])],
            'target_branch_id' => [
                'required_if:issue_type,TRANSFER',
                'nullable',
                'exists:branches,id',
                'different:branch_id'
            ],
            'payment_type' => [
                'required_if:issue_type,SALE',
                'nullable',
                Rule::in(['CASH', 'CREDIT'])
            ],
            
            // Header discount (خصم الفاتورة)
            'discount_type' => ['nullable', Rule::in(['none', 'fixed', 'percentage'])],
            'discount_value' => 'nullable|numeric|min:0',
            
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            
            // Line item discount (خصم البند)
            'items.*.discount_type' => ['nullable', Rule::in(['none', 'fixed', 'percentage'])],
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0', // للتوافق مع الكود القديم
        ]);

        // Additional validation: Check sufficient stock for each item
        $warnings = [];
        
        foreach ($validated['items'] as $index => $item) {
            $validator = validator($item, [
                'quantity' => [
                    'required',
                    'numeric',
                    'min:0.01',
                    new SufficientStock(
                        productId: $item['product_id'],
                        branchId: $validated['branch_id']
                    )
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'خطأ في التحقق من المخزون',
                    'errors' => [
                        "items.{$index}.quantity" => $validator->errors()->first('quantity')
                    ]
                ], 422);
            }
            
            // Check pack size warning
            $product = Product::find($item['product_id']);
            if ($product && $product->pack_size && $product->pack_size > 1) {
                $remainder = fmod($item['quantity'], $product->pack_size);
                if ($remainder != 0) {
                    $warnings[] = [
                        'item_index' => $index,
                        'product_name' => $product->name,
                        'quantity' => $item['quantity'],
                        'pack_size' => $product->pack_size,
                        'message' => "تحذير: الكمية ({$item['quantity']}) ليست من مضاعفات حجم العبوة ({$product->pack_size}) للمنتج '{$product->name}'"
                    ];
                }
            }
        }

        // Validate discounts on line items
        foreach ($validated['items'] as $index => $item) {
            if (!empty($item['discount_type']) && $item['discount_type'] !== 'none') {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                
                $validator = validator($item, [
                    'discount_value' => [
                        'required',
                        'numeric',
                        'min:0',
                        new MaxDiscountValue(
                            discountType: $item['discount_type'],
                            totalAmount: $itemTotal
                        )
                    ]
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'message' => 'خطأ في التحقق من الخصم',
                        'errors' => [
                            "items.{$index}.discount_value" => $validator->errors()->first('discount_value')
                        ]
                    ], 422);
                }
            }
        }

        // Validate header discount (after calculating subtotal)
        if (!empty($validated['discount_type']) && $validated['discount_type'] !== 'none') {
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $itemCalculations = $this->calculateItemTotals($item);
                $subtotal += $itemCalculations['net_price'];
            }

            $validator = validator($validated, [
                'discount_value' => [
                    'required',
                    'numeric',
                    'min:0',
                    new MaxDiscountValue(
                        discountType: $validated['discount_type'],
                        totalAmount: $subtotal
                    )
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'خطأ في التحقق من خصم الفاتورة',
                    'errors' => $validator->errors()->toArray()
                ], 422);
            }
        }

        // Check permissions: regular users need full_access on the branch
        if (!$user->hasRole(['super-admin', 'manager'])) {
            $branchId = $validated['branch_id'];
            
            if (!$user->hasFullAccessToBranch($branchId)) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية كاملة لإنشاء أذونات صرف في هذا الفرع'
                ], 403);
            }
        }
        
        // حساب الإجماليات
        $calculations = $this->calculateVoucherTotals($validated);
        
        // Check customer credit limit for CREDIT sales
        if (isset($validated['payment_type']) && $validated['payment_type'] === 'CREDIT' && isset($validated['customer_id'])) {
            $creditCheckRule = new CustomerCreditLimitCheck(
                customerId: $validated['customer_id'],
                newAmount: $calculations['net_total'],
                blockIfExceeded: false // Warning only, don't block
            );
            
            // Run the validation to add warnings to session
            $creditCheckRule->validate('customer_id', $validated['customer_id'], function ($message) {
                // This closure won't be called unless we want to block
            });
            
            // Get warnings from session
            $creditWarnings = session()->pull('validation.warnings', []);
            if (!empty($creditWarnings)) {
                $warnings = array_merge($warnings, $creditWarnings);
            }
        }

        try {
            DB::beginTransaction();

            // توليد رقم الإذن
            $voucherNumber = $this->sequencerService->getNextSequence('issue_vouchers');

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
                // حساب تفاصيل البند مع الخصومات
                $itemCalculations = $this->calculateItemTotals($itemData);
                
                $item = $voucher->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemCalculations['total_price'],
                    'discount_type' => $itemCalculations['discount_type'],
                    'discount_value' => $itemCalculations['discount_value'],
                    'discount_amount' => $itemCalculations['discount_amount'],
                    'net_price' => $itemCalculations['net_price'],
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

            $response = [
                'data' => new IssueVoucherResource($voucher->load(['customer', 'branch', 'items.product']))
            ];
            
            if (!empty($warnings)) {
                $response['warnings'] = $warnings;
            }
            
            return response()->json($response, 201);
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

        $issueVoucher->load([
            'customer', 
            'branch', 
            'targetBranch', 
            'items.product', 
            'creator',
            'approver'
        ]);
        return new IssueVoucherResource($issueVoucher);
    }

    /**
     * طباعة إذن صرف كـ PDF
     */
    public function print(Request $request, IssueVoucher $issueVoucher)
    {
        $user = $request->user();

        // Check access: admin sees all, regular users need access to branch
        if (!$user->hasRole('super-admin')) {
            if (!$user->canAccessBranch($issueVoucher->branch_id)) {
                return response()->json([
                    'message' => 'ليس لديك صلاحية لطباعة هذا الإذن'
                ], 403);
            }
        }

        $issueVoucher->load([
            'customer', 
            'branch', 
            'targetBranch', 
            'items.product', 
            'creator',
            'approver'
        ]);

        $pdf = Pdf::loadView('pdf.issue-voucher', [
            'voucher' => $issueVoucher
        ]);

        // Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');
        
        // Update print tracking
        $issueVoucher->increment('print_count');
        $issueVoucher->update([
            'last_printed_at' => now(),
            'last_printed_by' => $user->id
        ]);

        // Return PDF as download or inline view
        $filename = 'issue-voucher-' . $issueVoucher->voucher_number . '.pdf';
        
        if ($request->has('download')) {
            return $pdf->download($filename);
        }
        
        return $pdf->stream($filename);
    }

    /**
     * Update the specified resource in storage.
     * Note: Currently disabled. If needed in future, use UpdateIssueVoucherRequest.
     */
    public function update(UpdateIssueVoucherRequest $request, string $id)
    {
        return response()->json([
            'message' => 'لا يمكن تعديل أذونات الصرف، يمكن الإلغاء وإنشاء إذن جديد',
        ], 422);
        
        // Future implementation would use $request->validated()
        // $validated = $request->validated();
        // ... update logic with warnings similar to store method
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
     * يحسب خصومات البنود وخصم الفاتورة
     */
    private function calculateVoucherTotals(array $data): array
    {
        $subtotal = 0; // مجموع الأسعار قبل أي خصومات
        $itemsSubtotal = 0; // مجموع البنود بعد خصومات البنود

        foreach ($data['items'] as $item) {
            // إجمالي البند قبل خصم البند
            $itemTotalBeforeDiscount = $item['quantity'] * $item['unit_price'];
            $subtotal += $itemTotalBeforeDiscount;
            
            // حساب خصم البند
            $itemDiscountAmount = 0;
            if (isset($item['discount_type']) && isset($item['discount_value'])) {
                if ($item['discount_type'] === 'fixed') {
                    $itemDiscountAmount = $item['discount_value'];
                } elseif ($item['discount_type'] === 'percentage') {
                    $itemDiscountAmount = ($itemTotalBeforeDiscount * $item['discount_value']) / 100;
                }
            } elseif (isset($item['discount_amount'])) {
                // للتوافق مع الكود القديم
                $itemDiscountAmount = $item['discount_amount'];
            }
            
            // صافي البند بعد خصم البند
            $itemNetPrice = $itemTotalBeforeDiscount - $itemDiscountAmount;
            $itemsSubtotal += $itemNetPrice;
        }

        // حساب خصم الفاتورة (Header Discount) - يطبق على مجموع البنود بعد خصوماتها
        $headerDiscountAmount = 0;
        if (isset($data['discount_type']) && isset($data['discount_value'])) {
            if ($data['discount_type'] === 'fixed') {
                $headerDiscountAmount = $data['discount_value'];
            } elseif ($data['discount_type'] === 'percentage') {
                $headerDiscountAmount = ($itemsSubtotal * $data['discount_value']) / 100;
            }
        }

        // الصافي النهائي
        $netTotal = $itemsSubtotal - $headerDiscountAmount;

        return [
            'total_amount' => round($subtotal, 2),           // إجمالي قبل كل الخصومات
            'subtotal' => round($itemsSubtotal, 2),          // بعد خصومات البنود وقبل خصم الفاتورة
            'discount_amount' => round($headerDiscountAmount, 2), // خصم الفاتورة فقط
            'net_total' => round($netTotal, 2),              // الصافي النهائي
        ];
    }

    /**
     * حساب تفاصيل البند (مع الخصومات)
     */
    private function calculateItemTotals(array $itemData): array
    {
        // إجمالي قبل الخصم
        $totalPrice = $itemData['quantity'] * $itemData['unit_price'];
        
        // حساب خصم البند
        $discountAmount = 0;
        $discountType = $itemData['discount_type'] ?? 'none';
        $discountValue = $itemData['discount_value'] ?? 0;
        
        if ($discountType === 'fixed') {
            $discountAmount = $discountValue;
        } elseif ($discountType === 'percentage') {
            $discountAmount = ($totalPrice * $discountValue) / 100;
        } elseif (isset($itemData['discount_amount'])) {
            // للتوافق مع الكود القديم
            $discountAmount = $itemData['discount_amount'];
            $discountType = 'fixed'; // نعتبره fixed
            $discountValue = $discountAmount;
        }
        
        // صافي السعر بعد الخصم
        $netPrice = $totalPrice - $discountAmount;
        
        return [
            'total_price' => round($totalPrice, 2),
            'discount_type' => $discountType,
            'discount_value' => round($discountValue, 2),
            'discount_amount' => round($discountAmount, 2),
            'net_price' => round($netPrice, 2),
        ];
    }
}
