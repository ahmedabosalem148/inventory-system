<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Customer;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Inventory Summary Report
     * عرض الرصيد الحالي لكل منتج/فرع
     */
    public function inventorySummary(Request $request)
    {
        $query = ProductBranchStock::with(['product.category', 'branch'])
            ->select('product_branch_stock.*');

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter: Below minimum quantity
        if ($request->filled('below_min') && $request->below_min == '1') {
            $query->whereColumn('current_stock', '<', DB::raw('(SELECT min_stock FROM products WHERE products.id = product_branch_stock.product_id)'));
        }

        // Order by
        $orderBy = $request->get('order_by', 'product_id');
        $orderDir = $request->get('order_dir', 'asc');
        
        if ($orderBy === 'product_name') {
            $query->join('products', 'product_branch_stock.product_id', '=', 'products.id')
                ->orderBy('products.name', $orderDir)
                ->select('product_branch_stock.*');
        } elseif ($orderBy === 'branch_name') {
            $query->join('branches', 'product_branch_stock.branch_id', '=', 'branches.id')
                ->orderBy('branches.name', $orderDir)
                ->select('product_branch_stock.*');
        } else {
            $query->orderBy($orderBy, $orderDir);
        }

        $inventory = $query->paginate(50);

        // Get filter options
        $branches = Branch::where('is_active', true)->get();
        $categories = Category::all();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        // Calculate statistics
        $stats = [
            'total_items' => ProductBranchStock::count(),
            'total_quantity' => ProductBranchStock::sum('current_stock'),
            'below_min_count' => ProductBranchStock::whereColumn('current_stock', '<', DB::raw('(SELECT min_stock FROM products WHERE products.id = product_branch_stock.product_id)'))->count(),
            'out_of_stock' => ProductBranchStock::where('current_stock', 0)->count(),
        ];

        return view('reports.inventory-summary', compact(
            'inventory',
            'branches',
            'categories',
            'products',
            'stats'
        ));
    }

    /**
     * Export Inventory Summary to CSV
     */
    public function inventorySummaryCSV(Request $request)
    {
        $query = ProductBranchStock::with(['product.category', 'branch']);

        // Apply same filters as main report
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('below_min') && $request->below_min == '1') {
            $query->whereColumn('current_stock', '<', DB::raw('(SELECT min_stock FROM products WHERE products.id = product_branch_stock.product_id)'));
        }

        $inventory = $query->get();

        $csv = "الكود,المنتج,التصنيف,الفرع,الرصيد الحالي,الحد الأدنى,الحالة\n";
        
        foreach ($inventory as $item) {
            $status = $item->current_stock < $item->product->min_stock ? 'أقل من الحد الأدنى' : 'طبيعي';
            if ($item->current_stock == 0) {
                $status = 'نفذ من المخزن';
            }
            
            $csv .= sprintf(
                "%s,%s,%s,%s,%d,%d,%s\n",
                $item->product->sku,
                $item->product->name,
                $item->product->category->name ?? '-',
                $item->branch->name,
                $item->current_stock,
                $item->product->min_stock,
                $status
            );
        }

        return response($csv, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="تقرير_المخزون_' . date('Y-m-d') . '.csv"');
    }

    /**
     * Export Inventory Summary to PDF
     */
    public function inventorySummaryPDF(Request $request)
    {
        $query = ProductBranchStock::with(['product.category', 'branch']);

        // Apply same filters
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('below_min') && $request->below_min == '1') {
            $query->whereColumn('current_stock', '<', DB::raw('(SELECT min_stock FROM products WHERE products.id = product_branch_stock.product_id)'));
        }

        $inventory = $query->get();

        $stats = [
            'total_items' => $inventory->count(),
            'total_quantity' => $inventory->sum('current_stock'),
            'below_min_count' => $inventory->filter(function($item) {
                return $item->current_stock < $item->product->min_stock;
            })->count(),
        ];

        $pdf = Pdf::loadView('reports.inventory-summary-pdf', compact('inventory', 'stats'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->stream('تقرير_المخزون_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Product Movement Report (TASK-021)
     * سجل كل حركات منتج معيّن في فترة
     */
    public function productMovement(Request $request)
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->get();
        
        $movements = collect([]);
        $product = null;
        
        if ($request->filled('product_id')) {
            $query = InventoryMovement::with(['product', 'branch'])
                ->where('product_id', $request->product_id);
            
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
            
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            $movements = $query->orderBy('created_at', 'desc')->paginate(50);
            $product = Product::find($request->product_id);
        }
        
        return view('reports.product-movement', compact('movements', 'products', 'branches', 'product'));
    }

    /**
     * Customer Balances Report (TASK-022)
     * قائمة بكل العملاء مع رصيد كل منهم وآخر نشاط
     */
    public function customerBalances(Request $request)
    {
        $query = Customer::query()
            ->select('customers.*')
            ->selectRaw('(SELECT COALESCE(SUM(debit_aliah), 0) - COALESCE(SUM(credit_lah), 0) 
                FROM customer_ledger_entries 
                WHERE customer_id = customers.id) as balance')
            ->selectRaw('(SELECT COUNT(*) 
                FROM issue_vouchers 
                WHERE customer_id = customers.id AND status = "completed") as invoices_count')
            ->selectRaw('(SELECT COUNT(*) 
                FROM return_vouchers 
                WHERE customer_id = customers.id AND status = "completed") as returns_count');
        
        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Filter by balance type
        if ($request->filled('balance_type')) {
            if ($request->balance_type == 'debit') {
                $query->havingRaw('balance > 0');
            } elseif ($request->balance_type == 'credit') {
                $query->havingRaw('balance < 0');
            } elseif ($request->balance_type == 'zero') {
                $query->havingRaw('balance = 0');
            }
        }
        
        // Order by
        $orderBy = $request->get('order_by', 'name');
        if ($orderBy === 'balance') {
            $query->orderByRaw('balance DESC');
        } else {
            $query->orderBy($orderBy);
        }
        
        $customers = $query->paginate(50);
        
        // Statistics
        $stats = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('is_active', true)->count(),
            'total_debit' => LedgerEntry::where('type', 'debit')->sum('amount'),
            'total_credit' => LedgerEntry::where('type', 'credit')->sum('amount'),
        ];
        $stats['net_balance'] = $stats['total_debit'] - $stats['total_credit'];
        
        return view('reports.customer-balances', compact('customers', 'stats'));
    }

    /**
     * Inactive Customers Report (TASK-023)
     * عملاء لم يشتروا منذ N شهر
     */
    public function inactiveCustomers(Request $request)
    {
        $months = $request->get('months', 12);
        
        $query = Customer::query()
            ->select('customers.*')
            ->selectRaw('(SELECT COALESCE(SUM(debit_aliah), 0) - COALESCE(SUM(credit_lah), 0) 
                FROM customer_ledger_entries 
                WHERE customer_id = customers.id) as balance')
            ->where(function($q) use ($months) {
                $q->whereNull('last_activity_at')
                  ->orWhere('last_activity_at', '<', now()->subMonths($months));
            });
        
        $customers = $query->orderBy('last_activity_at', 'asc')->paginate(50);
        
        return view('reports.inactive-customers', compact('customers', 'months'));
    }

    /**
     * Customer Statement PDF (TASK-024)
     * طباعة كشف حساب عميل كامل مع الرصيد
     */
    public function customerStatement(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        $query = LedgerEntry::where('customer_id', $customerId)
            ->orderBy('created_at', 'asc');
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        
        $entries = $query->get();
        
        // Calculate running balance
        $runningBalance = 0;
        foreach ($entries as $entry) {
            if ($entry->type === 'debit') {
                $runningBalance += $entry->amount;
            } else {
                $runningBalance -= $entry->amount;
            }
            $entry->running_balance = $runningBalance;
        }
        
        $stats = [
            'total_debit' => $entries->where('type', 'debit')->sum('amount'),
            'total_credit' => $entries->where('type', 'credit')->sum('amount'),
            'final_balance' => $runningBalance,
            'entries_count' => $entries->count(),
        ];
        
        $pdf = Pdf::loadView('reports.customer-statement-pdf', compact('customer', 'entries', 'stats', 'request'));
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->stream('كشف_حساب_' . $customer->code . '_' . date('Y-m-d') . '.pdf');
    }
}
