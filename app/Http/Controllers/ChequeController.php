<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ChequeController extends Controller
{
    /**
     * عرض قائمة الشيكات
     */
    public function index(Request $request)
    {
        $query = Cheque::with(['customer', 'issueVoucher', 'creator', 'clearedBy']);

        // التصفية حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // افتراضياً: عرض الشيكات المعلقة فقط
            $query->pending();
        }

        // التصفية حسب العميل
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // التصفية حسب فترة الاستحقاق
        if ($request->filled('due_from') && $request->filled('due_to')) {
            $query->dueDateRange($request->due_from, $request->due_to);
        }

        $cheques = $query->orderBy('due_date', 'asc')
                        ->orderBy('id', 'desc')
                        ->paginate(15);

        $customers = Customer::active()->orderBy('name')->get();

        // إحصائيات
        $stats = [
            'pending_count' => Cheque::pending()->count(),
            'pending_amount' => Cheque::pending()->sum('amount'),
            'due_soon_count' => Cheque::dueSoon(7)->count(),
            'due_soon_amount' => Cheque::dueSoon(7)->sum('amount'),
            'overdue_count' => Cheque::overdue()->count(),
            'cleared_count' => Cheque::where('status', 'CLEARED')
                                    ->whereMonth('cleared_at', now()->month)
                                    ->whereYear('cleared_at', now()->year)
                                    ->count(),
        ];

        return view('cheques.index', compact('cheques', 'customers', 'stats'));
    }

    /**
     * تقرير الشيكات غير المصروفة (PENDING)
     */
    public function pending(Request $request)
    {
        // جميع الشيكات المعلقة
        $pendingCheques = Cheque::with(['customer', 'issueVoucher', 'creator', 'payment'])
                               ->pending()
                               ->orderBy('due_date', 'asc')
                               ->get();

        // تقسيم الشيكات حسب الأولوية
        $overdueCheques = $pendingCheques->filter(function ($cheque) {
            return \Carbon\Carbon::parse($cheque->due_date)->isPast();
        });

        $dueSoonCheques = $pendingCheques->filter(function ($cheque) {
            $dueDate = \Carbon\Carbon::parse($cheque->due_date);
            return $dueDate->isFuture() && $dueDate->lte(now()->addDays(7));
        });

        $otherPendingCheques = $pendingCheques->filter(function ($cheque) {
            $dueDate = \Carbon\Carbon::parse($cheque->due_date);
            return $dueDate->gt(now()->addDays(7));
        });

        return view('cheques.pending', compact(
            'pendingCheques',
            'overdueCheques',
            'dueSoonCheques',
            'otherPendingCheques'
        ));
    }

    /**
     * صرف شيك (تحويله إلى CLEARED)
     */
    public function clear(Request $request, Cheque $cheque)
    {
        if ($cheque->status !== 'PENDING') {
            return redirect()->back()
                ->with('error', 'الشيك ليس في حالة انتظار');
        }

        try {
            DB::beginTransaction();

            // 1. تحديث حالة الشيك
            $cheque->update([
                'status' => 'CLEARED',
                'cleared_at' => now(),
                'cleared_by' => Auth::id(),
            ]);

            // 2. لا نحتاج تحديث رصيد العميل لأنه تم عند إنشاء السداد

            DB::commit();

            return redirect()->back()
                ->with('success', 'تم صرف الشيك رقم ' . $cheque->cheque_number . ' بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * إرجاع شيك (تحويله إلى BOUNCED)
     */
    public function return(Request $request, Cheque $cheque)
    {
        if ($cheque->status !== 'PENDING') {
            return redirect()->back()
                ->with('error', 'الشيك ليس في حالة انتظار');
        }

        $validated = $request->validate([
            'return_reason' => 'required|string',
        ], [
            'return_reason.required' => 'سبب الإرجاع مطلوب',
        ]);

        try {
            DB::beginTransaction();

            // 1. تحديث حالة الشيك إلى مرتد
            $cheque->update([
                'status' => 'BOUNCED',
                'return_reason' => $validated['return_reason'],
            ]);

            // 2. إرجاع رصيد العميل (عكس السداد - نضيف الدين مرة تانية)
            $customer = \App\Models\Customer::lockForUpdate()->find($cheque->customer_id);
            $customer->decrement('balance', $cheque->amount);

            // 3. تسجيل في دفتر العميل (debit = عليه = يضاف للرصيد)
            \App\Models\LedgerEntry::create([
                'customer_id' => $cheque->customer_id,
                'type' => 'debit',
                'amount' => $cheque->amount,
                'description' => 'إرجاع شيك رقم ' . $cheque->cheque_number . ' - السبب: ' . $validated['return_reason'],
                'reference_type' => 'Cheque',
                'reference_id' => $cheque->id,
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'تم إرجاع الشيك رقم ' . $cheque->cheque_number);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل شيك
     */
    public function show(Cheque $cheque)
    {
        $cheque->load(['customer', 'issueVoucher', 'creator', 'clearedBy', 'payment']);
        
        return view('cheques.show', compact('cheque'));
    }
}
