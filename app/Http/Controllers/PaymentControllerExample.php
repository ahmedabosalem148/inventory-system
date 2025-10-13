<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * مثال على استخدام Activity Logging في Controllers
 */
class PaymentController extends Controller
{
    /**
     * إنشاء سداد جديد
     */
    public function store(Request $request)
    {
        // Validation...
        $this->authorize('create', Payment::class);

        DB::beginTransaction();
        try {
            $payment = Payment::create([
                'customer_id' => $request->customer_id,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'cheque_id' => $request->cheque_id,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // ✅ Manual Activity Logging - يسجل تلقائياً لأن Payment يستخدم LogsActivity trait
            // لكن يمكنك إضافة سجل إضافي:
            activity()
                ->performedOn($payment)
                ->causedBy(auth()->user())
                ->withProperties(['ip' => request()->ip()])
                ->log('تم تسجيل سداد بمبلغ ' . $payment->amount . ' للعميل #' . $payment->customer_id);

            DB::commit();
            return redirect()->route('payments.index')->with('success', 'تم تسجيل السداد بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * تحديث سداد
     */
    public function update(Request $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        DB::beginTransaction();
        try {
            $oldAmount = $payment->amount;
            
            $payment->update($request->only([
                'payment_date', 
                'amount', 
                'payment_method', 
                'notes'
            ]));

            // ✅ Manual Activity Logging للتعديلات الهامة
            if ($oldAmount != $payment->amount) {
                activity()
                    ->performedOn($payment)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'old_amount' => $oldAmount,
                        'new_amount' => $payment->amount,
                    ])
                    ->log('تم تعديل مبلغ السداد من ' . $oldAmount . ' إلى ' . $payment->amount);
            }

            DB::commit();
            return redirect()->route('payments.index')->with('success', 'تم تحديث السداد بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * حذف سداد
     */
    public function destroy(Payment $payment)
    {
        $this->authorize('delete', $payment);

        DB::beginTransaction();
        try {
            // ✅ Manual Activity Logging قبل الحذف
            activity()
                ->performedOn($payment)
                ->causedBy(auth()->user())
                ->withProperties($payment->toArray())
                ->log('تم حذف سداد بمبلغ ' . $payment->amount . ' للعميل #' . $payment->customer_id);

            $payment->delete();

            DB::commit();
            return redirect()->route('payments.index')->with('success', 'تم حذف السداد بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
}
