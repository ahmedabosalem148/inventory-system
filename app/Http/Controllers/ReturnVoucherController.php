<?php

namespace App\Http\Controllers;

use App\Models\ReturnVoucher;
use App\Models\ReturnVoucherItem;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Services\SequencerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReturnVoucherController extends Controller
{
    /**
     * عرض قائمة أذون الإرجاع
     */
    public function index(Request $request)
    {
        $query = ReturnVoucher::with(['customer', 'branch', 'creator']);

        // البحث برقم الإذن
        if ($request->filled('search')) {
            $query->searchByNumber($request->search);
        }

        // التصفية حسب الفرع
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // التصفية حسب العميل
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // التصفية حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // التصفية حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('return_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('return_date', '<=', $request->date_to);
        }

        $vouchers = $query->orderBy('return_date', 'desc')
                          ->orderBy('id', 'desc')
                          ->paginate(15);

        $branches = Branch::active()->get();
        $customers = Customer::active()->get();

        return view('return_vouchers.index', compact('vouchers', 'branches', 'customers'));
    }

    /**
     * عرض نموذج إنشاء إذن إرجاع جديد
     */
    public function create()
    {
        $branches = Branch::active()->get();
        $customers = Customer::active()->get();
        $products = Product::with('branchStocks')->active()->get();

        return view('return_vouchers.create', compact('branches', 'customers', 'products'));
    }

    /**
     * حفظ إذن الإرجاع الجديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'return_date' => 'required|date',
            'customer_type' => 'required|in:registered,cash',
            'customer_id' => 'required_if:customer_type,registered|nullable|exists:customers,id',
            'customer_name' => 'required_if:customer_type,cash|nullable|string|max:200',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'branch_id.required' => 'الفرع مطلوب',
            'return_date.required' => 'تاريخ الإرجاع مطلوب',
            'customer_type.required' => 'نوع العميل مطلوب',
            'customer_id.required_if' => 'العميل مطلوب للعميل المسجل',
            'customer_name.required_if' => 'اسم العميل مطلوب للعميل النقدي',
            'items.required' => 'يجب إضافة منتج واحد على الأقل',
            'items.min' => 'يجب إضافة منتج واحد على الأقل',
        ]);

        try {
            DB::beginTransaction();

            // توليد رقم الإذن تلقائياً
            $voucherNumber = SequencerService::getNext('return_voucher', 'RET-', 6);

            // إنشاء إذن الإرجاع
            $voucher = ReturnVoucher::create([
                'voucher_number' => $voucherNumber,
                'customer_id' => $validated['customer_type'] === 'registered' ? $validated['customer_id'] : null,
                'customer_name' => $validated['customer_type'] === 'cash' ? $validated['customer_name'] : null,
                'branch_id' => $validated['branch_id'],
                'return_date' => $validated['return_date'],
                'total_amount' => 0,
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $totalAmount = 0;

            // إضافة الأصناف وزيادة المخزون
            foreach ($validated['items'] as $itemData) {
                // إنشاء الصنف
                $item = ReturnVoucherItem::create([
                    'return_voucher_id' => $voucher->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                ]);

                $totalAmount += $item->total_price;

                // زيادة المخزون (عكس عملية الصرف)
                $stock = ProductBranchStock::lockForUpdate()
                    ->where('product_id', $itemData['product_id'])
                    ->where('branch_id', $validated['branch_id'])
                    ->first();

                if (!$stock) {
                    // إنشاء سجل مخزون جديد إذا لم يكن موجوداً
                    ProductBranchStock::create([
                        'product_id' => $itemData['product_id'],
                        'branch_id' => $validated['branch_id'],
                        'current_stock' => $itemData['quantity'],
                    ]);
                } else {
                    // زيادة المخزون
                    $stock->increment('current_stock', $itemData['quantity']);
                }
            }

            // تحديث إجمالي المبلغ
            $voucher->update(['total_amount' => $totalAmount]);

            // تحديث رصيد العميل (إذا كان مسجلاً) - زيادة رصيد العميل (عليه)
            if ($validated['customer_type'] === 'registered') {
                $customer = Customer::find($validated['customer_id']);
                $customer->decrement('balance', $totalAmount); // تقليل الرصيد = زيادة المديونية (عليه)
                
                // تسجيل في دفتر العميل
                CustomerLedger::record(
                    customerId: $validated['customer_id'],
                    transactionType: 'return_voucher',
                    transactionDate: $voucher->return_date,
                    debit: $totalAmount,
                    credit: 0,
                    referenceNumber: $voucherNumber,
                    referenceId: $voucher->id,
                    notes: 'إذن إرجاع - ' . $voucher->branch->name
                );
            }

            DB::commit();

            return redirect()->route('return-vouchers.show', $voucher)
                ->with('success', 'تم إنشاء إذن الإرجاع بنجاح - رقم الإذن: ' . $voucherNumber);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إنشاء إذن الإرجاع: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل إذن الإرجاع
     */
    public function show(ReturnVoucher $return_voucher)
    {
        $return_voucher->load(['customer', 'branch', 'items.product', 'creator']);
        
        return view('return_vouchers.show', ['voucher' => $return_voucher]);
    }

    /**
     * إلغاء إذن الإرجاع
     */
    public function destroy(ReturnVoucher $returnVoucher)
    {
        if ($returnVoucher->status === 'cancelled') {
            return back()->with('error', 'الإذن ملغى بالفعل');
        }

        try {
            DB::beginTransaction();

            // إرجاع المخزون (خصم الكميات المرتجعة)
            foreach ($returnVoucher->items as $item) {
                $stock = ProductBranchStock::lockForUpdate()
                    ->where('product_id', $item->product_id)
                    ->where('branch_id', $returnVoucher->branch_id)
                    ->first();

                if ($stock) {
                    // التأكد من أن المخزون كافٍ للخصم
                    if ($stock->current_stock < $item->quantity) {
                        throw new \Exception("المخزون الحالي للمنتج {$item->product->name} غير كافٍ لإلغاء الإذن");
                    }
                    
                    $stock->decrement('current_stock', $item->quantity);
                }
            }

            // إرجاع رصيد العميل (إذا كان مسجلاً)
            if ($returnVoucher->customer_id) {
                $returnVoucher->customer->increment('balance', $returnVoucher->total_amount);
                
                // تسجيل في دفتر العميل (إلغاء)
                CustomerLedger::record(
                    customerId: $returnVoucher->customer_id,
                    transactionType: 'return_voucher',
                    transactionDate: now(),
                    debit: 0,
                    credit: $returnVoucher->total_amount,
                    referenceNumber: $returnVoucher->voucher_number . ' (ملغى)',
                    referenceId: $returnVoucher->id,
                    notes: 'إلغاء إذن إرجاع - ' . $returnVoucher->branch->name
                );
            }

            // تحديث حالة الإذن
            $returnVoucher->update(['status' => 'cancelled']);

            DB::commit();

            return back()->with('success', 'تم إلغاء إذن الإرجاع بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء إلغاء الإذن: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        $voucher = ReturnVoucher::with(['items.product', 'branch', 'customer', 'creator'])->findOrFail($id);
        
        if ($voucher->status !== 'completed') {
            return redirect()->back()->with('error', 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø·Ø¨Ø§Ø¹Ø© Ø¥Ø°Ù† ØºÙŠØ± Ù…ÙƒØªÙ…Ù„');
        }
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.return_voucher', compact('voucher'));
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->stream('return_voucher_' . $voucher->voucher_number . '.pdf');
    }
}