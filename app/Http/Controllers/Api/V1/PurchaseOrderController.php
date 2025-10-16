<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\InventoryService;
use App\Services\SequencerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService,
        private SequencerService $sequencerService
    ) {}

    /**
     * قائمة أوامر الشراء مع الفلترة
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = PurchaseOrder::with(['supplier', 'branch', 'items.product']);

        // Filter by branch if not super-admin
        if (!$user->hasRole('super-admin')) {
            $activeBranch = $user->getActiveBranch();
            if (!$activeBranch) {
                return response()->json([
                    'message' => 'لم يتم تعيين فرع للمستخدم'
                ], 403);
            }
            $query->where('branch_id', $activeBranch->id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Search by order number
        if ($request->filled('search')) {
            $query->searchByNumber($request->search);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        // Sort
        $sortField = $request->input('sort_by', 'order_date');
        $sortDirection = $request->input('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = min((int) $request->input('per_page', 15), 100);
        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * إنشاء أمر شراء جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after:order_date',
            'discount_type' => ['nullable', Rule::in(['NONE', 'PERCENTAGE', 'FIXED'])],
            'discount_value' => 'nullable|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_type' => ['nullable', Rule::in(['NONE', 'PERCENTAGE', 'FIXED'])],
            'items.*.discount_value' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Generate order number
            $orderNumber = $this->sequencerService->getNextSequence('purchase_orders');

            // Calculate totals
            $calculations = $this->calculateOrderTotals($validated);

            // Create order
            $order = PurchaseOrder::create([
                'order_number' => $orderNumber,
                'supplier_id' => $validated['supplier_id'],
                'branch_id' => $validated['branch_id'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'discount_type' => $validated['discount_type'] ?? 'NONE',
                'discount_value' => $validated['discount_value'] ?? 0,
                'tax_percentage' => $validated['tax_percentage'] ?? 0,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'status' => 'PENDING',
                'created_by' => auth()->id(),
                ...$calculations,
            ]);

            // Add items
            foreach ($validated['items'] as $itemData) {
                $itemCalc = $this->calculateItemTotals($itemData);
                $order->items()->create([
                    'product_id' => $itemData['product_id'],
                    'quantity_ordered' => $itemData['quantity_ordered'],
                    'unit_price' => $itemData['unit_price'],
                    'discount_type' => $itemData['discount_type'] ?? 'NONE',
                    'discount_value' => $itemData['discount_value'] ?? 0,
                    ...$itemCalc,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'تم إنشاء أمر الشراء بنجاح',
                'data' => $order->load(['supplier', 'branch', 'items.product']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل إنشاء أمر الشراء: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض تفاصيل أمر شراء
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'branch', 'items.product', 'creator', 'approver']);
        
        return response()->json([
            'data' => $purchaseOrder,
        ]);
    }

    /**
     * تحديث أمر الشراء
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (!$purchaseOrder->isEditable()) {
            return response()->json([
                'message' => 'لا يمكن تعديل الطلب في حالته الحالية',
            ], 422);
        }

        $validated = $request->validate([
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'expected_delivery_date' => 'nullable|date',
            'discount_type' => ['nullable', Rule::in(['NONE', 'PERCENTAGE', 'FIXED'])],
            'discount_value' => 'nullable|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'sometimes|required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Update order
            if (isset($validated['items'])) {
                // Delete old items and create new ones
                $purchaseOrder->items()->delete();
                
                foreach ($validated['items'] as $itemData) {
                    $itemCalc = $this->calculateItemTotals($itemData);
                    $purchaseOrder->items()->create([
                        'product_id' => $itemData['product_id'],
                        'quantity_ordered' => $itemData['quantity_ordered'],
                        'unit_price' => $itemData['unit_price'],
                        ...$itemCalc,
                    ]);
                }
                
                $calculations = $this->calculateOrderTotals($validated);
                $validated = array_merge($validated, $calculations);
            }

            $purchaseOrder->update($validated);

            DB::commit();

            return response()->json([
                'message' => 'تم تحديث أمر الشراء بنجاح',
                'data' => $purchaseOrder->fresh(['supplier', 'branch', 'items.product']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل تحديث أمر الشراء: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف أمر الشراء
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if (!$purchaseOrder->isEditable()) {
            return response()->json([
                'message' => 'لا يمكن حذف الطلب في حالته الحالية',
            ], 422);
        }

        $purchaseOrder->delete();

        return response()->json([
            'message' => 'تم حذف أمر الشراء بنجاح',
        ]);
    }

    /**
     * حساب إجماليات الأمر
     */
    private function calculateOrderTotals($data)
    {
        $subtotal = 0;
        
        foreach ($data['items'] as $item) {
            $itemTotal = $item['quantity_ordered'] * $item['unit_price'];
            $subtotal += $itemTotal;
        }

        $discountAmount = 0;
        if (isset($data['discount_type']) && $data['discount_type'] !== 'NONE') {
            if ($data['discount_type'] === 'PERCENTAGE') {
                $discountAmount = ($subtotal * ($data['discount_value'] ?? 0)) / 100;
            } else {
                $discountAmount = $data['discount_value'] ?? 0;
            }
        }

        $afterDiscount = $subtotal - $discountAmount;
        $taxAmount = ($afterDiscount * ($data['tax_percentage'] ?? 0)) / 100;
        $shippingCost = $data['shipping_cost'] ?? 0;
        $totalAmount = $afterDiscount + $taxAmount + $shippingCost;

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * حساب إجماليات الصنف
     */
    private function calculateItemTotals($item)
    {
        $subtotal = $item['quantity_ordered'] * $item['unit_price'];
        
        $discountAmount = 0;
        if (isset($item['discount_type']) && $item['discount_type'] !== 'NONE') {
            if ($item['discount_type'] === 'PERCENTAGE') {
                $discountAmount = ($subtotal * ($item['discount_value'] ?? 0)) / 100;
            } else {
                $discountAmount = $item['discount_value'] ?? 0;
            }
        }

        $total = $subtotal - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total' => $total,
        ];
    }
}
