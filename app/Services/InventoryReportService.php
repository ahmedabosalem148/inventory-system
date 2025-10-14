<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\InventoryMovement;
use App\Models\ProductBranchStock;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryReportService
{
    /**
     * تقرير إجمالي المخزون حسب الفرع والفئة
     * Total Inventory Report by Branch and Category
     * 
     * @param array $filters
     * @return array
     */
    public function getTotalInventoryReport(array $filters = []): array
    {
        $query = ProductBranchStock::with(['product.category', 'branch'])
            ->select(
                'product_id',
                'branch_id',
                DB::raw('SUM(current_stock) as total_quantity'),
                DB::raw('SUM(current_stock) as total_value')
            )
            ->groupBy('product_id', 'branch_id');

        // Filter by branch
        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // Filter by category
        if (isset($filters['category_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        // Get the data
        $stocks = $query->get();

        // Load relationships
        $stocks->load(['product.category', 'branch']);

        // Group by branch
        $reportByBranch = [];
        $grandTotal = [
            'quantity' => 0,
            'value' => 0,
        ];

        foreach ($stocks as $stock) {
            $branchId = $stock->branch_id;
            $branchName = $stock->branch->name;

            if (!isset($reportByBranch[$branchId])) {
                $reportByBranch[$branchId] = [
                    'branch_id' => $branchId,
                    'branch_name' => $branchName,
                    'categories' => [],
                    'total_quantity' => 0,
                    'total_value' => 0,
                ];
            }

            $categoryId = $stock->product->category_id;
            $categoryName = $stock->product->category->name ?? 'غير مصنف';

            if (!isset($reportByBranch[$branchId]['categories'][$categoryId])) {
                $reportByBranch[$branchId]['categories'][$categoryId] = [
                    'category_id' => $categoryId,
                    'category_name' => $categoryName,
                    'products' => [],
                    'total_quantity' => 0,
                    'total_value' => 0,
                ];
            }

            // Add product to category
            $reportByBranch[$branchId]['categories'][$categoryId]['products'][] = [
                'product_id' => $stock->product_id,
                'product_name' => $stock->product->name,
                'product_code' => $stock->product->code,
                'quantity' => $stock->total_quantity,
                'total_value' => $stock->total_value,
                'unit' => $stock->product->unit,
            ];

            // Update category totals
            $reportByBranch[$branchId]['categories'][$categoryId]['total_quantity'] += $stock->total_quantity;
            $reportByBranch[$branchId]['categories'][$categoryId]['total_value'] += $stock->total_value;

            // Update branch totals
            $reportByBranch[$branchId]['total_quantity'] += $stock->total_quantity;
            $reportByBranch[$branchId]['total_value'] += $stock->total_value;

            // Update grand totals
            $grandTotal['quantity'] += $stock->total_quantity;
            $grandTotal['value'] += $stock->total_value;
        }

        // Convert categories to array (remove keys)
        foreach ($reportByBranch as &$branch) {
            $branch['categories'] = array_values($branch['categories']);
        }

        return [
            'branches' => array_values($reportByBranch),
            'grand_total' => $grandTotal,
            'generated_at' => now()->toDateTimeString(),
            'filters' => $filters,
        ];
    }

    /**
     * تقرير حركة المنتج مع الرصيد الجاري
     * Product Movement Report with Running Balance
     * 
     * @param int $productId
     * @param int|null $branchId
     * @param array $filters
     * @return array
     */
    public function getProductMovementReport(int $productId, ?int $branchId = null, array $filters = []): array
    {
        $product = Product::with('category')->findOrFail($productId);

        // Get opening balance
        $openingBalance = $this->getOpeningBalance($productId, $branchId, $filters['from_date'] ?? null);

        // Build movements query
        $query = InventoryMovement::with(['branch', 'targetBranch', 'user'])
            ->where('product_id', $productId)
            ->orderBy('movement_date')
            ->orderBy('created_at');

        // Filter by branch
        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhere('target_branch_id', $branchId);
            });
        }

        // Filter by date range
        if (isset($filters['from_date'])) {
            $query->where('movement_date', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->where('movement_date', '<=', $filters['to_date']);
        }

        // Filter by movement type
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $movements = $query->get();

        // Calculate running balance
        $runningBalance = $openingBalance;
        $reportData = [];

        foreach ($movements as $movement) {
            $quantityChange = 0;
            $movementType = '';
            $movementDescription = '';

            switch ($movement->type) {
                case 'IN':
                    $quantityChange = $movement->quantity;
                    $movementType = 'إضافة';
                    $movementDescription = 'إضافة للمخزون';
                    break;

                case 'OUT':
                    $quantityChange = -$movement->quantity;
                    $movementType = 'صرف';
                    $movementDescription = $movement->reference_type ? 'صرف - ' . class_basename($movement->reference_type) : 'صرف';
                    break;

                case 'TRANSFER_OUT':
                    if (!$branchId || $movement->branch_id == $branchId) {
                        $quantityChange = -$movement->quantity;
                        $movementType = 'تحويل خارج';
                        $movementDescription = 'تحويل إلى ' . ($movement->targetBranch->name ?? 'غير محدد');
                    }
                    break;

                case 'TRANSFER_IN':
                    if (!$branchId || $movement->target_branch_id == $branchId) {
                        $quantityChange = $movement->quantity;
                        $movementType = 'تحويل داخل';
                        $movementDescription = 'تحويل من ' . ($movement->branch->name ?? 'غير محدد');
                    }
                    break;

                case 'RETURN':
                    $quantityChange = $movement->quantity;
                    $movementType = 'إرجاع';
                    $movementDescription = 'إرجاع من عميل';
                    break;

                case 'ADJUSTMENT':
                    $quantityChange = $movement->quantity;
                    $movementType = 'تسوية';
                    $movementDescription = $movement->notes ?? 'تسوية مخزون';
                    break;
            }

            // Only include if quantity changes for this branch
            if ($quantityChange != 0) {
                $runningBalance += $quantityChange;

                $reportData[] = [
                    'date' => $movement->movement_date,
                    'time' => Carbon::parse($movement->created_at)->format('H:i'),
                    'type' => $movementType,
                    'description' => $movementDescription,
                    'reference' => $movement->reference_number,
                    'quantity_in' => $quantityChange > 0 ? $quantityChange : null,
                    'quantity_out' => $quantityChange < 0 ? abs($quantityChange) : null,
                    'balance' => $runningBalance,
                    'unit_cost' => $movement->unit_cost,
                    'branch' => $movement->branch->name ?? '',
                    'user' => $movement->user->name ?? '',
                    'notes' => $movement->notes,
                ];
            }
        }

        return [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'category' => $product->category->name ?? 'غير مصنف',
                'unit' => $product->unit,
            ],
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'movements' => $reportData,
            'total_in' => collect($reportData)->sum('quantity_in') ?? 0,
            'total_out' => collect($reportData)->sum('quantity_out') ?? 0,
            'generated_at' => now()->toDateTimeString(),
            'filters' => $filters,
        ];
    }

    /**
     * تقرير المنتجات قليلة المخزون
     * Low Stock Alert Report
     * 
     * @param array $filters
     * @return array
     */
    public function getLowStockReport(array $filters = []): array
    {
        // Get threshold from filters or use default
        $threshold = $filters['threshold'] ?? null;

        $query = Product::with(['category', 'branchStocks.branch'])
            ->select('products.*')
            ->join('product_branch_stock', 'products.id', '=', 'product_branch_stock.product_id')
            ->where('products.is_active', true);

        // Filter by branch
        if (isset($filters['branch_id'])) {
            $query->where('product_branch_stock.branch_id', $filters['branch_id']);
        }

        // Filter by category
        if (isset($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }

        // Apply threshold logic
        if ($threshold !== null) {
            // Use custom threshold
            $query->whereRaw('product_branch_stock.current_stock <= ?', [$threshold]);
        } else {
            // Use per-product minimum thresholds
            $query->whereRaw('product_branch_stock.current_stock <= product_branch_stock.min_qty');
        }

        $query->groupBy('products.id');

        $products = $query->get();

        $reportData = [];

        foreach ($products as $product) {
            foreach ($product->branchStocks as $stock) {
                // Skip if filtered by branch and this isn't the branch
                if (isset($filters['branch_id']) && $stock->branch_id != $filters['branch_id']) {
                    continue;
                }

                // Check if below threshold
                $effectiveThreshold = $threshold ?? $stock->min_qty;
                
                if ($stock->current_stock <= $effectiveThreshold) {
                    $shortfall = $effectiveThreshold - $stock->current_stock;
                    $percentage = $effectiveThreshold > 0 
                        ? round(($stock->current_stock / $effectiveThreshold) * 100, 2) 
                        : 0;

                    $reportData[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_code' => $product->code,
                        'category' => $product->category->name ?? 'غير مصنف',
                        'branch_id' => $stock->branch_id,
                        'branch_name' => $stock->branch->name,
                        'current_quantity' => $stock->current_stock,
                        'minimum_quantity' => $stock->min_qty,
                        'threshold_used' => $effectiveThreshold,
                        'shortfall' => max(0, $shortfall),
                        'percentage_of_minimum' => $percentage,
                        'unit' => $product->unit,
                        'status' => $stock->current_stock <= 0 ? 'نفذ' : ($percentage < 50 ? 'حرج' : 'منخفض'),
                    ];
                }
            }
        }

        // Sort by status priority (نفذ > حرج > منخفض) then by shortfall
        usort($reportData, function ($a, $b) {
            $statusPriority = ['نفذ' => 3, 'حرج' => 2, 'منخفض' => 1];
            $aPriority = $statusPriority[$a['status']] ?? 0;
            $bPriority = $statusPriority[$b['status']] ?? 0;

            if ($aPriority !== $bPriority) {
                return $bPriority - $aPriority;
            }

            return $b['shortfall'] - $a['shortfall'];
        });

        return [
            'products' => $reportData,
            'summary' => [
                'total_items' => count($reportData),
                'out_of_stock' => collect($reportData)->where('status', 'نفذ')->count(),
                'critical' => collect($reportData)->where('status', 'حرج')->count(),
                'low' => collect($reportData)->where('status', 'منخفض')->count(),
            ],
            'generated_at' => now()->toDateTimeString(),
            'filters' => $filters,
        ];
    }

    /**
     * Get opening balance for a product
     * 
     * @param int $productId
     * @param int|null $branchId
     * @param string|null $beforeDate
     * @return float
     */
    private function getOpeningBalance(int $productId, ?int $branchId, ?string $beforeDate): float
    {
        if (!$beforeDate) {
            // No date filter, opening balance is 0
            return 0;
        }

        // Calculate balance from movements before the date
        $query = InventoryMovement::where('product_id', $productId)
            ->where('movement_date', '<', $beforeDate);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhere('target_branch_id', $branchId);
            });
        }

        $movements = $query->get();

        $balance = 0;
        foreach ($movements as $movement) {
            switch ($movement->type) {
                case 'IN':
                case 'RETURN':
                    $balance += $movement->quantity;
                    break;

                case 'OUT':
                    $balance -= $movement->quantity;
                    break;

                case 'TRANSFER_OUT':
                    if (!$branchId || $movement->branch_id == $branchId) {
                        $balance -= $movement->quantity;
                    }
                    break;

                case 'TRANSFER_IN':
                    if (!$branchId || $movement->target_branch_id == $branchId) {
                        $balance += $movement->quantity;
                    }
                    break;

                case 'ADJUSTMENT':
                    $balance += $movement->quantity;
                    break;
            }
        }

        return $balance;
    }

    /**
     * تقرير ملخص المخزون السريع
     * Quick Inventory Summary
     * 
     * @return array
     */
    public function getInventorySummary(): array
    {
        $totalProducts = Product::where('is_active', true)->count();
        $totalValue = ProductBranchStock::sum('current_stock');
        $totalQuantity = ProductBranchStock::sum('current_stock');
        $lowStockCount = Product::where('is_active', true)
            ->join('product_branch_stock', 'products.id', '=', 'product_branch_stock.product_id')
            ->whereRaw('product_branch_stock.current_stock <= product_branch_stock.min_qty')
            ->distinct('products.id')
            ->count('products.id');
        $outOfStockCount = Product::where('is_active', true)
            ->join('product_branch_stock', 'products.id', '=', 'product_branch_stock.product_id')
            ->where('product_branch_stock.current_stock', '<=', 0)
            ->distinct('products.id')
            ->count('products.id');

        $branches = Branch::all();

        $branchSummary = [];
        foreach ($branches as $branch) {
            $branchQuantity = ProductBranchStock::where('branch_id', $branch->id)
                ->sum('current_stock');
            
            $branchSummary[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'total_quantity' => $branchQuantity ?? 0,
                'total_value' => $branchQuantity ?? 0,
            ];
        }

        return [
            'total_products' => $totalProducts,
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'branches' => $branchSummary,
            'generated_at' => now()->toDateTimeString(),
        ];
    }
}
