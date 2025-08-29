<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            ->get();
            
        $inventory = $inventory->map(function ($item) {
                $totalUnits = $item->closed_cartons * ($item->product->carton_size ?? 1) + $item->loose_units;
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $item->warehouse_id,
                    'closed_cartons' => $item->closed_cartons,
                    'loose_units' => $item->loose_units,
                    'carton_size' => $item->product->carton_size ?? 1,
                    'min_threshold' => $item->min_threshold,
                    'totalUnits' => $totalUnits,
                    'total_units' => $totalUnits, // For compatibility
                    'belowMin' => $totalUnits < $item->min_threshold,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name_ar ?? $item->product->name ?? 'منتج غير محدد',
                        'carton_size' => $item->product->carton_size ?? $item->product->units_per_carton ?? 1
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

        if (password_verify($request->password, $warehouse->password)) {
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

    /**
     * Show create product form for specific warehouse
     */
    public function createProduct($warehouseId)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        
        // التأكد من أن المستخدم مسجل دخول في هذا المخزن
        if (!session("warehouse_{$warehouseId}_auth")) {
            return redirect("/warehouses/{$warehouseId}/login")
                ->with('error', 'يجب تسجيل الدخول أولاً');
        }

        return view('warehouses.create-product', compact('warehouse'));
    }

    /**
     * Store new product for specific warehouse
     */
    public function storeProduct(Request $request, $warehouseId)
    {
        // Add extensive debugging
        \Log::info('=== STORE PRODUCT DEBUG START ===');
        \Log::info('Request Method:', [$request->method()]);
        \Log::info('Request URL:', [$request->url()]);
        \Log::info('Warehouse ID:', ['id' => $warehouseId]);
        \Log::info('Request Data:', $request->all());
        \Log::info('All Sessions:', session()->all());
        
        $warehouse = Warehouse::findOrFail($warehouseId);
        \Log::info('Warehouse Found:', ['name' => $warehouse->name, 'id' => $warehouse->id]);
        
        // التأكد من أن المستخدم مسجل دخول في هذا المخزن
        $sessionKey = "warehouse_{$warehouseId}_auth";
        $isAuthenticated = session($sessionKey);
        \Log::info('Authentication Check:', [
            'session_key' => $sessionKey,
            'is_authenticated' => $isAuthenticated,
            'session_value' => session($sessionKey)
        ]);
        
        if (!$isAuthenticated) {
            \Log::warning('Authentication Failed - Redirecting to login');
            return redirect("/warehouses/{$warehouseId}/login")
                ->with('error', 'يجب تسجيل الدخول أولاً');
        }
        
        \Log::info('Authentication Passed - Proceeding with validation');

        // Add debugging
        \Log::info('Store Product Request Data:', $request->all());
        \Log::info('Warehouse ID:', ['id' => $warehouseId]);
        \Log::info('Session Auth:', ['auth' => session("warehouse_{$warehouseId}_auth")]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'units_per_carton' => 'required|integer|min:1',
            'min_threshold' => 'nullable|integer|min:0',
            'cartons' => 'required|integer|min:1',
        ], [
            'name.required' => 'اسم المنتج مطلوب',
            'name.max' => 'اسم المنتج لا يجب أن يتجاوز 255 حرف',
            'units_per_carton.required' => 'عدد الوحدات في كل كرتونة مطلوب',
            'units_per_carton.min' => 'عدد الوحدات في كل كرتونة يجب أن يكون 1 على الأقل',
            'min_threshold.min' => 'الحد الأدنى لا يمكن أن يكون سالب',
            'cartons.required' => 'عدد الكراتين مطلوب',
            'cartons.min' => 'عدد الكراتين يجب أن يكون 1 على الأقل',
        ]);

        \Log::info('Validation Passed:', $validated);

        try {
            DB::beginTransaction();

            // إنشاء المنتج مع البيانات المدخلة
            $product = \App\Models\Product::create([
                'name_ar' => $validated['name'], // استخدام name_ar
                'carton_size' => $validated['units_per_carton'], // استخدام carton_size
                'active' => true,
            ]);
            \Log::info('Product Created:', ['id' => $product->id, 'name_ar' => $product->name_ar, 'carton_size' => $product->carton_size]);

            // إنشاء سجل المخزون في هذا المخزن مع عدد الكراتين المدخل
            $inventory = \App\Models\WarehouseInventory::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $product->id,
                'closed_cartons' => $validated['cartons'], // استخدام عدد الكراتين المدخل
                'loose_units' => 0, // بدون وحدات منفصلة
                'min_threshold' => $validated['min_threshold'] ?? 0, // استخدام الحد الأدنى المدخل
            ]);

            // إضافة حركة أولية
            \App\Models\InventoryMovement::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $product->id,
                'movement_type' => 'add',
                'quantity' => $validated['cartons'] * $validated['units_per_carton'], // Total units
                'cartons' => $validated['cartons'],
                'notes' => 'كمية أولية عند إنشاء المنتج',
                'created_by' => 'مخزن ' . $warehouse->name,
                'created_at' => now(),
            ]);

            DB::commit();
            \Log::info('Product Created Successfully:', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'warehouse_id' => $warehouseId,
                'cartons' => $validated['cartons']
            ]);

            $totalUnits = $validated['cartons'] * $validated['units_per_carton'];
            \Log::info('=== STORE PRODUCT DEBUG END - SUCCESS ===');
            
            $successMessage = "تم إنشاء المنتج '{$product->name_ar}' بنجاح ✅";
            $successMessage .= "\n📦 تم إضافة {$validated['cartons']} كرتون ({$totalUnits} وحدة)";
            $successMessage .= "\n🏪 في مخزن {$warehouse->name}";
            
            return redirect("/warehouses/{$warehouseId}")
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Product Creation Failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            \Log::info('=== STORE PRODUCT DEBUG END - ERROR ===');
            
            $errorMessage = "فشل في إنشاء المنتج ❌";
            $errorMessage .= "\n\nتفاصيل الخطأ:\n" . $e->getMessage();
            
            return back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }

    public function deleteProduct(Product $product)
    {
        try {
            // Check if product exists
            if (!$product) {
                return response()->json(['message' => 'المنتج غير موجود'], 404);
            }

            // Delete all inventory records for this product
            WarehouseInventory::where('product_id', $product->id)->delete();
            
            // Delete all movement records for this product
            InventoryMovement::where('product_id', $product->id)->delete();
            
            // Delete the product
            $product->delete();

            return response()->json([
                'message' => 'تم حذف المنتج وجميع البيانات المرتبطة به بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'حدث خطأ أثناء حذف المنتج'], 500);
        }
    }
}
