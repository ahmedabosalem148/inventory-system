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
     * Product Movement Report - PDF Export
     */
    public function productMovementPDF(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'nullable|exists:branches,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'movement_type' => 'nullable|in:issue,return,transfer_out,transfer_in',
        ]);

        // استخدام نفس منطق productMovement
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
        $product = Product::find($validated['product_id']);

        // TODO: Implement PDF generation
        return response()->json(['message' => 'PDF export will be implemented soon']);
    }

    /**
     * Product Movement Report - Excel Export
     */
    public function productMovementExcel(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'nullable|exists:branches,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'movement_type' => 'nullable|in:issue,return,transfer_out,transfer_in',
        ]);

        // استخدام نفس منطق productMovement
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
        
        // TODO: Implement Excel export
        return response()->json(['message' => 'Excel export will be implemented soon']);
    }

    /**
     * Customer Balances Report
     */
    public function customerBalances(Request $request)
    {
        $validated = $request->validate([
            'customer_type' => 'nullable|in:retail,wholesale',
            'status' => 'nullable|in:active,inactive',
            'search' => 'nullable|string',
        ]);

        $query = Customer::query();

        if (isset($validated['customer_type'])) {
            $query->where('type', $validated['customer_type']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->with('ledgerEntries')->get();

        $data = $customers->map(function ($customer) {
            $balance = $customer->ledgerEntries->sum(function ($entry) {
                return $entry->debit - $entry->credit;
            });

            return [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'type' => $customer->type,
                'phone' => $customer->phone,
                'balance' => round($balance, 2),
                'status' => $balance > 0 ? 'له' : ($balance < 0 ? 'عليه' : 'متزن'),
            ];
        });

        // ترتيب حسب الرصيد (الأكبر أولاً)
        $sorted = $data->sortByDesc(function ($customer) {
            return abs($customer['balance']);
        })->values();

        return response()->json([
            'data' => $sorted,
            'summary' => [
                'total_customers' => $customers->count(),
                'total_debit' => round($sorted->where('balance', '>', 0)->sum('balance'), 2),
                'total_credit' => round(abs($sorted->where('balance', '<', 0)->sum('balance')), 2),
            ],
        ]);
    }

    /**
     * Customer Balances Report - PDF Export
     */
    public function customerBalancesPDF(Request $request)
    {
        // استخدام نفس منطق customerBalances
        // TODO: Implement PDF generation
        return response()->json(['message' => 'PDF export will be implemented soon']);
    }

    /**
     * Customer Balances Report - Excel Export
     */
    public function customerBalancesExcel(Request $request)
    {
        // استخدام نفس منطق customerBalances
        // TODO: Implement Excel export
        return response()->json(['message' => 'Excel export will be implemented soon']);
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
     * تصدير تقرير المبيعات PDF
     */
    public function salesReportPDF(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        // TODO: Implement PDF export
        return response()->json([
            'message' => 'PDF export will be implemented soon',
            'data' => $validated
        ]);
    }

    /**
     * تصدير تقرير المبيعات Excel
     */
    public function salesReportExcel(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        // TODO: Implement Excel export
        return response()->json([
            'message' => 'Excel export will be implemented soon',
            'data' => $validated
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

    /**
     * تقرير تقييم المخزون (Stock Valuation Report)
     * 
     * GET /api/v1/reports/stock-valuation
     */
    public function stockValuation(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:categories,id',
            'product_classification' => 'nullable|string|in:finished_product,semi_finished,raw_material,parts,plastic_parts,aluminum_parts,other',
        ]);

        $query = Product::with(['category', 'branchStocks.branch'])
            ->select('products.*');

        // Filter by category
        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        // Filter by product classification
        if (isset($validated['product_classification'])) {
            $query->where('product_classification', $validated['product_classification']);
        }

        $products = $query->get();

        $data = [];
        $totalValue = 0;
        $totalQuantity = 0;
        $totalProducts = 0;

        foreach ($products as $product) {
            // Filter by branch if specified
            $branches = isset($validated['branch_id'])
                ? $product->branchStocks->where('branch_id', $validated['branch_id'])
                : $product->branchStocks;

            foreach ($branches as $branchStock) {
                $quantity = $branchStock->current_stock ?? 0;
                $cost = $product->purchase_price ?? 0;
                $value = $quantity * $cost;

                $data[] = [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'غير محدد',
                    'branch' => $branchStock->branch->name ?? 'غير محدد',
                    'branch_id' => $branchStock->branch_id,
                    'quantity' => (float) $quantity,
                    'unit' => $product->unit ?? 'قطعة',
                    'cost' => (float) $cost,
                    'total_value' => (float) $value,
                ];

                $totalQuantity += $quantity;
                $totalValue += $value;
                $totalProducts++;
            }
        }

        return response()->json([
            'data' => $data,
            'summary' => [
                'total_products' => $totalProducts,
                'total_quantity' => (float) $totalQuantity,
                'total_value' => (float) $totalValue,
                'average_value' => $totalProducts > 0 ? (float) ($totalValue / $totalProducts) : 0,
            ],
        ]);
    }

    /**
     * تصدير تقرير تقييم المخزون PDF
     * 
     * GET /api/v1/reports/stock-valuation/pdf
     */
    public function stockValuationPDF(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        // Get data (reuse logic)
        $reportData = $this->stockValuation($request)->getData();

        // TODO: Use DomPDF or similar for professional PDF
        // For now, simple text format
        $content = "تقرير تقييم المخزون\n";
        $content .= str_repeat("=", 80) . "\n\n";
        $content .= sprintf("إجمالي المنتجات: %d\n", $reportData->summary->total_products);
        $content .= sprintf("إجمالي الكمية: %.2f\n", $reportData->summary->total_quantity);
        $content .= sprintf("إجمالي القيمة: %.2f ج.م\n\n", $reportData->summary->total_value);
        $content .= str_repeat("-", 80) . "\n";
        
        foreach ($reportData->data as $item) {
            $content .= sprintf(
                "%s | %s | %s | الكمية: %.2f | التكلفة: %.2f | القيمة: %.2f ج.م\n",
                $item->sku,
                $item->name,
                $item->category,
                $item->quantity,
                $item->cost,
                $item->total_value
            );
        }

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="stock-valuation-report.pdf"');
    }

    /**
     * تصدير تقرير تقييم المخزون Excel
     * 
     * GET /api/v1/reports/stock-valuation/excel
     */
    public function stockValuationExcel(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        // Get data (reuse logic)
        $reportData = $this->stockValuation($request)->getData();

        // TODO: Use Laravel Excel for professional export
        // For now, CSV format
        $csv = "الرمز,اسم المنتج,الفئة,الفرع,الكمية,الوحدة,التكلفة,القيمة الإجمالية\n";
        
        foreach ($reportData->data as $item) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%.2f","%s","%.2f","%.2f"' . "\n",
                $item->sku,
                $item->name,
                $item->category,
                $item->branch,
                $item->quantity,
                $item->unit,
                $item->cost,
                $item->total_value
            );
        }

        return response($csv)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="stock-valuation-report.xlsx"');
    }

    /**
     * تقرير إجمالي المخزون
     * Stock Summary Report - All products with branch breakdown
     * 
     * GET /api/v1/reports/stock-summary
     */
    public function stockSummary(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:categories,id',
            'search' => 'nullable|string|max:255',
        ]);

        $query = Product::with(['category', 'branchStocks.branch'])
            ->select('products.*');

        // Filter by category
        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        // Search by product name or SKU
        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->get();

        $data = [];
        $totalQuantityAll = 0;
        $totalProducts = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach ($products as $product) {
            // Filter branches if specified
            $branches = isset($validated['branch_id'])
                ? $product->branchStocks->where('branch_id', $validated['branch_id'])
                : $product->branchStocks;

            if ($branches->isEmpty()) {
                continue; // Skip products with no stock in selected branch
            }

            $branchesData = [];
            $totalQuantityProduct = 0;

            foreach ($branches as $branchStock) {
                $quantity = $branchStock->quantity ?? 0;
                $minStock = $product->min_stock ?? 0;
                
                // Determine status
                $status = 'normal';
                if ($quantity == 0) {
                    $status = 'out_of_stock';
                } elseif ($minStock > 0 && $quantity < ($minStock * 0.5)) {
                    $status = 'critical';
                } elseif ($minStock > 0 && $quantity <= $minStock) {
                    $status = 'low';
                }

                $branchesData[] = [
                    'branch_id' => $branchStock->branch_id,
                    'branch_name' => $branchStock->branch->name ?? 'غير محدد',
                    'quantity' => (float) $quantity,
                    'min_stock' => (float) $minStock,
                    'status' => $status,
                ];

                $totalQuantityProduct += $quantity;
            }

            // Check if product has low stock in any branch
            $hasLowStock = collect($branchesData)->contains(function($branch) {
                return in_array($branch['status'], ['low', 'critical', 'out_of_stock']);
            });

            if ($hasLowStock) {
                $lowStockCount++;
            }

            $hasOutOfStock = collect($branchesData)->contains(function($branch) {
                return $branch['status'] === 'out_of_stock';
            });

            if ($hasOutOfStock) {
                $outOfStockCount++;
            }

            $data[] = [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category->name ?? 'غير محدد',
                'unit' => $product->unit ?? 'قطعة',
                'branches' => $branchesData,
                'total_quantity' => (float) $totalQuantityProduct,
                'total_branches' => count($branchesData),
                'has_low_stock' => $hasLowStock,
            ];

            $totalQuantityAll += $totalQuantityProduct;
            $totalProducts++;
        }

        return response()->json([
            'data' => $data,
            'summary' => [
                'total_products' => $totalProducts,
                'total_quantity' => (float) $totalQuantityAll,
                'low_stock_items' => $lowStockCount,
                'out_of_stock_items' => $outOfStockCount,
            ],
        ]);
    }

    /**
     * تصدير تقرير إجمالي المخزون PDF
     */
    public function stockSummaryPDF(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:categories,id',
            'search' => 'nullable|string|max:255',
        ]);

        // Get the same data
        $reportData = $this->stockSummary($request)->getData();
        $data = $reportData->data;
        $summary = $reportData->summary;

        // Generate simple text-based PDF content
        $content = "تقرير إجمالي المخزون\n";
        $content .= "=================\n\n";
        $content .= "التاريخ: " . now()->format('Y-m-d H:i') . "\n\n";
        $content .= "الملخص:\n";
        $content .= "عدد المنتجات: " . $summary->total_products . "\n";
        $content .= "إجمالي الكمية: " . $summary->total_quantity . "\n";
        $content .= "منتجات منخفضة المخزون: " . $summary->low_stock_items . "\n";
        $content .= "منتجات نفذت: " . $summary->out_of_stock_items . "\n\n";
        $content .= "التفاصيل:\n";
        $content .= str_repeat("-", 80) . "\n";

        foreach ($data as $item) {
            $content .= "المنتج: {$item->name} ({$item->sku})\n";
            $content .= "الفئة: {$item->category} | الوحدة: {$item->unit}\n";
            $content .= "إجمالي الكمية: {$item->total_quantity}\n";
            $content .= "الفروع:\n";
            foreach ($item->branches as $branch) {
                $content .= "  - {$branch->branch_name}: {$branch->quantity} ({$branch->status})\n";
            }
            $content .= str_repeat("-", 80) . "\n";
        }

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="stock-summary-report.pdf"');
    }

    /**
     * تصدير تقرير إجمالي المخزون Excel
     */
    public function stockSummaryExcel(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:categories,id',
            'search' => 'nullable|string|max:255',
        ]);

        // Get the same data
        $reportData = $this->stockSummary($request)->getData();
        $data = $reportData->data;

        // Generate CSV content
        $csv = "SKU,Product Name,Category,Unit,Branch,Quantity,Min Stock,Status,Total Quantity\n";
        
        foreach ($data as $item) {
            foreach ($item->branches as $branch) {
                $csv .= sprintf(
                    "%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                    $item->sku,
                    $item->name,
                    $item->category,
                    $item->unit,
                    $branch->branch_name,
                    $branch->quantity,
                    $branch->min_stock,
                    $branch->status,
                    $item->total_quantity
                );
            }
        }

        return response($csv)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="stock-summary-report.xlsx"');
    }

    /**
     * تقرير المخزون المنخفض
     * Low Stock Report with filtering and severity levels
     * 
     * GET /api/v1/reports/low-stock
     */
    public function lowStock(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|in:all,out_of_stock,critical,low',
            'search' => 'nullable|string|max:255',
        ]);

        $query = ProductBranchStock::with(['product.category', 'branch'])
            ->whereHas('product', function($q) {
                $q->where('is_active', true);
            });

        // Filter by branch
        if (isset($validated['branch_id'])) {
            $query->where('branch_id', $validated['branch_id']);
        }

        // Filter by category
        if (isset($validated['category_id'])) {
            $query->whereHas('product', function($q) use ($validated) {
                $q->where('category_id', $validated['category_id']);
            });
        }

        // Search by product name or SKU
        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $stocks = $query->get();

        $data = [];
        $outOfStockCount = 0;
        $criticalCount = 0;
        $lowCount = 0;

        foreach ($stocks as $stock) {
              $quantity = $stock->current_stock ?? 0;
              $minStock = $stock->min_qty ?? 0;

            // Skip if min_stock is not set
            if ($minStock <= 0) {
                continue;
            }

            // Determine status
            $status = 'normal';
            if ($quantity == 0) {
                $status = 'out_of_stock';
                $outOfStockCount++;
            } elseif ($quantity < ($minStock * 0.5)) {
                $status = 'critical';
                $criticalCount++;
            } elseif ($quantity <= $minStock) {
                $status = 'low';
                $lowCount++;
            } else {
                continue; // Skip normal stock items
            }

            // Apply status filter
            if (isset($validated['status']) && $validated['status'] !== 'all') {
                if ($status !== $validated['status']) {
                    continue;
                }
            }

            $deficit = max(0, $minStock - $quantity);
            $percentage = $minStock > 0 ? round(($quantity / $minStock) * 100, 1) : 0;

            $data[] = [
                'product_id' => $stock->product_id,
                'sku' => $stock->product->sku,
                'name' => $stock->product->name,
                'category' => $stock->product->category->name ?? 'غير محدد',
                'unit' => $stock->product->unit ?? 'قطعة',
                'branch_id' => $stock->branch_id,
                'branch_name' => $stock->branch->name,
                'quantity' => (float) $quantity,
                'min_stock' => (float) $minStock,
                'deficit' => (float) $deficit,
                'percentage' => (float) $percentage,
                'status' => $status,
                'reorder_suggestion' => (float) ($minStock * 2), // Suggest reorder to 2x min stock
            ];
        }

        // Sort by severity: out_of_stock > critical > low, then by percentage ascending
        usort($data, function($a, $b) {
            $statusOrder = ['out_of_stock' => 1, 'critical' => 2, 'low' => 3];
            $statusCompare = ($statusOrder[$a['status']] ?? 99) <=> ($statusOrder[$b['status']] ?? 99);
            if ($statusCompare !== 0) {
                return $statusCompare;
            }
            return $a['percentage'] <=> $b['percentage'];
        });

        return response()->json([
            'data' => $data,
            'summary' => [
                'total_items' => count($data),
                'out_of_stock' => $outOfStockCount,
                'critical' => $criticalCount,
                'low' => $lowCount,
            ],
        ]);
    }

    /**
     * تصدير تقرير المخزون المنخفض PDF
     */
    public function lowStockPDF(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|in:all,out_of_stock,critical,low',
            'search' => 'nullable|string|max:255',
        ]);

        // Get data from main method
        $reportData = $this->lowStock($request)->getData(true);

        $content = "تقرير المخزون المنخفض\n";
        $content .= "===================\n\n";
        $content .= "التاريخ: " . now()->format('Y-m-d H:i') . "\n\n";
        
        $content .= "الإحصائيات:\n";
        $content .= "إجمالي الأصناف: " . $reportData['summary']['total_items'] . "\n";
        $content .= "نفذ من المخزون: " . $reportData['summary']['out_of_stock'] . "\n";
        $content .= "حرج: " . $reportData['summary']['critical'] . "\n";
        $content .= "منخفض: " . $reportData['summary']['low'] . "\n\n";

        $content .= "التفاصيل:\n";
        foreach ($reportData['data'] as $item) {
            $content .= "\n";
            $content .= "المنتج: {$item['name']} ({$item['sku']})\n";
            $content .= "الفرع: {$item['branch_name']}\n";
            $content .= "الكمية الحالية: {$item['quantity']}\n";
            $content .= "الحد الأدنى: {$item['min_stock']}\n";
            $content .= "النقص: {$item['deficit']}\n";
            $content .= "النسبة: {$item['percentage']}%\n";
            $content .= "الحالة: {$item['status']}\n";
            $content .= "كمية الطلب المقترحة: {$item['reorder_suggestion']}\n";
            $content .= "---\n";
        }

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="low-stock-report.pdf"');
    }

    /**
     * تصدير تقرير المخزون المنخفض Excel
     */
    public function lowStockExcel(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|in:all,out_of_stock,critical,low',
            'search' => 'nullable|string|max:255',
        ]);

        // Get data from main method
        $reportData = $this->lowStock($request)->getData(true);

        $csv = "الرمز,اسم المنتج,الفئة,الوحدة,الفرع,الكمية الحالية,الحد الأدنى,النقص,النسبة %,الحالة,كمية الطلب المقترحة\n";

        foreach ($reportData['data'] as $item) {
            $csv .= "{$item['sku']},{$item['name']},{$item['category']},{$item['unit']},{$item['branch_name']},{$item['quantity']},{$item['min_stock']},{$item['deficit']},{$item['percentage']},{$item['status']},{$item['reorder_suggestion']}\n";
        }

        return response($csv)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="low-stock-report.xlsx"');
    }

    /**
     * تقرير أعمار الذمم (Customer Aging Report)
     */
    public function customerAging(Request $request)
    {
        $validated = $request->validate([
            'as_of_date' => 'nullable|date',
        ]);

        $asOfDate = $validated['as_of_date'] ?? now()->format('Y-m-d');

        // Get all customers with balances
        $customers = Customer::where('is_active', true)
            ->orderBy('name')
            ->get();

        $agingData = [];
        $totalBalance = 0;
        $agingTotals = [
            '0-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '91-120' => 0,
            '120+' => 0,
            'total' => 0,
        ];

        foreach ($customers as $customer) {
            $balance = $this->ledgerService->getBalance($customer->id, $asOfDate);
            
            if ($balance <= 0) {
                continue; // Skip customers with no balance
            }

            // Calculate aging buckets
            $aging = $this->calculateAging($customer->id, $asOfDate);
            
            $agingData[] = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_code' => $customer->code,
                'phone' => $customer->phone,
                'aging' => $aging,
            ];

            $totalBalance += $aging['total'];
            $agingTotals['0-30'] += $aging['0-30'];
            $agingTotals['31-60'] += $aging['31-60'];
            $agingTotals['61-90'] += $aging['61-90'];
            $agingTotals['91-120'] += $aging['91-120'];
            $agingTotals['120+'] += $aging['120+'];
        }

        $agingTotals['total'] = $totalBalance;

        return response()->json([
            'success' => true,
            'data' => [
                'customers' => $agingData,
                'summary' => [
                    'total_customers' => count($agingData),
                    'total_balance' => $totalBalance,
                    'aging_totals' => $agingTotals,
                ],
                'as_of_date' => $asOfDate,
            ],
        ]);
    }

    /**
     * حساب أعمار الذمم لعميل معين
     */
    private function calculateAging($customerId, $asOfDate)
    {
        $aging = [
            '0-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '91-120' => 0,
            '120+' => 0,
            'total' => 0,
        ];

        // Get all unpaid vouchers for the customer
        $vouchers = IssueVoucher::where('customer_id', $customerId)
            ->where('date', '<=', $asOfDate)
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($vouchers as $voucher) {
            // Get unpaid balance for this voucher
            $voucherBalance = $voucher->total - $voucher->paid;
            
            if ($voucherBalance <= 0) {
                continue;
            }

            // Calculate days since voucher date
            $voucherDate = \Carbon\Carbon::parse($voucher->date);
            $asOfDateCarbon = \Carbon\Carbon::parse($asOfDate);
            $daysDiff = $voucherDate->diffInDays($asOfDateCarbon);

            // Classify into aging buckets
            if ($daysDiff <= 30) {
                $aging['0-30'] += $voucherBalance;
            } elseif ($daysDiff <= 60) {
                $aging['31-60'] += $voucherBalance;
            } elseif ($daysDiff <= 90) {
                $aging['61-90'] += $voucherBalance;
            } elseif ($daysDiff <= 120) {
                $aging['91-120'] += $voucherBalance;
            } else {
                $aging['120+'] += $voucherBalance;
            }

            $aging['total'] += $voucherBalance;
        }

        return $aging;
    }

    /**
     * تصدير تقرير أعمار الذمم إلى Excel أو PDF
     */
    public function customerAgingExport(Request $request)
    {
        $validated = $request->validate([
            'as_of_date' => 'nullable|date',
            'format' => 'required|in:excel,pdf',
        ]);

        $asOfDate = $validated['as_of_date'] ?? now()->format('Y-m-d');
        $format = $validated['format'];

        // Get aging data
        $response = $this->customerAging($request);
        $data = $response->getData()->data;

        if ($format === 'excel') {
            return $this->customerAgingExcel($data, $asOfDate);
        } else {
            return $this->customerAgingPDF($data, $asOfDate);
        }
    }

    /**
     * تصدير تقرير أعمار الذمم إلى Excel
     */
    private function customerAgingExcel($data, $asOfDate)
    {
        $csv = "كود العميل,اسم العميل,الهاتف,0-30 يوم,31-60 يوم,61-90 يوم,91-120 يوم,120+ يوم,الإجمالي\n";

        foreach ($data->customers as $customer) {
            $csv .= "\"{$customer['customer_code']}\",";
            $csv .= "\"{$customer['customer_name']}\",";
            $csv .= "\"{$customer['phone']}\",";
            $csv .= "{$customer['aging']['0-30']},";
            $csv .= "{$customer['aging']['31-60']},";
            $csv .= "{$customer['aging']['61-90']},";
            $csv .= "{$customer['aging']['91-120']},";
            $csv .= "{$customer['aging']['120+']},";
            $csv .= "{$customer['aging']['total']}\n";
        }

        // Add summary
        $csv .= "\n";
        $csv .= "الإجمالي,,";
        $csv .= ",{$data->summary->aging_totals['0-30']}";
        $csv .= ",{$data->summary->aging_totals['31-60']}";
        $csv .= ",{$data->summary->aging_totals['61-90']}";
        $csv .= ",{$data->summary->aging_totals['91-120']}";
        $csv .= ",{$data->summary->aging_totals['120+']}";
        $csv .= ",{$data->summary->aging_totals['total']}\n";

        return response($csv)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"customer-aging-{$asOfDate}.xlsx\"");
    }

    /**
     * تصدير تقرير أعمار الذمم إلى PDF
     */
    private function customerAgingPDF($data, $asOfDate)
    {
        // For now, return the same as Excel
        // TODO: Implement actual PDF generation with proper formatting
        return $this->customerAgingExcel($data, $asOfDate);
    }
}


