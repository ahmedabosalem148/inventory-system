<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\Cheque;
use App\Models\InventoryMovement;
use App\Models\IssueVoucher;
use App\Models\ReturnVoucher;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. الإحصائيات الأساسية
        $stats = [
            'branches_count' => Branch::active()->count(),
            'categories_count' => Category::active()->count(),
            'products_count' => Product::active()->count(),
            'total_stock_value' => ProductBranchStock::with('product')
                ->get()
                ->sum(fn($stock) => $stock->current_stock * $stock->product->purchase_price),
        ];

        // 2. أصناف أقل من الحد الأدنى (مرتبة بالنقص الأكبر)
        $lowStockItems = ProductBranchStock::with(['product.category', 'branch'])
            ->whereHas('product', fn($q) => $q->where('is_active', true))
            ->get()
            ->filter(function($stock) {
                return $stock->current_stock < $stock->product->min_stock;
            })
            ->sortBy(function($stock) {
                return ($stock->current_stock / max($stock->product->min_stock, 1));
            })
            ->take(10);

        // 3. أصناف نفذت من المخزون (0)
        $outOfStock = ProductBranchStock::with(['product', 'branch'])
            ->whereHas('product', fn($q) => $q->where('is_active', true))
            ->where('current_stock', 0)
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        // 4. شيكات مستحقة قريبًا (خلال 7 أيام)
        $upcomingCheques = Cheque::with(['customer', 'creator'])
            ->pending()
            ->dueSoon(7)
            ->orderBy('due_date', 'asc')
            ->take(10)
            ->get();

        // 5. شيكات متأخرة (overdue)
        $overdueCheques = Cheque::with(['customer', 'creator'])
            ->overdue()
            ->orderBy('due_date', 'asc')
            ->take(10)
            ->get();

        // 6. أكثر 10 أصناف حركةً (الشهر الحالي)
        $mostActiveProducts = InventoryMovement::with(['product.category', 'branch'])
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->selectRaw('product_id, branch_id, SUM(ABS(qty_units)) as total_movement')
            ->groupBy('product_id', 'branch_id')
            ->orderByDesc('total_movement')
            ->take(10)
            ->get();

        // 7. آخر 10 أذون صرف معتمدة
        $recentVouchers = collect()
            ->merge(
                IssueVoucher::with(['customer', 'branch', 'creator'])
                    ->where('status', 'APPROVED')
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(fn($v) => ['type' => 'issue', 'voucher' => $v])
            )
            ->merge(
                ReturnVoucher::with(['customer', 'branch', 'creator'])
                    ->where('status', 'APPROVED')
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(fn($v) => ['type' => 'return', 'voucher' => $v])
            )
            ->sortByDesc(fn($item) => $item['voucher']->created_at)
            ->take(10);

        return view('dashboard', compact(
            'stats',
            'lowStockItems',
            'outOfStock',
            'upcomingCheques',
            'overdueCheques',
            'mostActiveProducts',
            'recentVouchers'
        ));
    }
}
