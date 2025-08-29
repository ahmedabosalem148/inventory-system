<?php

namespace App\Http\Controllers\WarehouseManager;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WarehouseManagerController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Show login page
     */
    public function showLogin()
    {
        // إذا كان مسجل دخول، انقله للوحة التحكم
        if (session('warehouse_manager_auth')) {
            return redirect('/warehouse-manager/dashboard');
        }

        return view('warehouse-manager.login');
    }

    /**
     * Handle login
     */
    public function login(Request $request)
    {
        $pin = $request->input('pin');

        // Use simple PIN: 5678 for warehouse manager
        if ($pin !== '5678') {
            return back()
                ->withInput()
                ->with('error', 'كود PIN غير صحيح');
        }

        session(['warehouse_manager_auth' => true]);
        return redirect('/warehouse-manager/dashboard')
            ->with('success', 'مرحباً بك في لوحة تحكم المخازن');
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        session()->forget('warehouse_manager_auth');
        return redirect('/warehouse-manager')
            ->with('success', 'تم تسجيل الخروج بنجاح');
    }

    /**
     * Show dashboard
     */
    public function dashboard(Request $request)
    {
        $kpis = $this->getStats();
        $rows = $this->getSummaryRows($request);
        $warehouses = Warehouse::orderBy('name')->get();

        return view('warehouse-manager.dashboard', compact('kpis', 'rows', 'warehouses'));
    }

    /**
     * Get summary data as JSON
     */
    public function summaryFlat(Request $request)
    {
        $rows = $this->getSummaryRows($request);
        
        return response()->json($rows);
    }

    /**
     * Get statistics for dashboard
     */
    private function getStats()
    {
        $totalProducts = Product::where('active', true)->count();
        $totalWarehouses = Warehouse::count();
        
        $belowMinCount = WarehouseInventory::whereRaw('(closed_cartons * (SELECT carton_size FROM products WHERE products.id = warehouse_inventory.product_id) + loose_units) < min_threshold')
            ->count();
        
        // Calculate total inventory units accurately
        $totalUnits = DB::table('warehouse_inventory as wi')
            ->join('products as p', 'wi.product_id', '=', 'p.id')
            ->select(DB::raw('SUM(wi.closed_cartons * p.carton_size + wi.loose_units) as total'))
            ->where('p.active', true)
            ->value('total') ?? 0;

        return [
            'totalProducts' => $totalProducts,
            'totalWarehouses' => $totalWarehouses,
            'belowMinCount' => $belowMinCount,
            'totalUnits' => $totalUnits,
        ];
    }

    /**
     * Get summary rows for the table
     */
    private function getSummaryRows(Request $request)
    {
        $query = DB::table('warehouse_inventory as wi')
            ->join('products as p', 'wi.product_id', '=', 'p.id')
            ->join('warehouses as w', 'wi.warehouse_id', '=', 'w.id')
            ->select([
                'wi.id as inventory_id',
                'wi.warehouse_id',
                'wi.product_id',
                'p.name_ar as product_name',
                'p.carton_size',
                'w.name as warehouse_name',
                'wi.closed_cartons',
                'wi.loose_units',
                'wi.min_threshold',
                DB::raw('(wi.closed_cartons * p.carton_size + wi.loose_units) as total_units'),
                DB::raw('CASE WHEN (wi.closed_cartons * p.carton_size + wi.loose_units) < wi.min_threshold THEN 1 ELSE 0 END as is_below_min')
            ])
            ->where('p.active', true);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('p.name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('w.name', 'LIKE', "%{$search}%");
            });
        }

        // Warehouse filter
        if ($request->filled('warehouse_id')) {
            $query->where('wi.warehouse_id', $request->warehouse_id);
        }

        // Below minimum filter
        if ($request->boolean('below_min')) {
            $query->whereRaw('(wi.closed_cartons * p.carton_size + wi.loose_units) < wi.min_threshold');
        }

        return $query->orderBy('w.name')
                    ->orderBy('p.name_ar')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'inventory_id' => $row->inventory_id,
                            'warehouse_id' => $row->warehouse_id,
                            'product_id' => $row->product_id,
                            'product_name' => $row->product_name,
                            'warehouse_name' => $row->warehouse_name,
                            'closed_cartons' => $row->closed_cartons,
                            'loose_units' => $row->loose_units,
                            'units_per_carton' => $row->carton_size,
                            'total_units' => $row->total_units,
                            'min_threshold' => $row->min_threshold,
                            'below_min' => (bool) $row->is_below_min,
                        ];
                    });
    }
}
