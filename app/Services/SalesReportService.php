<?php

namespace App\Services;

use App\Models\IssueVoucher;
use App\Models\IssueVoucherItem;
use App\Models\ReturnVoucher;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesReportService
{
    /**
     * تقرير المبيعات حسب الفترة
     * Sales Report by Period
     * 
     * @param array $filters
     * @return array
     */
    public function getSalesByPeriod(array $filters = []): array
    {
        $query = IssueVoucher::with(['customer', 'branch', 'items.product'])
            ->where('status', 'approved')
            ->where('is_transfer', false); // Exclude transfers

        // Date range filter
        if (isset($filters['from_date'])) {
            $query->where('date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('date', '<=', $filters['to_date']);
        }

        // Branch filter
        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // Customer filter
        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Voucher type filter
        if (isset($filters['voucher_type'])) {
            $query->where('voucher_type', $filters['voucher_type']);
        }

        $vouchers = $query->orderBy('date', 'desc')->get();

        // Calculate summary
        $summary = [
            'total_vouchers' => $vouchers->count(),
            'total_before_discount' => 0,
            'total_discount' => 0,
            'net_total' => 0,
            'by_type' => [
                'cash' => ['count' => 0, 'total' => 0],
                'credit' => ['count' => 0, 'total' => 0],
            ],
            'by_branch' => [],
            'by_customer' => [],
        ];

        $reportData = [];

        foreach ($vouchers as $voucher) {
            $reportData[] = [
                'voucher_id' => $voucher->id,
                'voucher_number' => $voucher->voucher_number,
                'date' => $voucher->date ? $voucher->date->toDateString() : null,
                'customer_name' => $voucher->customer->name,
                'customer_code' => $voucher->customer->code,
                'branch_name' => $voucher->branch->name,
                'voucher_type' => $voucher->voucher_type,
                'voucher_type_arabic' => $voucher->voucher_type === 'cash' ? 'نقدي' : 'آجل',
                'total_before_discount' => $voucher->subtotal ?? 0,
                'total_discount' => $voucher->discount_amount ?? 0,
                'net_total' => $voucher->net_total ?? 0,
                'items_count' => $voucher->items->count(),
            ];

            // Update summary
            $summary['total_before_discount'] += $voucher->subtotal ?? 0;
            $summary['total_discount'] += $voucher->discount_amount ?? 0;
            $summary['net_total'] += $voucher->net_total ?? 0;

            $voucherType = $voucher->voucher_type ?: 'cash'; // Default to cash if empty
            if (!isset($summary['by_type'][$voucherType])) {
                $summary['by_type'][$voucherType] = ['count' => 0, 'total' => 0];
            }
            $summary['by_type'][$voucherType]['count']++;
            $summary['by_type'][$voucherType]['total'] += $voucher->net_total ?? 0;

            // By branch
            $branchId = $voucher->branch_id;
            if (!isset($summary['by_branch'][$branchId])) {
                $summary['by_branch'][$branchId] = [
                    'branch_name' => $voucher->branch->name,
                    'count' => 0,
                    'total' => 0,
                ];
            }
            $summary['by_branch'][$branchId]['count']++;
            $summary['by_branch'][$branchId]['total'] += $voucher->net_total;

            // By customer
            $customerId = $voucher->customer_id;
            if (!isset($summary['by_customer'][$customerId])) {
                $summary['by_customer'][$customerId] = [
                    'customer_name' => $voucher->customer->name,
                    'customer_code' => $voucher->customer->code,
                    'count' => 0,
                    'total' => 0,
                ];
            }
            $summary['by_customer'][$customerId]['count']++;
            $summary['by_customer'][$customerId]['total'] += $voucher->net_total;
        }

        // Convert associative arrays to indexed arrays for JSON
        $summary['by_branch'] = array_values($summary['by_branch']);
        $summary['by_customer'] = array_values($summary['by_customer']);

        // Sort by total (highest first)
        usort($summary['by_branch'], fn($a, $b) => $b['total'] <=> $a['total']);
        usort($summary['by_customer'], fn($a, $b) => $b['total'] <=> $a['total']);

        return [
            'vouchers' => $reportData,
            'summary' => $summary,
            'generated_at' => now()->toDateTimeString(),
            'filters' => $filters,
        ];
    }

    /**
     * تقرير المبيعات حسب المنتج
     * Sales Report by Product
     * 
     * @param array $filters
     * @return array
     */
    public function getSalesByProduct(array $filters = []): array
    {
        $query = IssueVoucherItem::with(['product.category', 'voucher'])
            ->whereHas('voucher', function ($q) use ($filters) {
                $q->where('status', 'approved')
                  ->where('is_transfer', false);

                if (isset($filters['from_date'])) {
                    $q->where('date', '>=', $filters['from_date']);
                }

                if (isset($filters['to_date'])) {
                    $q->where('date', '<=', $filters['to_date']);
                }

                if (isset($filters['branch_id'])) {
                    $q->where('branch_id', $filters['branch_id']);
                }
            });

        // Category filter
        if (isset($filters['category_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        $items = $query->get();

        // Group by product
        $productSales = [];

        foreach ($items as $item) {
            $productId = $item->product_id;

            if (!isset($productSales[$productId])) {
                $productSales[$productId] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_code' => $item->product->code,
                    'category' => $item->product->category->name ?? 'غير مصنف',
                    'unit' => $item->product->unit,
                    'quantity_sold' => 0,
                    'total_revenue' => 0,
                    'total_discount' => 0,
                    'net_revenue' => 0,
                    'sales_count' => 0,
                ];
            }

            $productSales[$productId]['quantity_sold'] += $item->quantity;
            $productSales[$productId]['total_revenue'] += $item->total_price;
            $productSales[$productId]['total_discount'] += $item->discount_amount;
            $productSales[$productId]['net_revenue'] += $item->net_total;
            $productSales[$productId]['sales_count']++;
        }

        // Convert to indexed array and sort by revenue
        $reportData = array_values($productSales);
        usort($reportData, fn($a, $b) => $b['net_revenue'] <=> $a['net_revenue']);

        // Calculate summary
        $summary = [
            'total_products' => count($reportData),
            'total_quantity_sold' => array_sum(array_column($reportData, 'quantity_sold')),
            'total_revenue' => array_sum(array_column($reportData, 'total_revenue')),
            'total_discount' => array_sum(array_column($reportData, 'total_discount')),
            'net_revenue' => array_sum(array_column($reportData, 'net_revenue')),
        ];

        return [
            'products' => $reportData,
            'summary' => $summary,
            'generated_at' => now()->toDateTimeString(),
            'filters' => $filters,
        ];
    }

    /**
     * تقرير المبيعات حسب الفئة
     * Sales Report by Category
     * 
     * @param array $filters
     * @return array
     */
    public function getSalesByCategory(array $filters = []): array
    {
        $query = IssueVoucherItem::with(['product.category', 'voucher'])
            ->whereHas('voucher', function ($q) use ($filters) {
                $q->where('status', 'approved')
                  ->where('is_transfer', false);

                if (isset($filters['from_date'])) {
                    $q->where('date', '>=', $filters['from_date']);
                }

                if (isset($filters['to_date'])) {
                    $q->where('date', '<=', $filters['to_date']);
                }

                if (isset($filters['branch_id'])) {
                    $q->where('branch_id', $filters['branch_id']);
                }
            });

        $items = $query->get();

        // Group by category
        $categorySales = [];

        foreach ($items as $item) {
            $categoryId = $item->product->category_id ?? 0;
            $categoryName = $item->product->category->name ?? 'غير مصنف';

            if (!isset($categorySales[$categoryId])) {
                $categorySales[$categoryId] = [
                    'category_id' => $categoryId,
                    'category_name' => $categoryName,
                    'total_quantity' => 0,
                    'total_revenue' => 0,
                    'total_discount' => 0,
                    'net_revenue' => 0,
                    'products_count' => 0,
                    'sales_count' => 0,
                ];
            }

            $categorySales[$categoryId]['total_quantity'] += $item->quantity;
            $categorySales[$categoryId]['total_revenue'] += $item->total_price;
            $categorySales[$categoryId]['total_discount'] += $item->discount_amount;
            $categorySales[$categoryId]['net_revenue'] += $item->net_total;
            $categorySales[$categoryId]['sales_count']++;
        }

        // Count unique products per category
        foreach ($categorySales as $categoryId => &$data) {
            $data['products_count'] = $items
                ->where('product.category_id', $categoryId == 0 ? null : $categoryId)
                ->unique('product_id')
                ->count();
        }

        // Convert to indexed array and sort by revenue
        $reportData = array_values($categorySales);
        usort($reportData, fn($a, $b) => $b['net_revenue'] <=> $a['net_revenue']);

        return [
            'categories' => $reportData,
            'generated_at' => now()->toDateTimeString(),
            'filters' => $filters,
        ];
    }

    /**
     * مقارنة المبيعات بين فترتين
     * Sales Comparison Between Periods
     * 
     * @param array $filters
     * @return array
     */
    public function compareSalesBetweenPeriods(array $filters = []): array
    {
        // Current period
        $currentFrom = isset($filters['current_from']) 
            ? Carbon::parse($filters['current_from']) 
            : now()->startOfMonth();
        
        $currentTo = isset($filters['current_to']) 
            ? Carbon::parse($filters['current_to']) 
            : now();

        // Previous period (same duration)
        $periodDays = $currentFrom->diffInDays($currentTo);
        $previousTo = $currentFrom->copy()->subDay();
        $previousFrom = $previousTo->copy()->subDays($periodDays);

        // Get current period sales
        $currentSales = IssueVoucher::where('status', 'approved')
            ->where('is_transfer', false)
            ->whereBetween('date', [$currentFrom, $currentTo]);

        if (isset($filters['branch_id'])) {
            $currentSales->where('branch_id', $filters['branch_id']);
        }

        $currentTotal = $currentSales->sum('net_total');
        $currentCount = $currentSales->count();

        // Get previous period sales
        $previousSales = IssueVoucher::where('status', 'approved')
            ->where('is_transfer', false)
            ->whereBetween('date', [$previousFrom, $previousTo]);

        if (isset($filters['branch_id'])) {
            $previousSales->where('branch_id', $filters['branch_id']);
        }

        $previousTotal = $previousSales->sum('net_total');
        $previousCount = $previousSales->count();

        // Calculate changes
        $totalChange = $currentTotal - $previousTotal;
        $totalChangePercentage = $previousTotal > 0 
            ? ($totalChange / $previousTotal) * 100 
            : 0;

        $countChange = $currentCount - $previousCount;
        $countChangePercentage = $previousCount > 0 
            ? ($countChange / $previousCount) * 100 
            : 0;

        return [
            'periods' => [
                'current' => [
                    'from' => $currentFrom->toDateString(),
                    'to' => $currentTo->toDateString(),
                    'total_sales' => $currentTotal,
                    'voucher_count' => $currentCount,
                    'average_per_voucher' => $currentCount > 0 ? $currentTotal / $currentCount : 0,
                ],
                'previous' => [
                    'from' => $previousFrom->toDateString(),
                    'to' => $previousTo->toDateString(),
                    'total_sales' => $previousTotal,
                    'voucher_count' => $previousCount,
                    'average_per_voucher' => $previousCount > 0 ? $previousTotal / $previousCount : 0,
                ],
            ],
            'comparison' => [
                'total_change' => $totalChange,
                'total_change_percentage' => round($totalChangePercentage, 2),
                'count_change' => $countChange,
                'count_change_percentage' => round($countChangePercentage, 2),
                'growth_trend' => $totalChange > 0 ? 'نمو' : ($totalChange < 0 ? 'تراجع' : 'ثابت'),
            ],
            'generated_at' => now()->toDateTimeString(),
            'filters' => $filters,
        ];
    }

    /**
     * أفضل العملاء (Top Customers)
     * 
     * @param array $filters
     * @return array
     */
    public function getTopCustomers(array $filters = []): array
    {
        $limit = $filters['limit'] ?? 10;

        $query = IssueVoucher::select(
                'customer_id',
                DB::raw('COUNT(*) as purchase_count'),
                DB::raw('SUM(net_total) as total_purchases')
            )
            ->where('status', 'approved')
            ->where('is_transfer', false);

        // Date range filter
        if (isset($filters['from_date'])) {
            $query->where('date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('date', '<=', $filters['to_date']);
        }

        // Branch filter
        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        $topCustomers = $query->groupBy('customer_id')
            ->orderBy('total_purchases', 'desc')
            ->limit($limit)
            ->with('customer')
            ->get();

        $reportData = [];
        foreach ($topCustomers as $data) {
            $reportData[] = [
                'customer_id' => $data->customer_id,
                'customer_name' => $data->customer->name,
                'customer_code' => $data->customer->code,
                'purchase_count' => $data->purchase_count,
                'total_purchases' => $data->total_purchases,
                'average_per_purchase' => $data->total_purchases / $data->purchase_count,
            ];
        }

        return [
            'top_customers' => $reportData,
            'limit' => $limit,
            'generated_at' => now()->toDateTimeString(),
            'filters' => $filters,
        ];
    }

    /**
     * ملخص المبيعات (Sales Summary)
     * 
     * @param array $filters
     * @return array
     */
    public function getSalesSummary(array $filters = []): array
    {
        // Get sales for different time periods
        $today = IssueVoucher::where('status', 'approved')
            ->where('is_transfer', false)
            ->whereDate('date', now()->toDateString())
            ->sum('net_total');

        $thisWeek = IssueVoucher::where('status', 'approved')
            ->where('is_transfer', false)
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('net_total');

        $thisMonth = IssueVoucher::where('status', 'approved')
            ->where('is_transfer', false)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('net_total');

        $thisYear = IssueVoucher::where('status', 'approved')
            ->where('is_transfer', false)
            ->whereBetween('date', [now()->startOfYear(), now()->endOfYear()])
            ->sum('net_total');

        $allTime = IssueVoucher::where('status', 'approved')
            ->where('is_transfer', false)
            ->sum('net_total');

        return [
            'summary' => [
                'today' => $today,
                'this_week' => $thisWeek,
                'this_month' => $thisMonth,
                'this_year' => $thisYear,
                'all_time' => $allTime,
            ],
            'generated_at' => now()->toDateTimeString(),
        ];
    }
}
