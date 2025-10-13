<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\IssueVoucher;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\StockMovement;
use App\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(
        private LedgerService $ledgerService
    ) {}

    /**
     * تقرير المخزون حسب الفرع
     */
    public function inventoryByBranch(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:categories,id',
            'low_stock_only' => 'boolean',
        ]);

        $query = ProductBranchStock::with(['product.category', 'branch'])
            ->select('product_branch_stock.*')
            ->join('products', 'products.id', '=', 'product_branch_stock.product_id');

        if (isset($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }

        if (isset($validated['category_id'])) {
            $query->where('products.category_id', $validated['category_id']);
        }

        if ($validated['low_stock_only'] ?? false) {
            $query->whereRaw('product_branch_stock.current_stock < products.reorder_level');
        }

        $stock = $query->orderBy('products.name')->get();

        // حساب القيم
        $stockWithValues = $stock->map(function ($item) {
            $stockValue = $item->current_stock * $item->product->cost_price;
            return [
                'product_id' => $item->product_id,
                'product_code' => $item->product->code,
                'product_name' => $item->product->name,
                'category' => $item->product->category?->name,
                'branch_id' => $item->branch_id,
                'branch_name' => $item->branch->name,
                'current_stock' => $item->current_stock,
                'unit' => $item->product->unit,
                'cost_price' => $item->product->cost_price,
                'selling_price' => $item->product->selling_price,
                'stock_value' => round($stockValue, 2),
                'reorder_level' => $item->product->reorder_level,
                'is_low_stock' => $item->current_stock < $item->product->reorder_level,
            ];
        });

        return response()->json([
            'data' => $stockWithValues,
            'summary' => [
                'total_items' => $stockWithValues->count(),
                'total_value' => round($stockWithValues->sum('stock_value'), 2),
                'low_stock_items' => $stockWithValues->where('is_low_stock', true)->count(),
            ],
        ]);
    }

    /**
     * تقرير حركة الأصناف
     */
    public function productMovement(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'nullable|exists:branches,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'movement_type' => 'nullable|in:issue,return,transfer_out,transfer_in',
        ]);

        $query = StockMovement::with(['branch', 'user'])
            ->where('product_id', $validated['product_id']);

        if (isset($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }

        if (isset($validated['from_date'])) {
            $query->whereDate('created_at', '>=', $validated['from_date']);
        }

        if (isset($validated['to_date'])) {
            $query->whereDate('created_at', '<=', $validated['to_date']);
        }

        if (isset($validated['movement_type'])) {
            $query->where('movement_type', $validated['movement_type']);
        }

        $movements = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'product' => Product::find($validated['product_id']),
            'data' => $movements,
            'summary' => [
                'total_movements' => $movements->count(),
                'total_issues' => $movements->where('movement_type', 'issue')->sum('quantity'),
                'total_returns' => $movements->where('movement_type', 'return')->sum('quantity'),
            ],
        ]);
    }

    /**
     * كشف حساب عميل
     */
    public function customerStatement(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $query = $customer->ledgerEntries()->with(['user']);

        if (isset($validated['from_date'])) {
            $query->whereDate('entry_date', '>=', $validated['from_date']);
        }

        if (isset($validated['to_date'])) {
            $query->whereDate('entry_date', '<=', $validated['to_date']);
        }

        $entries = $query->orderBy('entry_date')->orderBy('id')->get();

        // حساب الرصيد الجاري
        $runningBalance = 0;
        $entriesWithBalance = $entries->map(function ($entry) use (&$runningBalance) {
            $runningBalance += $entry->debit - $entry->credit;
            return [
                'id' => $entry->id,
                'date' => $entry->entry_date,
                'description' => $entry->description,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'balance' => round($runningBalance, 2),
                'voucher_type' => $entry->voucher_type,
                'created_by' => $entry->user?->name,
            ];
        });

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'type' => $customer->type,
            ],
            'data' => $entriesWithBalance,
            'summary' => [
                'total_debits' => round($entries->sum('debit'), 2),
                'total_credits' => round($entries->sum('credit'), 2),
                'current_balance' => round($runningBalance, 2),
            ],
        ]);
    }

    /**
     * تقرير المبيعات
     */
    public function salesReport(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $query = IssueVoucher::with(['customer', 'branch', 'items.product'])
            ->where('status', 'completed')
            ->whereDate('issue_date', '>=', $validated['from_date'])
            ->whereDate('issue_date', '<=', $validated['to_date']);

        if (isset($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }

        if (isset($validated['customer_id'])) {
            $query->where('customer_id', $validated['customer_id']);
        }

        $vouchers = $query->get();

        // تحليل المبيعات
        $totalSales = $vouchers->sum('net_total');
        $totalDiscounts = $vouchers->sum('discount_amount');
        $vouchersCount = $vouchers->count();

        // المبيعات حسب الفرع
        $salesByBranch = $vouchers->groupBy('branch_id')->map(function ($branchVouchers, $branchId) {
            $branch = Branch::find($branchId);
            return [
                'branch_id' => $branchId,
                'branch_name' => $branch?->name,
                'vouchers_count' => $branchVouchers->count(),
                'total_sales' => round($branchVouchers->sum('net_total'), 2),
            ];
        })->values();

        // المبيعات حسب الأصناف
        $allItems = $vouchers->flatMap(fn($v) => $v->items);
        $salesByProduct = $allItems->groupBy('product_id')->map(function ($items, $productId) {
            $product = Product::find($productId);
            return [
                'product_id' => $productId,
                'product_code' => $product?->code,
                'product_name' => $product?->name,
                'quantity_sold' => $items->sum('quantity'),
                'total_sales' => round($items->sum('total'), 2),
            ];
        })->sortByDesc('total_sales')->take(20)->values();

        return response()->json([
            'summary' => [
                'total_vouchers' => $vouchersCount,
                'total_sales' => round($totalSales, 2),
                'total_discounts' => round($totalDiscounts, 2),
                'average_voucher_value' => $vouchersCount > 0 ? round($totalSales / $vouchersCount, 2) : 0,
            ],
            'sales_by_branch' => $salesByBranch,
            'top_products' => $salesByProduct,
        ]);
    }

    /**
     * تقرير الأرباح
     */
    public function profitReport(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $query = IssueVoucher::with(['items.product'])
            ->where('status', 'completed')
            ->whereDate('issue_date', '>=', $validated['from_date'])
            ->whereDate('issue_date', '<=', $validated['to_date']);

        if (isset($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }

        $vouchers = $query->get();

        $totalRevenue = 0;
        $totalCost = 0;

        foreach ($vouchers as $voucher) {
            foreach ($voucher->items as $item) {
                $revenue = $item->quantity * $item->unit_price;
                $cost = $item->quantity * $item->product->cost_price;
                
                $totalRevenue += $revenue;
                $totalCost += $cost;
            }
        }

        $totalProfit = $totalRevenue - $totalCost;
        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        return response()->json([
            'summary' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_cost' => round($totalCost, 2),
                'total_profit' => round($totalProfit, 2),
                'profit_margin_percentage' => round($profitMargin, 2),
                'vouchers_count' => $vouchers->count(),
            ],
        ]);
    }

    /**
     * تقرير الشيكات
     */
    public function chequesReport(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending,cleared,bounced',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $query = DB::table('cheques')
            ->join('customers', 'cheques.customer_id', '=', 'customers.id')
            ->select(
                'cheques.*',
                'customers.name as customer_name',
                'customers.code as customer_code'
            );

        if (isset($validated['status'])) {
            $query->where('cheques.status', $validated['status']);
        }

        if (isset($validated['from_date'])) {
            $query->whereDate('cheques.cheque_date', '>=', $validated['from_date']);
        }

        if (isset($validated['to_date'])) {
            $query->whereDate('cheques.cheque_date', '<=', $validated['to_date']);
        }

        $cheques = $query->orderBy('cheques.cheque_date', 'desc')->get();

        return response()->json([
            'data' => $cheques,
            'summary' => [
                'total_cheques' => $cheques->count(),
                'total_amount' => round($cheques->sum('amount'), 2),
                'pending_count' => $cheques->where('status', 'pending')->count(),
                'pending_amount' => round($cheques->where('status', 'pending')->sum('amount'), 2),
                'cleared_count' => $cheques->where('status', 'cleared')->count(),
                'cleared_amount' => round($cheques->where('status', 'cleared')->sum('amount'), 2),
                'bounced_count' => $cheques->where('status', 'bounced')->count(),
                'bounced_amount' => round($cheques->where('status', 'bounced')->sum('amount'), 2),
            ],
        ]);
    }
}
