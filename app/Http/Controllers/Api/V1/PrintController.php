<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IssueVoucher;
use App\Models\ReturnVoucher;
use App\Models\PurchaseOrder;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class PrintController extends Controller
{
    /**
     * طباعة إذن صرف
     * 
     * GET /api/v1/print/issue-voucher/{id}
     */
    public function printIssueVoucher(Request $request, $id): mixed
    {
        $validated = $request->validate([
            'format' => 'nullable|in:pdf,html',
            'template' => 'nullable|in:default,thermal',
        ]);

        $voucher = IssueVoucher::with([
            'customer',
            'branch',
            'items.product.category',
            'createdBy',
            'approvedBy'
        ])->findOrFail($id);

        // Validation: Status must be approved
        if ($voucher->status !== 'APPROVED') {
            return response()->json([
                'message' => 'لا يمكن طباعة الإذن قبل اعتماده',
                'errors' => ['status' => ['الحالة يجب أن تكون معتمد']]
            ], 422);
        }

        // Validation: Check permissions
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('print-issue-vouchers')) {
            return response()->json([
                'message' => 'ليس لديك صلاحية طباعة إذن الصرف'
            ], 403);
        }

        // Validation: Check data completeness
        if (!$voucher->customer_id && !$voucher->customer_name) {
            return response()->json([
                'message' => 'بيانات العميل غير مكتملة',
                'errors' => ['customer' => ['يجب توفر بيانات العميل']]
            ], 422);
        }

        if ($voucher->items->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد منتجات في الإذن',
                'errors' => ['items' => ['يجب أن يحتوي الإذن على منتج واحد على الأقل']]
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update tracking
            $voucher->increment('print_count');
            $voucher->update(['last_printed_at' => now()]);

            // Audit log
            activity()
                ->performedOn($voucher)
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'print',
                    'format' => $validated['format'] ?? 'pdf',
                    'template' => $validated['template'] ?? 'default',
                    'print_count' => $voucher->print_count
                ])
                ->log('طباعة إذن صرف رقم ' . $voucher->voucher_number);

            DB::commit();

            $template = $validated['template'] ?? 'default';
            $format = $validated['format'] ?? 'pdf';

            if ($format === 'html') {
                return view("pdfs.issue-voucher-{$template}", compact('voucher'));
            }

            $pdf = PDF::loadView("pdfs.issue-voucher-{$template}", compact('voucher'))
                ->setPaper('a4')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10);

            return $pdf->download("issue-voucher-{$voucher->voucher_number}.pdf");

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل في الطباعة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ في الخادم'
            ], 500);
        }
    }

    /**
     * طباعة إذن مرتجع
     * 
     * GET /api/v1/print/return-voucher/{id}
     */
    public function printReturnVoucher(Request $request, $id): mixed
    {
        $validated = $request->validate([
            'format' => 'nullable|in:pdf,html',
            'template' => 'nullable|in:default,thermal',
        ]);

        $voucher = ReturnVoucher::with([
            'customer',
            'branch',
            'items.product.category',
            'createdBy',
            'approvedBy'
        ])->findOrFail($id);

        // Validation
        if ($voucher->status !== 'APPROVED') {
            return response()->json([
                'message' => 'لا يمكن طباعة الإذن قبل اعتماده'
            ], 422);
        }

        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('print-return-vouchers')) {
            return response()->json([
                'message' => 'ليس لديك صلاحية طباعة إذن المرتجع'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $voucher->increment('print_count');
            $voucher->update(['last_printed_at' => now()]);

            activity()
                ->performedOn($voucher)
                ->causedBy(auth()->user())
                ->withProperties(['action' => 'print', 'print_count' => $voucher->print_count])
                ->log('طباعة إذن مرتجع رقم ' . $voucher->voucher_number);

            DB::commit();

            $template = $validated['template'] ?? 'default';
            $format = $validated['format'] ?? 'pdf';

            if ($format === 'html') {
                return view("pdfs.return-voucher-{$template}", compact('voucher'));
            }

            $pdf = PDF::loadView("pdfs.return-voucher-{$template}", compact('voucher'))->setPaper('a4');
            return $pdf->download("return-voucher-{$voucher->voucher_number}.pdf");

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'فشل في الطباعة', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * طباعة أمر شراء
     * 
     * GET /api/v1/print/purchase-order/{id}
     */
    public function printPurchaseOrder(Request $request, $id): mixed
    {
        $validated = $request->validate([
            'format' => 'nullable|in:pdf,html',
        ]);

        $order = PurchaseOrder::with([
            'supplier',
            'branch',
            'items.product.category',
            'createdBy',
            'approvedBy'
        ])->findOrFail($id);

        if ($order->status !== 'APPROVED') {
            return response()->json(['message' => 'لا يمكن طباعة الأمر قبل اعتماده'], 422);
        }

        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('print-purchase-orders')) {
            return response()->json(['message' => 'ليس لديك صلاحية طباعة أمر الشراء'], 403);
        }

        DB::beginTransaction();
        try {
            $order->increment('print_count');
            $order->update(['last_printed_at' => now()]);

            activity()
                ->performedOn($order)
                ->causedBy(auth()->user())
                ->withProperties(['action' => 'print'])
                ->log('طباعة أمر شراء رقم ' . $order->order_number);

            DB::commit();

            $format = $validated['format'] ?? 'pdf';

            if ($format === 'html') {
                return view('pdfs.purchase-order', compact('order'));
            }

            $pdf = PDF::loadView('pdfs.purchase-order', compact('order'))->setPaper('a4');
            return $pdf->download("purchase-order-{$order->order_number}.pdf");

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'فشل في الطباعة', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * طباعة كشف حساب عميل
     * 
     * GET /api/v1/print/customer-statement/{customerId}
     */
    public function printCustomerStatement(Request $request, $customerId): mixed
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'format' => 'nullable|in:pdf,html',
        ]);

        $customer = Customer::with([
            'ledgerEntries' => function ($query) use ($validated) {
                $query->whereBetween('date', [$validated['from_date'], $validated['to_date']])
                    ->orderBy('date', 'asc')
                    ->orderBy('id', 'asc');
            }
        ])->findOrFail($customerId);

        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('print-customer-statements')) {
            return response()->json(['message' => 'ليس لديك صلاحية طباعة كشف الحساب'], 403);
        }

        // Calculate opening balance
        $openingBalance = $customer->ledgerEntries()
            ->where('date', '<', $validated['from_date'])
            ->sum(DB::raw('debit - credit'));

        $data = [
            'customer' => $customer,
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
            'opening_balance' => $openingBalance,
            'entries' => $customer->ledgerEntries,
        ];

        activity()
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->log('طباعة كشف حساب من ' . $validated['from_date'] . ' إلى ' . $validated['to_date']);

        $format = $validated['format'] ?? 'pdf';

        if ($format === 'html') {
            return view('pdfs.customer-statement', $data);
        }

        $pdf = PDF::loadView('pdfs.customer-statement', $data)->setPaper('a4');
        return $pdf->download("customer-statement-{$customer->code}.pdf");
    }

    /**
     * طباعة شيك
     * 
     * GET /api/v1/print/cheque/{id}
     */
    public function printCheque(Request $request, $id): mixed
    {
        $payment = Payment::with(['customer', 'createdBy'])
            ->where('payment_method', 'cheque')
            ->findOrFail($id);

        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('print-cheques')) {
            return response()->json(['message' => 'ليس لديك صلاحية طباعة الشيك'], 403);
        }

        activity()
            ->performedOn($payment)
            ->causedBy(auth()->user())
            ->log('طباعة شيك رقم ' . $payment->cheque_number);

        $pdf = PDF::loadView('pdfs.cheque', compact('payment'))
            ->setPaper([0, 0, 612, 288]); // Cheque size

        return $pdf->download("cheque-{$payment->cheque_number}.pdf");
    }

    /**
     * طباعة جماعية
     * 
     * POST /api/v1/print/bulk
     */
    public function bulkPrint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => 'required|in:issue-voucher,return-voucher,purchase-order',
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'required|integer',
            'format' => 'nullable|in:pdf',
        ]);

        $documentType = $validated['document_type'];
        $ids = $validated['ids'];

        // Get documents based on type
        $documents = match($documentType) {
            'issue-voucher' => IssueVoucher::with(['customer', 'branch', 'items.product'])->whereIn('id', $ids)->get(),
            'return-voucher' => ReturnVoucher::with(['customer', 'branch', 'items.product'])->whereIn('id', $ids)->get(),
            'purchase-order' => PurchaseOrder::with(['supplier', 'branch', 'items.product'])->whereIn('id', $ids)->get(),
        };

        if ($documents->isEmpty()) {
            return response()->json(['message' => 'لا توجد مستندات'], 404);
        }

        // Validate all are approved
        $notApproved = $documents->where('status', '!=', 'APPROVED');
        if ($notApproved->count() > 0) {
            return response()->json([
                'message' => 'بعض المستندات غير معتمدة',
                'not_approved_ids' => $notApproved->pluck('id')->toArray()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update all print counts
            foreach ($documents as $doc) {
                $doc->increment('print_count');
                $doc->update(['last_printed_at' => now()]);
            }

            activity()
                ->causedBy(auth()->user())
                ->withProperties(['document_type' => $documentType, 'count' => count($ids)])
                ->log("طباعة جماعية: {$documentType} - " . count($ids) . " مستند");

            DB::commit();

            $pdf = PDF::loadView('pdfs.bulk-print', [
                'documents' => $documents,
                'type' => $documentType
            ])->setPaper('a4');

            return response()->json([
                'message' => 'تم إعداد الطباعة بنجاح',
                'count' => $documents->count(),
                'download_url' => null // TODO: Implement bulk download endpoint
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'فشل في الطباعة الجماعية', 'error' => $e->getMessage()], 500);
        }
    }
}
