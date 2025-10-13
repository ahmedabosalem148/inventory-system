<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Branch;

/**
 * تقارير المخزون مع الفلاتر المتقدمة
 */
class InventoryReportController extends Controller
{
    /**
     * تقرير حركة المخزون
     */
    public function movements(Request $request)
    {
        // Build query with filters
        $query = InventoryMovement::with(['product', 'branch'])
            ->applyFilters($request->only([
                'date_from',
                'date_to',
                'branch_id',
                'product_id',
                'category_id',
            ]));

        // فلتر إضافي حسب نوع الحركة
        if ($request->filled('movement_type')) {
            $query->ofType($request->movement_type);
        }

        // الترتيب
        $query->latest();

        // Pagination
        $movements = $query->paginate(50)->appends($request->query());

        return view('reports.inventory.movements', compact('movements'));
    }

    /**
     * تقرير الأرصدة الحالية
     */
    public function currentStock(Request $request)
    {
        $query = Product::with(['branches', 'category'])
            ->active();

        // فلتر حسب الفرع
        if ($request->filled('branch_id')) {
            $query->whereHas('branches', function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        // فلتر حسب التصنيف
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // فلتر: أقل من الحد الأدنى فقط
        if ($request->filled('low_stock')) {
            $query->whereHas('branches', function ($q) {
                $q->whereRaw('current_qty < min_qty');
            });
        }

        // البحث
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $products = $query->paginate(50)->appends($request->query());

        // إذا كان تصدير
        if ($request->filled('export')) {
            return $this->exportStock($products, $request->export);
        }

        return view('reports.inventory.current-stock', compact('products'));
    }

    /**
     * تقرير حركة صنف معين
     */
    public function productMovementHistory(Request $request, Product $product)
    {
        $query = InventoryMovement::where('product_id', $product->id)
            ->with('branch')
            ->filterByDateRange($request->date_from, $request->date_to)
            ->filterByBranch($request->branch_id);

        // فلتر نوع الحركة
        if ($request->filled('movement_type')) {
            $query->ofType($request->movement_type);
        }

        $query->orderBy('created_at');

        $movements = $query->get();

        // حساب الرصيد المتحرك
        $balance = 0;
        $movements = $movements->map(function ($movement) use (&$balance) {
            if (in_array($movement->movement_type, ['ADD', 'RETURN', 'TRANSFER_IN'])) {
                $balance += $movement->qty_units;
            } else {
                $balance -= $movement->qty_units;
            }
            $movement->running_balance = $balance;
            return $movement;
        });

        return view('reports.inventory.product-history', compact('product', 'movements'));
    }

    /**
     * تقرير الأصناف الأكثر حركة
     */
    public function mostActive(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth();
        $dateTo = $request->date_to ?? now();

        $products = Product::select('products.*')
            ->join('inventory_movements', 'products.id', '=', 'inventory_movements.product_id')
            ->whereBetween('inventory_movements.created_at', [$dateFrom, $dateTo])
            ->selectRaw('COUNT(inventory_movements.id) as movements_count')
            ->selectRaw('SUM(CASE WHEN inventory_movements.movement_type = "ISSUE" THEN inventory_movements.qty_units ELSE 0 END) as total_issued')
            ->groupBy('products.id')
            ->orderByDesc('movements_count')
            ->limit(50)
            ->get();

        return view('reports.inventory.most-active', compact('products', 'dateFrom', 'dateTo'));
    }

    /**
     * تصدير تقرير الأرصدة
     */
    private function exportStock($products, $format)
    {
        if ($format === 'csv') {
            return $this->exportCSV($products);
        } elseif ($format === 'pdf') {
            return $this->exportPDF($products);
        }
    }

    /**
     * تصدير CSV
     */
    private function exportCSV($products)
    {
        $filename = 'inventory-stock-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM للعربية في Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, ['SKU', 'اسم الصنف', 'الفرع', 'الرصيد الحالي', 'الحد الأدنى', 'الحالة']);

            foreach ($products as $product) {
                foreach ($product->branches as $branch) {
                    $status = $branch->pivot->current_qty < $branch->pivot->min_qty ? 'أقل من الحد' : 'طبيعي';
                    
                    fputcsv($file, [
                        $product->sku,
                        $product->name,
                        $branch->name,
                        $branch->pivot->current_qty,
                        $branch->pivot->min_qty,
                        $status,
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * تصدير PDF
     */
    private function exportPDF($products)
    {
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('reports.inventory.stock-pdf', compact('products'));
        
        return $pdf->download('inventory-stock-' . date('Y-m-d') . '.pdf');
    }
}
