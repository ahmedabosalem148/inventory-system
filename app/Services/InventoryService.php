<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Issue product from branch (decrease stock)
     *
     * @param int $productId
     * @param int $branchId
     * @param float $quantity
     * @param string $notes
     * @param array $metadata
     * @return InventoryMovement
     * @throws \Exception
     */
    public function issueProduct(
        int $productId,
        int $branchId,
        float $quantity,
        string $notes,
        array $metadata = []
    ): InventoryMovement {
        return DB::transaction(function () use ($productId, $branchId, $quantity, $notes, $metadata) {
            // Check current stock
            $currentStock = $this->getCurrentStock($productId, $branchId);

            if ($currentStock < $quantity) {
                throw new \Exception("Insufficient stock. Available: {$currentStock}, Requested: {$quantity}");
            }

            // Decrease stock
            $this->updateStock($productId, $branchId, -$quantity);

            // Record movement
            return InventoryMovement::create([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'movement_type' => 'ISSUE',
                'qty_units' => $quantity,
                'ref_table' => $metadata['reference_type'] ?? null,
                'ref_id' => $metadata['reference_id'] ?? null,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Return product to branch (increase stock)
     *
     * @param int $productId
     * @param int $branchId
     * @param float $quantity
     * @param string $notes
     * @param array $metadata
     * @return InventoryMovement
     */
    public function returnProduct(
        int $productId,
        int $branchId,
        float $quantity,
        string $notes,
        array $metadata = []
    ): InventoryMovement {
        return DB::transaction(function () use ($productId, $branchId, $quantity, $notes, $metadata) {
            // Increase stock
            $this->updateStock($productId, $branchId, $quantity);

            // Record movement
            return InventoryMovement::create([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'movement_type' => 'RETURN',
                'qty_units' => $quantity,
                'ref_table' => $metadata['reference_type'] ?? null,
                'ref_id' => $metadata['reference_id'] ?? null,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Transfer product between branches
     *
     * @param int $productId
     * @param int $fromBranchId
     * @param int $toBranchId
     * @param float $quantity
     * @param string $notes
     * @return array ['out' => InventoryMovement, 'in' => InventoryMovement]
     * @throws \Exception
     */
    public function transferProduct(
        int $productId,
        int $fromBranchId,
        int $toBranchId,
        float $quantity,
        string $notes
    ): array {
        return DB::transaction(function () use ($productId, $fromBranchId, $toBranchId, $quantity, $notes) {
            // Check source stock
            $currentStock = $this->getCurrentStock($productId, $fromBranchId);

            if ($currentStock < $quantity) {
                throw new \Exception("Insufficient stock for transfer. Available: {$currentStock}, Requested: {$quantity}");
            }

            // Decrease from source
            $this->updateStock($productId, $fromBranchId, -$quantity);

            // Increase at target
            $this->updateStock($productId, $toBranchId, $quantity);

            // Record movements
            $outMovement = InventoryMovement::create([
                'product_id' => $productId,
                'branch_id' => $fromBranchId,
                'movement_type' => 'TRANSFER_OUT',
                'qty_units' => $quantity,
                'notes' => $notes,
            ]);

            $inMovement = InventoryMovement::create([
                'product_id' => $productId,
                'branch_id' => $toBranchId,
                'movement_type' => 'TRANSFER_IN',
                'qty_units' => $quantity,
                'notes' => $notes,
            ]);

            return [
                'out' => $outMovement,
                'in' => $inMovement,
            ];
        });
    }

    /**
     * Get current stock for product in branch
     *
     * @param int $productId
     * @param int $branchId
     * @return float
     */
    public function getCurrentStock(int $productId, int $branchId): float
    {
        $productBranch = ProductBranch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        return $productBranch?->current_stock ?? 0;
    }

    /**
     * Check if stock is below reorder level
     *
     * @param int $productId
     * @param int $branchId
     * @return bool
     */
    public function isBelowReorderLevel(int $productId, int $branchId): bool
    {
        $product = Product::findOrFail($productId);
        $currentStock = $this->getCurrentStock($productId, $branchId);

        return $currentStock < $product->reorder_level;
    }

    /**
     * Update stock quantity
     *
     * @param int $productId
     * @param int $branchId
     * @param float $change (positive for increase, negative for decrease)
     * @return void
     */
    protected function updateStock(int $productId, int $branchId, float $change): void
    {
        $productBranch = ProductBranch::firstOrCreate(
            [
                'product_id' => $productId,
                'branch_id' => $branchId,
            ],
            [
                'current_stock' => 0,
                'reserved_stock' => 0,
                'min_qty' => 0,
            ]
        );

        $newStock = $productBranch->current_stock + $change;
        $productBranch->current_stock = $newStock;
        $productBranch->save();

        // Check if stock dropped below minimum and send notification
        if ($change < 0) { // Only when stock decreases
            $product = Product::find($productId);
            if ($product && $newStock <= $productBranch->min_qty && $productBranch->min_qty > 0) {
                $this->sendLowStockNotification($product, $productBranch);
            }
        }
    }

    /**
     * Send low stock notification to admins and managers
     *
     * @param Product $product
     * @param ProductBranch $productBranch
     * @return void
     */
    protected function sendLowStockNotification(Product $product, ProductBranch $productBranch): void
    {
        try {
            $notificationService = new \App\Services\NotificationService();
            
            // Send to managers only (accounting is the admin role in this system)
            $notificationService->sendToRole(
                'manager',
                \App\Models\Notification::TYPE_LOW_STOCK,
                'تنبيه مخزون منخفض',
                "منتج \"{$product->name}\" وصل لأقل من الحد الأدنى للمخزون ({$productBranch->current_stock} وحدة متبقية)",
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'branch_id' => $productBranch->branch_id,
                    'current_stock' => $productBranch->current_stock,
                    'min_qty' => $productBranch->min_qty,
                ],
                '#products'
            );
        } catch (\Exception $e) {
            // Log error but don't fail the stock update
            \Log::error('Failed to send low stock notification: ' . $e->getMessage());
        }
    }

    /**
     * Get products below reorder level for a branch
     *
     * @param int $branchId
     * @return \Illuminate\Support\Collection
     */
    public function getProductsBelowReorderLevel(int $branchId)
    {
        return ProductBranch::where('branch_id', $branchId)
            ->with('product')
            ->get()
            ->filter(function ($pb) {
                return $pb->current_stock < $pb->product->reorder_level;
            });
    }

    /**
     * Get products below minimum quantity for a branch (using new min_qty field)
     *
     * @param int $branchId
     * @return \Illuminate\Support\Collection
     */
    public function getProductsBelowMinQuantity(int $branchId)
    {
        return ProductBranch::where('branch_id', $branchId)
            ->with('product')
            ->get()
            ->filter(function ($pb) {
                return $pb->is_low_stock; // Uses the attribute we defined
            });
    }

    /**
     * Check if product is below minimum quantity in branch
     *
     * @param int $productId
     * @param int $branchId
     * @return bool
     */
    public function isBelowMinQuantity(int $productId, int $branchId): bool
    {
        $productBranch = ProductBranch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        return $productBranch ? $productBranch->is_low_stock : false;
    }

    /**
     * Get stock status for product in branch
     *
     * @param int $productId
     * @param int $branchId
     * @return string (ok|warning|low_stock|out_of_stock)
     */
    public function getStockStatus(int $productId, int $branchId): string
    {
        $productBranch = ProductBranch::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        return $productBranch ? $productBranch->stock_status : 'out_of_stock';
    }

    /**
     * Add product to branch (increase stock)
     *
     * @param int $productId
     * @param int $branchId
     * @param float $quantity
     * @param string $notes
     * @param array $metadata
     * @return InventoryMovement
     */
    public function addProduct(
        int $productId,
        int $branchId,
        float $quantity,
        string $notes,
        array $metadata = []
    ): InventoryMovement {
        return DB::transaction(function () use ($productId, $branchId, $quantity, $notes, $metadata) {
            // Get product for pack size calculation
            $product = Product::findOrFail($productId);
            
            // Convert to units if pack size is specified
            $unitsToAdd = $quantity;
            if (isset($metadata['in_packs']) && $metadata['in_packs'] && $product->pack_size > 0) {
                $unitsToAdd = $quantity * $product->pack_size;
            }

            // Increase stock
            $this->updateStock($productId, $branchId, $unitsToAdd);

            // Record movement
            return InventoryMovement::create([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'movement_type' => 'ADD',
                'qty_units' => $unitsToAdd,
                'unit_price_snapshot' => $metadata['unit_price'] ?? $product->purchase_price,
                'ref_table' => $metadata['reference_type'] ?? null,
                'ref_id' => $metadata['reference_id'] ?? null,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Bulk update stock for multiple products (for stock adjustments)
     *
     * @param array $adjustments [['product_id' => 1, 'branch_id' => 1, 'new_quantity' => 50, 'notes' => '...'], ...]
     * @return array of InventoryMovements
     */
    public function bulkStockAdjustment(array $adjustments): array
    {
        return DB::transaction(function () use ($adjustments) {
            $movements = [];

            foreach ($adjustments as $adjustment) {
                $productId = $adjustment['product_id'];
                $branchId = $adjustment['branch_id'];
                $newQuantity = $adjustment['new_quantity'];
                $notes = $adjustment['notes'] ?? 'Stock Adjustment';

                $currentStock = $this->getCurrentStock($productId, $branchId);
                $difference = $newQuantity - $currentStock;

                if ($difference != 0) {
                    // Update the stock
                    $this->updateStock($productId, $branchId, $difference);

                    // Record the movement
                    $movements[] = InventoryMovement::create([
                        'product_id' => $productId,
                        'branch_id' => $branchId,
                        'movement_type' => $difference > 0 ? 'ADD' : 'ISSUE',
                        'qty_units' => abs($difference),
                        'ref_table' => 'stock_adjustment',
                        'ref_id' => null,
                        'notes' => $notes . " (من {$currentStock} إلى {$newQuantity})",
                    ]);
                }
            }

            return $movements;
        });
    }

    /**
     * Get inventory summary for dashboard
     *
     * @param int|null $branchId
     * @return array
     */
    public function getInventorySummary(?int $branchId = null): array
    {
        $query = ProductBranch::with('product');
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $stocks = $query->get();
        
        $totalProducts = $stocks->count();
        $totalQuantity = $stocks->sum('current_stock');
        $lowStockCount = $stocks->filter(fn($stock) => $stock->is_low_stock)->count();
        $outOfStockCount = $stocks->filter(fn($stock) => $stock->current_stock <= 0)->count();
        $totalValue = $stocks->sum(function($stock) {
            return $stock->current_stock * ($stock->product->purchase_price ?? 0);
        });

        return [
            'total_items' => $totalProducts,
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'low_stock_percentage' => $totalProducts > 0 ? round(($lowStockCount / $totalProducts) * 100, 2) : 0,
        ];
    }
}
