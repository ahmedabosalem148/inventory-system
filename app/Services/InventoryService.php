<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBranchStock;
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
        $productBranch = ProductBranchStock::where('product_id', $productId)
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
        $productBranch = ProductBranchStock::firstOrCreate(
            [
                'product_id' => $productId,
                'branch_id' => $branchId,
            ],
            [
                'qty_units' => 0,
            ]
        );

        $productBranch->current_stock += $change;
        $productBranch->save();
    }

    /**
     * Get products below reorder level for a branch
     *
     * @param int $branchId
     * @return \Illuminate\Support\Collection
     */
    public function getProductsBelowReorderLevel(int $branchId)
    {
        return ProductBranchStock::where('branch_id', $branchId)
            ->with('product')
            ->get()
            ->filter(function ($pb) {
                return $pb->current_stock < $pb->product->reorder_level;
            });
    }
}
