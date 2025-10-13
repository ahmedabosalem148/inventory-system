<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\LedgerEntry;
use App\Models\IssueVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * عرض قائمة المدفوعات
     */
    public function index(Request $request)
    {
        $query = Payment::with(['customer', 'cheque', 'issueVoucher', 'creator']);

        // التصفية حسب العميل
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // التصفية حسب طريقة الدفع
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // التصفية حسب التاريخ
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        $payments = $query->orderBy('payment_date', 'desc')
                          ->orderBy('id', 'desc')
                          ->paginate(15);

        $customers = Customer::active()->orderBy('name')->get();

        return view('payments.index', compact('payments', 'customers'));
    }

    /**
     * عرض نموذج إنشاء سداد جديد
     */
    public function create()
    {
        $customers = Customer::active()->orderBy('name')->get();
        $recentVouchers = IssueVoucher::where('status', 'completed')
                                     ->whereNotNull('customer_id')
                                     ->with('customer')
                                     ->orderBy('issue_date', 'desc')
                                     ->limit(50)
                                     ->get();

        return view('payments.create', compact('customers', 'recentVouchers'));
    }

    /**
     * حفظ السداد الجديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:CASH,CHEQUE',
            'issue_voucher_id' => 'nullable|exists:issue_vouchers,id',
            'notes' => 'nullable|string',
            
            // Cheque fields (required if payment_method is CHEQUE)
            'cheque_number' => 'required_if:payment_method,CHEQUE|nullable|string|max:255',
            'bank_name' => 'required_if:payment_method,CHEQUE|nullable|string|max:255',
            'due_date' => 'required_if:payment_method,CHEQUE|nullable|date',
        ], [
            'customer_id.required' => 'العميل مطلوب',
            'payment_date.required' => 'تاريخ السداد مطلوب',
            'amount.required' => 'المبلغ مطلوب',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر',
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'cheque_number.required_if' => 'رقم الشيك مطلوب عند الدفع بشيك',
            'bank_name.required_if' => 'اسم البنك مطلوب عند الدفع بشيك',
            'due_date.required_if' => 'تاريخ استحقاق الشيك مطلوب',
        ]);

        try {
            DB::beginTransaction();

            $chequeId = null;

            // 1. إنشاء شيك إذا كانت طريقة الدفع CHEQUE
            if ($validated['payment_method'] === 'CHEQUE') {
                $cheque = Cheque::create([
                    'customer_id' => $validated['customer_id'],
                    'cheque_number' => $validated['cheque_number'],
                    'bank_name' => $validated['bank_name'],
                    'due_date' => $validated['due_date'],
                    'amount' => $validated['amount'],
                    'status' => 'PENDING',
                    'issue_voucher_id' => $validated['issue_voucher_id'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                $chequeId = $cheque->id;
            }

            // 2. إنشاء السداد
            $payment = Payment::create([
                'customer_id' => $validated['customer_id'],
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'cheque_id' => $chequeId,
                'issue_voucher_id' => $validated['issue_voucher_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // 3. تحديث رصيد العميل (زيادة الرصيد = له)
            $customer = Customer::lockForUpdate()->find($validated['customer_id']);
            $customer->increment('balance', $validated['amount']);

            // 4. تسجيل في دفتر العميل (credit = له = يخصم من الرصيد)
            LedgerEntry::create([
                'customer_id' => $validated['customer_id'],
                'type' => 'credit',
                'amount' => $validated['amount'],
                'description' => 'سداد ' . ($validated['payment_method'] === 'CASH' ? 'نقدي' : 'بشيك رقم ' . $validated['cheque_number']),
                'reference_type' => 'Payment',
                'reference_id' => $payment->id,
            ]);

            DB::commit();

            $successMessage = 'تم تسجيل السداد بنجاح';
            if ($validated['payment_method'] === 'CHEQUE') {
                $successMessage .= ' - الشيك رقم ' . $validated['cheque_number'] . ' في انتظار الصرف';
            }

            return redirect()->route('payments.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تسجيل السداد: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل سداد
     */
    public function show(Payment $payment)
    {
        $payment->load(['customer', 'cheque', 'issueVoucher', 'creator']);
        
        return view('payments.show', compact('payment'));
    }

    /**
     * حذف سداد (إلغاء)
     */
    public function destroy(Payment $payment)
    {
        try {
            DB::beginTransaction();

            // 1. إرجاع رصيد العميل
            $customer = Customer::lockForUpdate()->find($payment->customer_id);
            $customer->decrement('balance', $payment->amount);

            // 2. تسجيل إلغاء في دفتر العميل (debit = عليه = يضاف للرصيد)
            LedgerEntry::create([
                'customer_id' => $payment->customer_id,
                'type' => 'debit',
                'amount' => $payment->amount,
                'description' => 'إلغاء سداد - ' . ($payment->payment_method === 'CASH' ? 'نقدي' : 'شيك'),
                'reference_type' => 'Payment',
                'reference_id' => $payment->id,
            ]);

            // 3. حذف الشيك إن وجد (ولم يتم صرفه)
            if ($payment->cheque_id && $payment->cheque) {
                if ($payment->cheque->status === 'PENDING') {
                    $payment->cheque->delete();
                } else {
                    throw new \Exception('لا يمكن حذف سداد مرتبط بشيك تم صرفه أو إرجاعه');
                }
            }

            // 4. حذف السداد
            $payment->delete();

            DB::commit();

            return redirect()->route('payments.index')
                ->with('success', 'تم إلغاء السداد بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
}
