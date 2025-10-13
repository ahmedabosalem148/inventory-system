<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\IssueVoucher;
use App\Models\Product;
use App\Models\ProductBranchStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * إحصائيات الداشبورد الرئيسية
     * 
     * GET /api/v1/dashboard
     * Query params: branch_id (optional, admin can filter by branch, users see their current branch)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = null;

        // Admin can optionally filter by branch, or see all branches
        if ($user->hasRole('super-admin')) {
            $branchId = $request->get('branch_id');
        } else {
            // Regular users see only their current/assigned branch
            $activeBranch = $user->getActiveBranch();
            if (!$activeBranch) {
                return response()->json([
                    'message' => 'لم يتم تعيين فرع للمستخدم'
                ], 403);
            }
            $branchId = $activeBranch->id;
        }

        $stats = [
            // إحصائيات عامة
            'total_products' => Product::active()->count(),
            'total_branches' => Branch::active()->count(),
            'total_customers' => Customer::active()->count(),
            
            // إحصائيات المخزون (filtered by branch if specified)
            'total_stock_value' => $this->calculateTotalStockValue($branchId),
            'low_stock_count' => $this->getLowStockCount($branchId),
            'out_of_stock_count' => $this->getOutOfStockCount($branchId),
            
            // إحصائيات العملاء
            'customers_with_credit' => Customer::withCredit()->count(),
            'customers_with_debit' => Customer::withDebit()->count(),
            'total_receivables' => Customer::where('balance', '>', 0)->sum('balance'),
            'total_payables' => abs(Customer::where('balance', '<', 0)->sum('balance')),
            
            // مبيعات اليوم (filtered by branch if specified)
            'today_sales' => $this->getTodaySales($branchId),
            'today_vouchers_count' => $this->getTodayVouchersCount($branchId),
            
            // معلومات الفرع
            'branch_id' => $branchId,
            'branch_name' => $branchId ? Branch::find($branchId)?->name : 'جميع الفروع',
        ];

        return response()->json([
            'data' => $stats,
            'timestamp' => now()->toISOString(),
        ], 200);
    }

    /**
     * إحصائيات تفصيلية
     * 
     * GET /api/v1/dashboard/stats
     * Query params: period, branch_id (optional for admin)
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = null;

        // Admin can optionally filter by branch, or see all branches
        if ($user->hasRole('super-admin')) {
            $branchId = $request->get('branch_id');
        } else {
            // Regular users see only their current/assigned branch
            $activeBranch = $user->getActiveBranch();
            if (!$activeBranch) {
                return response()->json([
                    'message' => 'لم يتم تعيين فرع للمستخدم'
                ], 403);
            }
            $branchId = $activeBranch->id;
        }

        $period = $request->get('period', 'today'); // today, week, month, year

        $dateFrom = match ($period) {
            'today' => today(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => today(),
        };

        $voucherQuery = IssueVoucher::where('voucher_date', '>=', $dateFrom);
        if ($branchId) {
            $voucherQuery->where('branch_id', $branchId);
        }

        $stats = [
            'sales_summary' => [
                'total_amount' => $voucherQuery->sum('total_amount'),
                'total_vouchers' => $voucherQuery->count(),
                'average_voucher' => $voucherQuery->avg('total_amount'),
            ],
            
            'top_products' => $this->getTopProducts($dateFrom, 5, $branchId),
            'top_customers' => $this->getTopCustomers($dateFrom, 5, $branchId),
            
            'branch_performance' => $this->getBranchPerformance($user, $branchId),
        ];

        return response()->json([
            'data' => $stats,
            'period' => $period,
            'date_from' => $dateFrom->toISOString(),
            'branch_id' => $branchId,
        ], 200);
    }

    /**
     * قائمة المنتجات منخفضة المخزون
     * 
     * GET /api/v1/dashboard/low-stock
     * Query params: limit, branch_id (optional for admin)
     */
    public function lowStock(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = null;

        // Admin can optionally filter by branch, or see all branches
        if ($user->hasRole('super-admin')) {
            $branchId = $request->get('branch_id');
        } else {
            // Regular users see only their current/assigned branch
            $activeBranch = $user->getActiveBranch();
            if (!$activeBranch) {
                return response()->json([
                    'message' => 'لم يتم تعيين فرع للمستخدم'
                ], 403);
            }
            $branchId = $activeBranch->id;
        }

        $limit = min($request->get('limit', 20), 100);

        $query = ProductBranchStock::with(['product.category', 'branch'])
            ->whereHas('product', fn($q) => $q->where('is_active', true));

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $lowStockProducts = $query->get()
            ->filter(function ($stock) {
                return $stock->current_stock < $stock->product->min_stock;
            })
            ->sortBy(function ($stock) {
                // Sort by severity (percentage of min_stock)
                return $stock->current_stock / max($stock->product->min_stock, 1);
            })
            ->take($limit)
            ->map(function ($stock) {
                return [
                    'product_id' => $stock->product_id,
                    'product_name' => $stock->product->name,
                    'category' => $stock->product->category->name ?? null,
                    'branch_id' => $stock->branch_id,
                    'branch_name' => $stock->branch->name,
                    'current_stock' => $stock->current_stock,
                    'min_stock' => $stock->product->min_stock,
                    'deficit' => $stock->product->min_stock - $stock->current_stock,
                    'percentage' => round(($stock->current_stock / max($stock->product->min_stock, 1)) * 100, 1),
                    'severity' => $this->getStockSeverity($stock->current_stock, $stock->product->min_stock),
                ];
            })
            ->values();

        return response()->json([
            'data' => $lowStockProducts,
            'total_count' => $lowStockProducts->count(),
            'branch_id' => $branchId,
        ], 200);
    }

    // ========================================================================
    // Private Helper Methods
    // ========================================================================

    private function calculateTotalStockValue(?int $branchId = null): float
    {
        $query = ProductBranchStock::with('product');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->get()
            ->sum(function ($stock) {
                return $stock->current_stock * ($stock->product->purchase_price ?? 0);
            });
    }

    private function getLowStockCount(?int $branchId = null): int
    {
        $query = ProductBranchStock::with('product');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->get()
            ->filter(function ($stock) {
                return $stock->current_stock < $stock->product->min_stock 
                    && $stock->current_stock > 0;
            })
            ->count();
    }

    private function getOutOfStockCount(?int $branchId = null): int
    {
        $query = ProductBranchStock::where('current_stock', 0);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->count();
    }

    private function getTodaySales(?int $branchId = null): float
    {
        $query = IssueVoucher::whereDate('voucher_date', today());
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->sum('total_amount');
    }

    private function getTodayVouchersCount(?int $branchId = null): int
    {
        $query = IssueVoucher::whereDate('voucher_date', today());
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        return $query->count();
    }

    private function getTopProducts($dateFrom, int $limit, ?int $branchId = null): array
    {
        $query = DB::table('issue_voucher_items')
            ->join('issue_vouchers', 'issue_voucher_items.issue_voucher_id', '=', 'issue_vouchers.id')
            ->join('products', 'issue_voucher_items.product_id', '=', 'products.id')
            ->where('issue_vouchers.voucher_date', '>=', $dateFrom);

        if ($branchId) {
            $query->where('issue_vouchers.branch_id', $branchId);
        }

        return $query->select(
                'products.id',
                'products.name',
                DB::raw('SUM(issue_voucher_items.quantity) as total_quantity'),
                DB::raw('SUM(issue_voucher_items.quantity * issue_voucher_items.unit_price) as total_amount')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'total_quantity' => (int) $item->total_quantity,
                'total_amount' => (float) $item->total_amount,
            ])
            ->toArray();
    }

    private function getTopCustomers($dateFrom, int $limit, ?int $branchId = null): array
    {
        $query = DB::table('issue_vouchers')
            ->join('customers', 'issue_vouchers.customer_id', '=', 'customers.id')
            ->where('issue_vouchers.voucher_date', '>=', $dateFrom);

        if ($branchId) {
            $query->where('issue_vouchers.branch_id', $branchId);
        }

        return $query->select(
                'customers.id',
                'customers.name',
                DB::raw('COUNT(issue_vouchers.id) as total_vouchers'),
                DB::raw('SUM(issue_vouchers.total_amount) as total_amount')
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'total_vouchers' => (int) $item->total_vouchers,
                'total_amount' => (float) $item->total_amount,
            ])
            ->toArray();
    }

    private function getBranchPerformance($user, ?int $branchId = null)
    {
        // Admin can see all branches or filter by specific branch
        if ($user->hasRole('super-admin')) {
            $query = Branch::active();
            if ($branchId) {
                $query->where('id', $branchId);
            }
        } else {
            // Regular users see only their current branch
            $query = Branch::active()->where('id', $branchId);
        }

        return $query->withCount(['productStocks as total_products'])
            ->get()
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'total_products' => $branch->total_products,
                    'stock_value' => $this->getBranchStockValue($branch->id),
                ];
            });
    }

    private function getBranchStockValue(int $branchId): float
    {
        return ProductBranchStock::with('product')
            ->where('branch_id', $branchId)
            ->get()
            ->sum(function ($stock) {
                return $stock->current_stock * ($stock->product->purchase_price ?? 0);
            });
    }

    private function getStockSeverity(int $current, int $min): string
    {
        if ($current == 0) return 'critical';
        
        $percentage = ($current / max($min, 1)) * 100;
        
        return match (true) {
            $percentage < 20 => 'critical',
            $percentage < 50 => 'warning',
            $percentage < 100 => 'low',
            default => 'normal',
        };
    }
}
