<?php

namespace App\Http\Controllers;

use App\Models\IssueVoucher;
use App\Models\IssueVoucherItem;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Services\SequencerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IssueVoucherController extends Controller
{
    /**
     * عرض قائمة أذون الصرف
     */
    public function index(Request $request)
    {
        $query = IssueVoucher::with(['customer', 'branch', 'items']);

        // البحث برقم الإذن
        if ($request->filled('search')) {
            $query->searchByNumber($request->search);
        }

        // فلترة حسب الفرع
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // فلترة حسب العميل
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب التاريخ
        if ($request->filled('from_date')) {
            $query->whereDate('issue_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('issue_date', '<=', $request->to_date);
        }

        $vouchers = $query->orderBy('issue_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $branches = Branch::active()->get();
        $customers = Customer::active()->orderBy('name')->get();

        return view('issue_vouchers.index', compact('vouchers', 'branches', 'customers'));
    }

    /**
     * عرض نموذج إنشاء إذن صرف جديد
     */
    public function create()
    {
        $branches = Branch::active()->get();
        $customers = Customer::active()->orderBy('name')->get();
        $products = Product::with('branchStocks.branch')
            ->active()
            ->orderBy('name')
            ->get();

        return view('issue_vouchers.create', compact('branches', 'customers', 'products'));
    }

    /**
     * حفظ إذن صرف جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_type' => 'required|in:registered,cash',
            'customer_id' => 'required_if:customer_type,registered|nullable|exists:customers,id',
            'customer_name' => 'required|string|max:200',
            'branch_id' => 'required|exists:branches,id',
            'issue_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:none,percentage,fixed',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'voucher_discount_type' => 'nullable|in:none,percentage,fixed',
            'voucher_discount_value' => 'nullable|numeric|min:0',
        ], [
            'customer_id.required_if' => 'يجب اختيار عميل',
            'customer_name.required_if' => 'يجب إدخال اسم العميل',
            'branch_id.required' => 'يجب اختيار الفرع',
            'issue_date.required' => 'تاريخ الصرف مطلوب',
            'items.required' => 'يجب إضافة منتج واحد على الأقل',
            'items.min' => 'يجب إضافة منتج واحد على الأقل',
        ]);

        try {
            DB::beginTransaction();

            // 1. توليد رقم الإذن
            $voucherNumber = SequencerService::getNext('issue_voucher', 'ISS-', 5);

            $subtotal = 0; // مجموع البنود بعد خصم البند
            $itemsData = []; // لحفظ بيانات البنود مع الخصومات

            // 2. حساب مجموع البنود مع خصومات البنود
            foreach ($validated['items'] as $item) {
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];
                $totalPrice = $quantity * $unitPrice;
                
                // حساب خصم البند
                $discountType = $item['discount_type'] ?? 'none';
                $discountValue = $item['discount_value'] ?? 0;
                $discountAmount = 0;
                
                if ($discountType === 'percentage') {
                    $discountAmount = ($totalPrice * $discountValue) / 100;
                } elseif ($discountType === 'fixed') {
                    $discountAmount = min($discountValue, $totalPrice);
                }
                
                $netPrice = $totalPrice - $discountAmount;
                $subtotal += $netPrice;
                
                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'discount_amount' => $discountAmount,
                    'net_price' => $netPrice,
                ];
            }

            // 3. حساب خصم الفاتورة
            $voucherDiscountType = $validated['voucher_discount_type'] ?? 'none';
            $voucherDiscountValue = $validated['voucher_discount_value'] ?? 0;
            $voucherDiscountAmount = 0;
            
            if ($voucherDiscountType === 'percentage') {
                $voucherDiscountAmount = ($subtotal * $voucherDiscountValue) / 100;
            } elseif ($voucherDiscountType === 'fixed') {
                $voucherDiscountAmount = min($voucherDiscountValue, $subtotal);
            }
            
            $netTotal = $subtotal - $voucherDiscountAmount;

            // 4. إنشاء الإذن
            $voucher = IssueVoucher::create([
                'voucher_number' => $voucherNumber,
                'customer_id' => $validated['customer_type'] === 'registered' ? $validated['customer_id'] : null,
                'customer_name' => $validated['customer_type'] === 'cash' ? $validated['customer_name'] : null,
                'branch_id' => $validated['branch_id'],
                'issue_date' => $validated['issue_date'],
                'notes' => $validated['notes'] ?? null,
                'total_amount' => $subtotal + $voucherDiscountAmount, // المجموع الأصلي قبل خصم الفاتورة
                'discount_type' => $voucherDiscountType,
                'discount_value' => $voucherDiscountValue,
                'discount_amount' => $voucherDiscountAmount,
                'subtotal' => $subtotal,
                'net_total' => $netTotal,
                'status' => 'completed',
                'created_by' => auth()->id() ?? 1,
            ]);

            // 5. إضافة الأصناف وخصم المخزون
            foreach ($itemsData as $itemData) {
                // 5.1. إنشاء الصنف
                IssueVoucherItem::create(array_merge(
                    ['issue_voucher_id' => $voucher->id],
                    $itemData
                ));

                // 5.2. خصم المخزون مع قفل الصف
                $stock = ProductBranchStock::where('product_id', $itemData['product_id'])
                    ->where('branch_id', $validated['branch_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    throw new \Exception("المنتج غير موجود في مخزون هذا الفرع");
                }

                if ($stock->current_stock < $itemData['quantity']) {
                    $product = Product::find($itemData['product_id']);
                    throw new \Exception("المخزون غير كافٍ للمنتج: {$product->name}. المتاح: {$stock->current_stock}");
                }

                $stock->decrement('current_stock', $itemData['quantity']);
            }

            // 6. تحديث رصيد العميل (إذا كان مسجلاً) - نستخدم الصافي
            if ($voucher->customer_id) {
                $customer = Customer::lockForUpdate()->find($voucher->customer_id);
                $customer->decrement('balance', $netTotal); // عليه (مدين) - الصافي بعد الخصومات
                
                // 7. تسجيل في دفتر العميل
                CustomerLedger::record(
                    customerId: $voucher->customer_id,
                    transactionType: 'issue_voucher',
                    transactionDate: $voucher->issue_date,
                    debit: 0,
                    credit: $netTotal, // الصافي بعد الخصومات
                    referenceNumber: $voucherNumber,
                    referenceId: $voucher->id,
                    notes: 'إذن صرف - ' . $voucher->branch->name
                );
            }

            DB::commit();

            return redirect()->route('issue-vouchers.show', $voucher)
                ->with('success', "تم إنشاء إذن الصرف رقم {$voucherNumber} بنجاح");

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل إذن صرف (للطباعة)
     */
    public function show(IssueVoucher $issue_voucher)
    {
        $issue_voucher->load(['customer', 'branch', 'items.product', 'creator']);

        return view('issue_vouchers.show', ['voucher' => $issue_voucher]);
    }

    /**
     * إلغاء إذن صرف (إرجاع المخزون)
     */
    public function destroy(IssueVoucher $issueVoucher)
    {
        if ($issueVoucher->status === 'cancelled') {
            return redirect()->back()
                ->with('error', 'هذا الإذن ملغي بالفعل');
        }

        try {
            DB::beginTransaction();

            // 1. إرجاع المخزون
            foreach ($issueVoucher->items as $item) {
                $stock = ProductBranchStock::where('product_id', $item->product_id)
                    ->where('branch_id', $issueVoucher->branch_id)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $stock->increment('current_stock', $item->quantity);
                }
            }

            // 2. إرجاع رصيد العميل (إذا كان مسجلاً)
            if ($issueVoucher->customer_id) {
                $customer = Customer::lockForUpdate()->find($issueVoucher->customer_id);
                $customer->increment('balance', $issueVoucher->total_amount);
                
                // 2.1 تسجيل في دفتر العميل (إلغاء)
                CustomerLedger::record(
                    customerId: $issueVoucher->customer_id,
                    transactionType: 'issue_voucher',
                    transactionDate: now(),
                    debit: $issueVoucher->total_amount,
                    credit: 0,
                    referenceNumber: $issueVoucher->voucher_number . ' (ملغى)',
                    referenceId: $issueVoucher->id,
                    notes: 'إلغاء إذن صرف - ' . $issueVoucher->branch->name
                );
            }

            // 3. تحديث حالة الإذن
            $issueVoucher->update(['status' => 'cancelled']);

            DB::commit();

            return redirect()->route('issue-vouchers.index')
                ->with('success', 'تم إلغاء الإذن وإرجاع المخزون بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        $voucher = IssueVoucher::with(['items.product', 'branch', 'customer', 'creator'])->findOrFail($id);
        
        if ($voucher->status !== 'completed') {
            return redirect()->back()->with('error', 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø·Ø¨Ø§Ø¹Ø© Ø¥Ø°Ù† ØºÙŠØ± Ù…ÙƒØªÙ…Ù„');
        }
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.issue_voucher', compact('voucher'));
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->stream('issue_voucher_' . $voucher->voucher_number . '.pdf');
    }
}