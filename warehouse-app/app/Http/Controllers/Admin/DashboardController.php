<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $kpis = $this->getStats();
        $rows = $this->getSummaryRows($request);
        $warehouses = Warehouse::orderBy('name')->get();

        return view('admin.dashboard', compact('kpis', 'rows', 'warehouses'));
    }

    public function summaryFlat(Request $request)
    {
        $rows = $this->getSummaryRows($request);
        
        return response()->json($rows);
    }

    private function getStats()
    {
        $totalProducts = Product::where('active', true)->count();
        $totalWarehouses = Warehouse::count();
        
        $belowMinCount = WarehouseInventory::whereRaw('(closed_cartons * (SELECT carton_size FROM products WHERE products.id = warehouse_inventory.product_id) + loose_units) < min_threshold')
            ->count();
        
        // Calculate total inventory units more accurately
        $totalUnits = DB::table('warehouse_inventory as wi')
            ->join('products as p', 'wi.product_id', '=', 'p.id')
            ->select(DB::raw('SUM(wi.closed_cartons * p.carton_size + wi.loose_units) as total'))
            ->where('p.active', true)
            ->value('total') ?? 0;
        
        // Estimated value (could be enhanced with actual product prices later)
        $estimatedPricePerUnit = 25; // Average price per unit in your currency
        $totalValue = $totalUnits * $estimatedPricePerUnit;

        return [
            'totalProducts' => $totalProducts,
            'totalWarehouses' => $totalWarehouses,
            'belowMinCount' => $belowMinCount,
            'totalValue' => $totalValue,
            'totalUnits' => $totalUnits
        ];
    }

    private function getSummaryRows(Request $request = null)
    {
        $query = DB::table('warehouse_inventory as wi')
            ->join('products as p', 'wi.product_id', '=', 'p.id')
            ->join('warehouses as w', 'wi.warehouse_id', '=', 'w.id')
            ->select([
                'wi.product_id',
                'p.name_ar as product_name',
                'wi.warehouse_id',
                'w.name as warehouse_name',
                'p.carton_size',
                'wi.closed_cartons',
                'wi.loose_units',
                'wi.min_threshold',
                DB::raw('(wi.closed_cartons * p.carton_size + wi.loose_units) as total_units'),
                DB::raw('CASE WHEN (wi.closed_cartons * p.carton_size + wi.loose_units) < wi.min_threshold THEN 1 ELSE 0 END as below_min')
            ])
            ->where('p.active', true);

        // Add search filter if provided
        if ($request && $request->has('search') && !empty($request->search)) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('p.name_ar', 'LIKE', $search)
                  ->orWhere('w.name', 'LIKE', $search);
            });
        }

        return $query->orderBy('p.name_ar')
            ->orderBy('w.name')
            ->paginate(50)
            ->through(function ($row) {
                $row->below_min = (bool) $row->below_min;
                return $row;
            });
    }
}
