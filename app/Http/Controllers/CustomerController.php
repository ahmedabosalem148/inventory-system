<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * عرض قائمة العملاء
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // البحث
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // فلترة حسب الحالة
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // فلترة حسب الرصيد
        if ($request->filled('balance_filter')) {
            switch ($request->balance_filter) {
                case 'credit': // له
                    $query->withCredit();
                    break;
                case 'debit': // عليه
                    $query->withDebit();
                    break;
                case 'zero': // متزن
                    $query->where('balance', 0);
                    break;
            }
        }

        $customers = $query->orderBy('name')->paginate(15);

        return view('customers.index', compact('customers'));
    }

    /**
     * عرض نموذج إضافة عميل جديد
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * حفظ عميل جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'balance' => 'nullable|numeric',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'اسم العميل مطلوب',
            'name.max' => 'اسم العميل يجب ألا يتجاوز 200 حرف',
            'phone.max' => 'رقم الهاتف يجب ألا يتجاوز 20 حرف',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['balance'] = $validated['balance'] ?? 0;

        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'تم إضافة العميل بنجاح');
    }

    /**
     * عرض تفاصيل عميل (دفتر الحساب)
     */
    public function show(Customer $customer, Request $request)
    {
        // Build query for ledger entries
        $query = $customer->ledgerEntries()->with('creator');

        // Filter by transaction type
        if ($request->filled('transaction_type')) {
            $query->byType($request->transaction_type);
        }

        // Filter by date range
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->dateRange($request->date_from, $request->date_to);
        }

        // Get ledger entries ordered by date (newest first)
        $ledgerEntries = $query->orderBy('transaction_date', 'desc')
                               ->orderBy('id', 'desc')
                               ->paginate(20);

        // Calculate summary statistics
        $stats = [
            'total_debits' => $customer->ledgerEntries()->sum('debit'),
            'total_credits' => $customer->ledgerEntries()->sum('credit'),
            'current_balance' => $customer->balance,
        ];

        return view('customers.ledger', compact('customer', 'ledgerEntries', 'stats'));
    }

    /**
     * عرض نموذج تعديل عميل
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * تحديث بيانات عميل
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'balance' => 'nullable|numeric',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'اسم العميل مطلوب',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'تم تعديل بيانات العميل بنجاح');
    }

    /**
     * حذف عميل
     */
    public function destroy(Customer $customer)
    {
        try {
            // التحقق من وجود رصيد
            if ($customer->balance != 0) {
                $balanceText = $customer->balance > 0 ? 'له (دائن)' : 'عليه (مدين)';
                $balanceAmount = number_format(abs($customer->balance), 2);
                
                return redirect()->back()
                    ->with('error', "لا يمكن حذف العميل. يوجد رصيد: {$balanceAmount} ج.م ({$balanceText}). يجب تسوية الحساب أولاً.");
            }

            // التحقق من وجود حركات
            $hasVouchers = $customer->issueVouchers()->exists() || $customer->returnVouchers()->exists();
            if ($hasVouchers) {
                return redirect()->back()
                    ->with('error', 'لا يمكن حذف العميل. يوجد معاملات مسجلة على حسابه');
            }

            $customer->delete();

            return redirect()->route('customers.index')
                ->with('success', 'تم حذف العميل بنجاح');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء الحذف: ' . $e->getMessage());
        }
    }
}
