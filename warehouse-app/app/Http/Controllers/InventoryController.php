<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function warehouses()
    {
        $warehouses = Warehouse::select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($warehouses);
    }

    public function index(Request $request, $warehouseId)
    {
        $inventory = WarehouseInventory::with(['product' => function($query) {
                $query->where('active', true);
            }])
            ->where('warehouse_id', $warehouseId)
            ->whereHas('product', function($query) {
                $query->where('active', true);
            })
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $item->warehouse_id,
                    'closed_cartons' => $item->closed_cartons,
                    'loose_units' => $item->loose_units,
                    'min_threshold' => $item->min_threshold,
                    'totalUnits' => $item->total_units,
                    'belowMin' => $item->total_units < $item->min_threshold,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'carton_size' => $item->product->carton_size
                    ]
                ];
            });

        if ($request->has('search') && $request->search) {
            $inventory = $inventory->filter(function ($item) use ($request) {
                return str_contains(strtolower($item['product']['name']), strtolower($request->search));
            });
        }

        return response()->json($inventory->values());
    }

    public function add(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit_type' => 'required|in:cartons,units',
            'note' => 'nullable|string|max:255'
        ]);

        try {
            // Convert quantity to units if needed
            $quantityInUnits = $request->quantity;
            if ($request->unit_type === 'cartons') {
                $product = \App\Models\Product::findOrFail($request->product_id);
                $quantityInUnits = $request->quantity * $product->carton_size;
            }

            $result = $this->inventoryService->add(
                $request->warehouse_id, 
                $request->product_id, 
                $quantityInUnits, 
                $request->note ?? 'إضافة من واجهة المخزن'
            );

            return response()->json([
                'message' => 'تم إضافة المخزون بنجاح',
                'data' => $result
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'حدث خطأ غير متوقع'], 500);
        }
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'unit_type' => 'required|in:cartons,units',
            'note' => 'nullable|string|max:255'
        ]);

        try {
            // Convert quantity to units if needed
            $quantityInUnits = $request->quantity;
            if ($request->unit_type === 'cartons') {
                $product = \App\Models\Product::findOrFail($request->product_id);
                $quantityInUnits = $request->quantity * $product->carton_size;
            }

            $result = $this->inventoryService->withdraw(
                $request->warehouse_id, 
                $request->product_id, 
                $quantityInUnits, 
                $request->note ?? 'سحب من واجهة المخزن'
            );

            return response()->json([
                'message' => 'تم سحب المخزون بنجاح',
                'data' => $result
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'حدث خطأ غير متوقع'], 500);
        }
    }

    public function setMin(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'product_id' => 'required|integer|exists:products,id',
            'min_threshold' => 'required|integer|min:0'
        ]);

        $inventory = WarehouseInventory::where('product_id', $request->product_id)
            ->where('warehouse_id', $request->warehouse_id)
            ->first();

        if (!$inventory) {
            return response()->json(['message' => 'سجل المخزون غير موجود'], 404);
        }

        $inventory->update(['min_threshold' => $request->min_threshold]);

        return response()->json([
            'message' => 'تم تحديث الحد الأدنى بنجاح',
            'min_threshold' => $inventory->min_threshold
        ]);
    }

    public function warehousesView()
    {
        $warehouses = Warehouse::orderBy('name')->get();
        return view('warehouses.index', compact('warehouses'));
    }

    public function showWarehouse($warehouseId)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        return view('warehouses.show', compact('warehouse'));
    }

    /**
     * Show warehouse login form
     */
    public function showWarehouseLogin($warehouseId)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        return view('warehouses.login', compact('warehouse'));
    }

    /**
     * Authenticate warehouse access
     */
    public function authenticateWarehouse(Request $request, $warehouseId)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        $warehouse = Warehouse::findOrFail($warehouseId);

        if ($request->password === $warehouse->password) {
            // Store warehouse authentication in session
            session(["warehouse_{$warehouseId}_auth" => true]);
            
            return redirect("/warehouses/{$warehouseId}")
                ->with('success', "مرحباً بك في {$warehouse->name}");
        }

        return back()
            ->withInput()
            ->with('error', 'كلمة مرور خاطئة');
    }

    /**
     * Logout from warehouse
     */
    public function logoutWarehouse($warehouseId)
    {
        session()->forget("warehouse_{$warehouseId}_auth");
        
        return redirect('/warehouses')
            ->with('success', 'تم تسجيل الخروج بنجاح');
    }
}
