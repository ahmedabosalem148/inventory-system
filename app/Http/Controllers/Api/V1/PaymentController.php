<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use App\Models\Cheque;
use App\Rules\UniqueChequeNumber;
use App\Services\CustomerLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(
        private CustomerLedgerService $customerLedgerService
    ) {}

    /**
     * عرض قائمة المدفوعات
     */
    public function index(Request $request)
    {
        $query = Payment::with(['customer', 'cheque', 'creator']);

        // فلترة بالعميل
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // فلترة بنوع الدفع
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // فلترة بالتاريخ
        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        // الترتيب
        $sortField = $request->input('sort_by', 'payment_date');
        $sortDirection = $request->input('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 100);
        $payments = $query->paginate($perPage);

        return PaymentResource::collection($payments);
    }

    /**
     * تسجيل دفعة جديدة
     */
    public function store(StorePaymentRequest $request)
    {
        $validated = $request->validated();
        
        // Get warnings from the form request
        $warnings = $request->getWarnings();

        try {
            DB::beginTransaction();

            $chequeId = null;

            // إذا كان الدفع بشيك، نسجل الشيك أولاً
            if (strtoupper($validated['payment_method']) === 'CHEQUE') {
                $cheque = Cheque::create([
                    'customer_id' => $validated['customer_id'],
                    'cheque_number' => $validated['cheque_number'],
                    'bank_name' => $validated['bank_name'] ?? null,
                    'due_date' => $validated['cheque_due_date'],
                    'amount' => $validated['amount'],
                    'status' => 'PENDING',
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);
                $chequeId = $cheque->id;
            }

            // تسجيل الدفعة
            $payment = Payment::create([
                'customer_id' => $validated['customer_id'],
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'cheque_id' => $chequeId,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // تسجيل في دفتر الحسابات
            $this->customerLedgerService->addEntry(
                customerId: $payment->customer_id,
                description: "سداد {$this->getPaymentMethodLabel($payment->payment_method)} - رقم {$payment->id}",
                debitAliah: 0,
                creditLah: $payment->amount,
                refTable: 'payments',
                refId: $payment->id,
                createdBy: auth()->id()
            );

            DB::commit();

            $response = [
                'data' => new PaymentResource($payment->load(['customer', 'cheque']))
            ];
            
            // Add warnings if any
            if (!empty($warnings)) {
                $response['warnings'] = $warnings;
            }
            
            return response()->json($response, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل تسجيل الدفعة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض تفاصيل دفعة واحدة
     */
    public function show(Payment $payment)
    {
        $payment->load(['customer', 'cheque', 'creator']);
        return new PaymentResource($payment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'message' => 'لا يمكن تعديل المدفوعات، يمكن الحذف وإنشاء دفعة جديدة',
        ], 422);
    }

    /**
     * حذف دفعة
     */
    public function destroy(Payment $payment)
    {
        try {
            DB::beginTransaction();

            // عكس القيد في دفتر الحسابات (إضافة مدين لإلغاء الدائن)
            $this->customerLedgerService->addEntry(
                customerId: $payment->customer_id,
                description: "إلغاء سداد - رقم {$payment->id}",
                debitAliah: $payment->amount,
                creditLah: 0,
                refTable: 'payments',
                refId: $payment->id,
                notes: 'قيد عكسي لإلغاء الدفعة',
                createdBy: auth()->id()
            );

            // حذف الدفعة
            $payment->delete();

            DB::commit();

            return response()->json([
                'message' => 'تم حذف الدفعة بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل حذف الدفعة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تغيير حالة الشيك
     */
    public function updateChequeStatus(Request $request, Cheque $cheque)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['PENDING', 'CLEARED', 'RETURNED'])],
            'notes' => 'nullable|string',
        ]);

        try {
            $cheque->update([
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? $cheque->notes,
            ]);

            // إذا ارتد الشيك، نعكس القيد (إضافة مدين)
            if ($validated['status'] === 'RETURNED' && $cheque->status !== 'RETURNED') {
                $payment = $cheque->payment;
                if ($payment) {
                    $this->customerLedgerService->addEntry(
                        customerId: $payment->customer_id,
                        description: "ارتداد شيك رقم {$cheque->cheque_number}",
                        debitAliah: $payment->amount,
                        creditLah: 0,
                        refTable: 'cheques',
                        refId: $cheque->id,
                        notes: 'قيد عكسي لارتداد الشيك',
                        createdBy: auth()->id()
                    );
                }
            }

            return response()->json([
                'message' => 'تم تحديث حالة الشيك بنجاح',
                'data' => $cheque->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل تحديث حالة الشيك: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * الحصول على جميع الشيكات
     */
    public function getCheques(Request $request)
    {
        $query = Cheque::with(['customer', 'creator']);

        // فلترة بالعميل
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // فلترة بالحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // بحث برقم الشيك أو اسم العميل
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cheque_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // فلترة بالتاريخ
        if ($request->filled('from_date')) {
            $query->whereDate('due_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('due_date', '<=', $request->to_date);
        }

        // الترتيب
        $query->orderBy('due_date', 'asc')->orderBy('id', 'desc');

        // Pagination
        $perPage = min((int) $request->input('per_page', 20), 100);
        $cheques = $query->paginate($perPage);

        return response()->json($cheques);
    }

    /**
     * إحصائيات الشيكات
     */
    public function chequeStats()
    {
        $pending = Cheque::where('status', 'PENDING')->count();
        $overdue = Cheque::where('status', 'PENDING')
            ->where('due_date', '<', now())
            ->count();
        $cleared = Cheque::where('status', 'CLEARED')->count();
        $returned = Cheque::where('status', 'RETURNED')->count();
        
        $totalAmount = Cheque::where('status', 'PENDING')->sum('amount');

        return response()->json([
            'pending' => $pending,
            'overdue' => $overdue,
            'cleared' => $cleared,
            'returned' => $returned,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * صرف شيك
     */
    public function clearCheque(Request $request, Cheque $cheque)
    {
        $validated = $request->validate([
            'cleared_at' => 'nullable|date',
        ]);

        try {
            if ($cheque->status !== 'PENDING') {
                return response()->json([
                    'message' => 'لا يمكن صرف هذا الشيك. الحالة الحالية: ' . $cheque->status,
                ], 400);
            }

            $cheque->update([
                'status' => 'CLEARED',
                'cleared_at' => $validated['cleared_at'] ?? now(),
                'cleared_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'تم صرف الشيك بنجاح',
                'data' => $cheque->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل صرف الشيك: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إرجاع شيك (Bounce)
     */
    public function bounceСheque(Request $request, Cheque $cheque)
    {
        $validated = $request->validate([
            'return_reason' => 'required|string|max:500',
        ]);

        try {
            if ($cheque->status !== 'PENDING') {
                return response()->json([
                    'message' => 'لا يمكن إرجاع هذا الشيك. الحالة الحالية: ' . $cheque->status,
                ], 400);
            }

            $cheque->update([
                'status' => 'RETURNED',
                'return_reason' => $validated['return_reason'],
            ]);

            // عكس القيد في دفتر الحسابات (إضافة مدين للعميل)
            $payment = Payment::where('cheque_id', $cheque->id)->first();
            if ($payment) {
                $this->customerLedgerService->addEntry(
                    customerId: $payment->customer_id,
                    transactionType: 'cheque_bounced',
                    referenceNumber: "CHQ-BOUNCE-{$cheque->cheque_number}",
                    referenceId: $cheque->id,
                    debit: $payment->amount,
                    credit: 0,
                    notes: "ارتداد شيك رقم {$cheque->cheque_number} - {$validated['return_reason']}",
                    createdBy: auth()->id()
                );
            }

            return response()->json([
                'message' => 'تم إرجاع الشيك بنجاح',
                'data' => $cheque->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل إرجاع الشيك: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * الحصول على تسمية طريقة الدفع
     */
    private function getPaymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'نقدي',
            'cheque' => 'شيك',
            'bank_transfer' => 'تحويل بنكي',
            default => 'غير محدد',
        };
    }
}
