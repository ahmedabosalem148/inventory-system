<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryCountRequest;
use App\Http\Requests\UpdateInventoryCountRequest;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\ProductStock;
use App\Services\SequencerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryCountController extends Controller
{
    public function __construct(private SequencerService $sequencer)
    {
    }

    public function index(Request $request)
    {
        $query = InventoryCount::with(['branch', 'creator', 'approver'])->withCount('items');
        if ($request->has('branch_id')) $query->where('branch_id', $request->branch_id);
        if ($request->has('status')) $query->where('status', $request->status);
        if ($request->has('search')) $query->where('code', 'like', '%' . $request->search . '%');
        
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);
        
        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    public function store(StoreInventoryCountRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $year = now()->year;
            $code = $this->sequencer->getNextSequence('inventory-count', $year, '%04d');
            
            $count = InventoryCount::create([
                'code' => "IC-{$year}-{$code}",
                'branch_id' => $request->branch_id,
                'count_date' => $request->count_date,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $stock = ProductStock::where('product_id', $item['product_id'])
                    ->where('branch_id', $request->branch_id)->first();
                
                InventoryCountItem::create([
                    'inventory_count_id' => $count->id,
                    'product_id' => $item['product_id'],
                    'system_quantity' => $stock ? $stock->quantity : 0,
                    'physical_quantity' => $item['physical_quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return response()->json([
                'message' => 'تم إنشاء الجرد بنجاح',
                'data' => $count->load(['branch', 'items.product'])
            ], 201);
        });
    }

    public function show(InventoryCount $inventoryCount)
    {
        return response()->json([
            'data' => $inventoryCount->load(['branch', 'creator', 'approver', 'items.product'])
        ]);
    }

    public function update(UpdateInventoryCountRequest $request, InventoryCount $inventoryCount)
    {
        if (!$inventoryCount->isEditable()) {
            return response()->json(['message' => 'لا يمكن تعديل الجرد بعد الاعتماد أو الرفض'], 403);
        }

        return DB::transaction(function () use ($request, $inventoryCount) {
            $inventoryCount->update($request->only(['count_date', 'notes']));

            if ($request->has('items')) {
                $inventoryCount->items()->delete();
                foreach ($request->items as $item) {
                    $stock = ProductStock::where('product_id', $item['product_id'])
                        ->where('branch_id', $inventoryCount->branch_id)->first();
                    
                    InventoryCountItem::create([
                        'inventory_count_id' => $inventoryCount->id,
                        'product_id' => $item['product_id'],
                        'system_quantity' => $stock ? $stock->quantity : 0,
                        'physical_quantity' => $item['physical_quantity'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            return response()->json([
                'message' => 'تم تحديث الجرد بنجاح',
                'data' => $inventoryCount->fresh()->load(['branch', 'items.product'])
            ]);
        });
    }

    public function destroy(InventoryCount $inventoryCount)
    {
        if (!$inventoryCount->isEditable()) {
            return response()->json(['message' => 'لا يمكن حذف الجرد بعد الاعتماد أو الرفض'], 403);
        }
        $inventoryCount->delete();
        return response()->json(['message' => 'تم حذف الجرد بنجاح']);
    }

    public function submit(InventoryCount $inventoryCount)
    {
        if ($inventoryCount->status !== 'DRAFT') {
            return response()->json(['message' => 'يمكن إرسال المسودات فقط للاعتماد'], 403);
        }
        $inventoryCount->update(['status' => 'PENDING']);
        return response()->json(['message' => 'تم إرسال الجرد للاعتماد', 'data' => $inventoryCount->fresh()]);
    }

    public function approve(InventoryCount $inventoryCount)
    {
        if (!$inventoryCount->isApprovable()) {
            return response()->json(['message' => 'لا يمكن اعتماد هذا الجرد'], 403);
        }

        return DB::transaction(function () use ($inventoryCount) {
            $inventoryCount->update([
                'status' => 'APPROVED',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            foreach ($inventoryCount->items as $item) {
                if ($item->difference != 0) {
                    $stock = ProductStock::firstOrCreate(
                        ['product_id' => $item->product_id, 'branch_id' => $inventoryCount->branch_id],
                        ['quantity' => 0]
                    );
                    $stock->quantity = $item->physical_quantity;
                    $stock->save();
                }
            }

            return response()->json([
                'message' => 'تم اعتماد الجرد وتسوية المخزون',
                'data' => $inventoryCount->fresh()
            ]);
        });
    }

    public function reject(Request $request, InventoryCount $inventoryCount)
    {
        if (!$inventoryCount->isApprovable()) {
            return response()->json(['message' => 'لا يمكن رفض هذا الجرد'], 403);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ], [
            'rejection_reason.required' => 'سبب الرفض مطلوب',
            'rejection_reason.max' => 'سبب الرفض لا يمكن أن يتجاوز 1000 حرف'
        ]);

        $inventoryCount->update([
            'status' => 'REJECTED',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json(['message' => 'تم رفض الجرد', 'data' => $inventoryCount->fresh()]);
    }
}
