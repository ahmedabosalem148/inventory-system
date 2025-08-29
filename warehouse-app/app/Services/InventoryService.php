<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\Product;
use App\Models\WarehouseInventory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Inventory management service with business logic
 */
class InventoryService
{
    /**
     * Maximum allowed quantity per operation to prevent system abuse
     */
    const MAX_QUANTITY_LIMIT = 100000;

    /**
     * Add inventory to warehouse
     * 
     * @param int $warehouseId
     * @param int $productId
     * @param int $q Quantity in units
     * @param string $actor Who performed the action
     * @return array ['cc' => closed_cartons, 'lu' => loose_units, 'total' => total_units]
     * @throws InvalidArgumentException
     */
    public function add(int $warehouseId, int $productId, int $q, string $actor = 'warehouse-ui'): array
    {
        if ($q <= 0) {
            throw new InvalidArgumentException('الكمية يجب أن تكون أكبر من صفر');
        }

        if ($q > self::MAX_QUANTITY_LIMIT) {
            throw new InvalidArgumentException("الكمية كبيرة جداً. الحد الأقصى المسموح: " . number_format(self::MAX_QUANTITY_LIMIT));
        }

        return DB::transaction(function () use ($warehouseId, $productId, $q, $actor) {
            // Get product and carton size
            $product = Product::findOrFail($productId);
            $cartonSize = $product->carton_size;

            // Get or create inventory record with lock
            $inventory = WarehouseInventory::lockForUpdate()
                ->firstOrCreate(
                    ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                    ['closed_cartons' => 0, 'loose_units' => 0, 'min_threshold' => 0]
                );

            $currentCC = $inventory->closed_cartons;
            $currentLU = $inventory->loose_units;
            $remaining = $q;

            // If there's an open carton, fill it first
            if ($currentLU > 0) {
                $space = $cartonSize - $currentLU;
                $toFill = min($remaining, $space);
                $currentLU += $toFill;
                $remaining -= $toFill;

                // If carton is now full, convert to closed carton
                if ($currentLU == $cartonSize) {
                    $currentCC++;
                    $currentLU = 0;
                }
            }

            // Add remaining as closed cartons and loose units
            if ($remaining > 0) {
                $newClosedCartons = intdiv($remaining, $cartonSize);
                $newLooseUnits = $remaining % $cartonSize;
                
                $currentCC += $newClosedCartons;
                $currentLU += $newLooseUnits;
            }

            // Validate loose units constraint
            if ($currentLU >= $cartonSize) {
                throw new InvalidArgumentException('خطأ في حساب الوحدات المفكوكة');
            }

            // Update inventory
            $inventory->update([
                'closed_cartons' => $currentCC,
                'loose_units' => $currentLU,
                'version' => $inventory->version + 1
            ]);

            // Assert inventory constraints after save
            if ($currentLU >= $cartonSize) {
                throw new \LogicException("خطأ منطقي: الوحدات المفكوكة ({$currentLU}) >= حجم الكرتونة ({$cartonSize})");
            }

            if ($currentCC < 0) {
                throw new \LogicException("خطأ منطقي: عدد الكراتين المغلقة لا يمكن أن يكون سالباً ({$currentCC})");
            }

            // Create movement record
            Movement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'in',
                'quantity_units' => $q,
                'created_by' => $actor
            ]);

            $totalUnits = ($currentCC * $cartonSize) + $currentLU;

            return [
                'cc' => $currentCC,
                'lu' => $currentLU,
                'total' => $totalUnits
            ];
        });
    }

    /**
     * Withdraw inventory from warehouse
     * 
     * @param int $warehouseId
     * @param int $productId
     * @param int $q Quantity in units
     * @param string $actor Who performed the action
     * @return array ['cc' => closed_cartons, 'lu' => loose_units, 'total' => total_units]
     * @throws InvalidArgumentException
     */
    public function withdraw(int $warehouseId, int $productId, int $q, string $actor = 'warehouse-ui'): array
    {
        if ($q <= 0) {
            throw new InvalidArgumentException('الكمية يجب أن تكون أكبر من صفر');
        }

        if ($q > self::MAX_QUANTITY_LIMIT) {
            throw new InvalidArgumentException("الكمية كبيرة جداً. الحد الأقصى المسموح: " . number_format(self::MAX_QUANTITY_LIMIT));
        }

        return DB::transaction(function () use ($warehouseId, $productId, $q, $actor) {
            // Get product and carton size
            $product = Product::findOrFail($productId);
            $cartonSize = $product->carton_size;

            // Get inventory record with lock
            $inventory = WarehouseInventory::lockForUpdate()
                ->where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            if (!$inventory) {
                throw new InvalidArgumentException('لا يوجد مخزون لهذا المنتج في هذا المخزن');
            }

            $currentCC = $inventory->closed_cartons;
            $currentLU = $inventory->loose_units;
            $available = ($currentCC * $cartonSize) + $currentLU;

            // Check if enough inventory available
            if ($q > $available) {
                $availableCartons = intval($available / $cartonSize);
                $availableUnits = $available % $cartonSize;
                
                throw new InvalidArgumentException(
                    "الكمية غير متاحة! " .
                    "الكمية المطلوبة: {$q} وحدة. " .
                    "الكمية المتاحة: {$available} وحدة " .
                    "({$availableCartons} كرتون + {$availableUnits} وحدة منفصلة)"
                );
            }

            $remaining = $q;

            // Withdraw from loose units first
            if ($currentLU > 0) {
                $fromLoose = min($remaining, $currentLU);
                $currentLU -= $fromLoose;
                $remaining -= $fromLoose;
            }

            // If still need more, open closed cartons
            while ($remaining > 0 && $currentCC > 0) {
                // Open a carton
                $currentCC--;
                $currentLU = $cartonSize;
                
                // Take what we need from this opened carton
                $fromThisCarton = min($remaining, $currentLU);
                $currentLU -= $fromThisCarton;
                $remaining -= $fromThisCarton;
            }

            // Update inventory
            $inventory->update([
                'closed_cartons' => $currentCC,
                'loose_units' => $currentLU,
                'version' => $inventory->version + 1
            ]);

            // Assert inventory constraints after save
            if ($currentLU >= $cartonSize) {
                throw new \LogicException("خطأ منطقي: الوحدات المفكوكة ({$currentLU}) >= حجم الكرتونة ({$cartonSize})");
            }

            if ($currentCC < 0 || $currentLU < 0) {
                throw new \LogicException("خطأ منطقي: المخزون لا يمكن أن يكون سالباً (CC: {$currentCC}, LU: {$currentLU})");
            }

            // Create movement record
            Movement::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => 'out',
                'quantity_units' => $q,
                'created_by' => $actor
            ]);

            $totalUnits = ($currentCC * $cartonSize) + $currentLU;

            return [
                'cc' => $currentCC,
                'lu' => $currentLU,
                'total' => $totalUnits
            ];
        });
    }
}
