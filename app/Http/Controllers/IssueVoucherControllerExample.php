<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IssueVoucher;
use Illuminate\Support\Facades\DB;

/**
 * مثال على استخدام Activity Logging لإذونات الصرف
 */
class IssueVoucherControllerExample extends Controller
{
    /**
     * اعتماد إذن صرف
     */
    public function approve(IssueVoucher $issueVoucher)
    {
        $this->authorize('approve', $issueVoucher);

        // التأكد من أن الإذن ليس معتمداً مسبقاً
        if ($issueVoucher->status === 'APPROVED') {
            return back()->with('error', 'الإذن معتمد مسبقاً');
        }

        DB::beginTransaction();
        try {
            $issueVoucher->update([
                'status' => 'APPROVED',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // ✅ Manual Activity Logging للاعتماد (عملية حساسة)
            activity()
                ->performedOn($issueVoucher)
                ->causedBy(auth()->user())
                ->withProperties([
                    'voucher_number' => $issueVoucher->number,
                    'total_amount' => $issueVoucher->total_after,
                    'customer_id' => $issueVoucher->customer_id,
                    'ip_address' => request()->ip(),
                ])
                ->log('تم اعتماد إذن صرف رقم ' . $issueVoucher->number . ' بقيمة ' . $issueVoucher->total_after);

            // هنا يمكن إضافة:
            // - تحديث المخزون
            // - تحديث حساب العميل
            // - إرسال إشعار

            DB::commit();
            return redirect()->route('issue-vouchers.show', $issueVoucher)
                ->with('success', 'تم اعتماد الإذن بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            
            // ✅ تسجيل الأخطاء
            activity()
                ->performedOn($issueVoucher)
                ->causedBy(auth()->user())
                ->withProperties(['error' => $e->getMessage()])
                ->log('فشل اعتماد إذن صرف رقم ' . $issueVoucher->number);

            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * طباعة إذن صرف
     */
    public function print(IssueVoucher $issueVoucher)
    {
        $this->authorize('print', $issueVoucher);

        // ✅ Manual Activity Logging للطباعة (للمراجعة)
        activity()
            ->performedOn($issueVoucher)
            ->causedBy(auth()->user())
            ->withProperties([
                'voucher_number' => $issueVoucher->number,
                'printed_at' => now(),
            ])
            ->log('تم طباعة إذن صرف رقم ' . $issueVoucher->number);

        return view('issue-vouchers.print', compact('issueVoucher'));
    }

    /**
     * إلغاء إذن صرف
     */
    public function cancel(IssueVoucher $issueVoucher)
    {
        $this->authorize('delete', $issueVoucher);

        if ($issueVoucher->status === 'APPROVED') {
            return back()->with('error', 'لا يمكن إلغاء إذن معتمد');
        }

        DB::beginTransaction();
        try {
            // ✅ حفظ البيانات قبل الحذف
            $voucherData = $issueVoucher->toArray();
            
            $issueVoucher->update(['status' => 'CANCELLED']);

            activity()
                ->performedOn($issueVoucher)
                ->causedBy(auth()->user())
                ->withProperties($voucherData)
                ->log('تم إلغاء إذن صرف رقم ' . $issueVoucher->number);

            DB::commit();
            return redirect()->route('issue-vouchers.index')
                ->with('success', 'تم إلغاء الإذن بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
}
