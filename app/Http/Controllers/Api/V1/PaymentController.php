<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use App\Models\Cheque;
use App\Rules\UniqueChequeNumber;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(
        private LedgerService $ledgerService
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => ['required', Rule::in(['cash', 'cheque', 'bank_transfer'])],
            'notes' => 'nullable|string',
            
            // حقول الشيك (إذا كانت الطريقة شيك)
            'cheque_number' => 'required_if:payment_method,cheque|string',
            'cheque_date' => [
                'required_if:payment_method,cheque',
                'date',
                'after_or_equal:' . now()->subYears(2)->format('Y-m-d')
            ],
            'cheque_due_date' => [
                'required_if:payment_method,cheque',
                'date',
                'after_or_equal:cheque_date'
            ],
            'bank_name' => 'required_if:payment_method,cheque|string',
        ]);
        
        // Additional validation for cheque
        if ($validated['payment_method'] === 'cheque') {
            $validator = validator($validated, [
                'cheque_number' => [
                    'required',
                    new UniqueChequeNumber(
                        bankName: $validated['bank_name']
                    )
                ]
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'خطأ في التحقق من بيانات الشيك',
                    'errors' => $validator->errors()
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $chequeId = null;

            // إذا كان الدفع بشيك، نسجل الشيك أولاً
            if ($validated['payment_method'] === 'cheque') {
                $cheque = Cheque::create([
                    'cheque_number' => $validated['cheque_number'],
                    'cheque_date' => $validated['cheque_date'],
                    'due_date' => $validated['cheque_due_date'],
                    'amount' => $validated['amount'],
                    'bank_name' => $validated['bank_name'],
                    'customer_id' => $validated['customer_id'],
                    'status' => 'pending',
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
            $this->ledgerService->recordCredit(
                customerId: $payment->customer_id,
                amount: $payment->amount,
                description: "سداد {$this->getPaymentMethodLabel($payment->payment_method)}",
                date: $payment->payment_date,
                paymentId: $payment->id
            );

            DB::commit();

            return new PaymentResource($payment->load(['customer', 'cheque']));
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

            // عكس القيد في دفتر الحسابات
            $this->ledgerService->recordDebit(
                customerId: $payment->customer_id,
                amount: $payment->amount,
                description: "إلغاء سداد بتاريخ {$payment->payment_date->format('Y-m-d')}",
                date: now(),
                paymentId: $payment->id
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
            'status' => ['required', Rule::in(['pending', 'cleared', 'bounced'])],
            'notes' => 'nullable|string',
        ]);

        try {
            $cheque->update([
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? $cheque->notes,
            ]);

            // إذا ارتد الشيك، نعكس القيد
            if ($validated['status'] === 'bounced' && $cheque->status !== 'bounced') {
                $payment = $cheque->payment;
                if ($payment) {
                    $this->ledgerService->recordDebit(
                        customerId: $payment->customer_id,
                        amount: $payment->amount,
                        description: "ارتداد شيك رقم {$cheque->cheque_number}",
                        date: now()
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
