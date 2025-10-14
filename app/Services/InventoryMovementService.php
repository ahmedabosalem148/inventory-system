<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductBranchStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * InventoryMovementService
 * إدارة شاملة لحركات المخزون مع transaction safety
 */
class InventoryMovementService
{
    /**
     * تسجيل حركة مخزنية عامة
     * 
     * @param array $data
     * @return InventoryMovement
     * @throws \Exception
     */
    public function recordMovement(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {
            // Validate required fields
            $this->validateMovementData($data);
            
            // Get current stock
            $stock = ProductBranchStock::where('product_id', $data['product_id'])
                ->where('branch_id', $data['branch_id'])
                ->lockForUpdate()
                ->first();
                
            if (!$stock) {
                throw new \Exception("لا يوجد رصيد للمنتج في هذا الفرع");
            }
            
            // Calculate quantity impact
            $quantityImpact = $this->calculateQuantityImpact($data['movement_type'], $data['qty_units']);
            
            // Check for negative stock
            $newBalance = $stock->quantity + $quantityImpact;
            if ($newBalance < 0) {
                $product = Product::find($data['product_id']);
                throw new \Exception("الرصيد غير كافٍ. الرصيد الحالي: {$stock->quantity}, المطلوب: " . abs($quantityImpact));
            }
            
            // Update stock
            $stock->quantity = $newBalance;
            $stock->save();
            
            // Record movement with running balance
            $movement = InventoryMovement::create([
                'branch_id' => $data['branch_id'],
                'product_id' => $data['product_id'],
                'movement_type' => $data['movement_type'],
                'qty_units' => $data['qty_units'],
                'unit_price_snapshot' => $data['unit_price_snapshot'] ?? null,
                'ref_table' => $data['ref_table'] ?? null,
                'ref_id' => $data['ref_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            
            Log::info('Inventory movement recorded', [
                'movement_id' => $movement->id,
                'type' => $data['movement_type'],
                'product_id' => $data['product_id'],
                'branch_id' => $data['branch_id'],
                'quantity' => $data['qty_units'],
                'new_balance' => $newBalance
            ]);
            
            return $movement;
        });
    }
    
    /**
     * تسجيل حركة صرف (Issue Voucher)
     * 
     * @param int $productId
     * @param int $branchId
     * @param float $quantity
     * @param float $unitPrice
     * @param int $issueVoucherId
     * @param string|null $notes
     * @return InventoryMovement
     */
    public function recordIssue(
        int $productId,
        int $branchId,
        float $quantity,
        float $unitPrice,
        int $issueVoucherId,
        ?string $notes = null
    ): InventoryMovement {
        return $this->recordMovement([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'movement_type' => 'ISSUE',
            'qty_units' => $quantity,
            'unit_price_snapshot' => $unitPrice,
            'ref_table' => 'issue_vouchers',
            'ref_id' => $issueVoucherId,
            'notes' => $notes ?? 'صرف بضاعة'
        ]);
    }
    
    /**
     * تسجيل حركة مرتجع (Return Voucher)
     * 
     * @param int $productId
     * @param int $branchId
     * @param float $quantity
     * @param float $unitPrice
     * @param int $returnVoucherId
     * @param string|null $notes
     * @return InventoryMovement
     */
    public function recordReturn(
        int $productId,
        int $branchId,
        float $quantity,
        float $unitPrice,
        int $returnVoucherId,
        ?string $notes = null
    ): InventoryMovement {
        return $this->recordMovement([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'movement_type' => 'RETURN',
            'qty_units' => $quantity,
            'unit_price_snapshot' => $unitPrice,
            'ref_table' => 'return_vouchers',
            'ref_id' => $returnVoucherId,
            'notes' => $notes ?? 'مرتجع بضاعة'
        ]);
    }
    
    /**
     * تسجيل حركة إضافة/شراء
     * 
     * @param int $productId
     * @param int $branchId
     * @param float $quantity
     * @param float $unitPrice
     * @param string|null $notes
     * @return InventoryMovement
     */
    public function recordAddition(
        int $productId,
        int $branchId,
        float $quantity,
        float $unitPrice,
        ?string $notes = null
    ): InventoryMovement {
        return $this->recordMovement([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'movement_type' => 'ADD',
            'qty_units' => $quantity,
            'unit_price_snapshot' => $unitPrice,
            'notes' => $notes ?? 'إضافة/شراء'
        ]);
    }
    
    /**
     * تسجيل حركة تحويل بين فروع
     * 
     * @param int $productId
     * @param int $fromBranchId
     * @param int $toBranchId
     * @param float $quantity
     * @param int|null $transferId
     * @param string|null $notes
     * @return array [outMovement, inMovement]
     */
    public function recordTransfer(
        int $productId,
        int $fromBranchId,
        int $toBranchId,
        float $quantity,
        ?int $transferId = null,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use (
            $productId, 
            $fromBranchId, 
            $toBranchId, 
            $quantity, 
            $transferId, 
            $notes
        ) {
            // Record TRANSFER_OUT from source branch
            $outMovement = $this->recordMovement([
                'product_id' => $productId,
                'branch_id' => $fromBranchId,
                'movement_type' => 'TRANSFER_OUT',
                'qty_units' => $quantity,
                'ref_table' => 'issue_vouchers',
                'ref_id' => $transferId,
                'notes' => $notes ?? "تحويل إلى الفرع #{$toBranchId}"
            ]);
            
            // Record TRANSFER_IN to destination branch
            $inMovement = $this->recordMovement([
                'product_id' => $productId,
                'branch_id' => $toBranchId,
                'movement_type' => 'TRANSFER_IN',
                'qty_units' => $quantity,
                'ref_table' => 'issue_vouchers',
                'ref_id' => $transferId,
                'notes' => $notes ?? "تحويل من الفرع #{$fromBranchId}"
            ]);
            
            return [$outMovement, $inMovement];
        });
    }
    
    /**
     * الحصول على كارت الصنف (Product Card) - تقرير حركة المنتج
     * 
     * @param int $productId
     * @param int $branchId
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return array
     */
    public function getProductCard(
        int $productId,
        int $branchId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $query = InventoryMovement::with(['product', 'branch'])
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->orderBy('created_at', 'asc');
            
        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }
        
        $movements = $query->get();
        
        // Calculate running balance
        $runningBalance = $this->getOpeningBalance($productId, $branchId, $fromDate);
        
        $movementsWithBalance = $movements->map(function ($movement) use (&$runningBalance) {
            $quantityImpact = $this->calculateQuantityImpact($movement->movement_type, $movement->qty_units);
            $runningBalance += $quantityImpact;
            
            return [
                'id' => $movement->id,
                'date' => $movement->created_at->format('Y-m-d H:i:s'),
                'type' => $movement->movement_type,
                'type_name' => $movement->movement_type_name,
                'qty_in' => $quantityImpact > 0 ? $movement->qty_units : 0,
                'qty_out' => $quantityImpact < 0 ? $movement->qty_units : 0,
                'running_balance' => $runningBalance,
                'unit_price' => $movement->unit_price_snapshot,
                'reference' => $movement->ref_table ? "{$movement->ref_table}#{$movement->ref_id}" : null,
                'notes' => $movement->notes
            ];
        });
        
        return [
            'product_id' => $productId,
            'branch_id' => $branchId,
            'opening_balance' => $this->getOpeningBalance($productId, $branchId, $fromDate),
            'closing_balance' => $runningBalance,
            'movements' => $movementsWithBalance,
            'summary' => $this->getMovementsSummary($productId, $branchId, $fromDate, $toDate)
        ];
    }
    
    /**
     * الحصول على ملخص الحركات
     * 
     * @param int $productId
     * @param int $branchId
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return array
     */
    public function getMovementsSummary(
        int $productId,
        int $branchId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $query = InventoryMovement::where('product_id', $productId)
            ->where('branch_id', $branchId);
            
        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }
        
        $movements = $query->get();
        
        return [
            'total_additions' => $movements->whereIn('movement_type', ['ADD', 'RETURN', 'TRANSFER_IN'])->sum('qty_units'),
            'total_issues' => $movements->whereIn('movement_type', ['ISSUE', 'TRANSFER_OUT'])->sum('qty_units'),
            'total_returns' => $movements->where('movement_type', 'RETURN')->sum('qty_units'),
            'total_transfers_in' => $movements->where('movement_type', 'TRANSFER_IN')->sum('qty_units'),
            'total_transfers_out' => $movements->where('movement_type', 'TRANSFER_OUT')->sum('qty_units'),
            'movements_count' => $movements->count()
        ];
    }
    
    /**
     * الحصول على رصيد الافتتاح
     * 
     * @param int $productId
     * @param int $branchId
     * @param string|null $beforeDate
     * @return float
     */
    private function getOpeningBalance(int $productId, int $branchId, ?string $beforeDate = null): float
    {
        if (!$beforeDate) {
            return 0;
        }
        
        $movements = InventoryMovement::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('created_at', '<', $beforeDate)
            ->get();
            
        $balance = 0;
        foreach ($movements as $movement) {
            $balance += $this->calculateQuantityImpact($movement->movement_type, $movement->qty_units);
        }
        
        return $balance;
    }
    
    /**
     * حساب تأثير الكمية على الرصيد
     * 
     * @param string $movementType
     * @param float $quantity
     * @return float
     */
    private function calculateQuantityImpact(string $movementType, float $quantity): float
    {
        // IN movements (increase stock)
        if (in_array($movementType, ['ADD', 'RETURN', 'TRANSFER_IN'])) {
            return $quantity;
        }
        
        // OUT movements (decrease stock)
        if (in_array($movementType, ['ISSUE', 'TRANSFER_OUT'])) {
            return -$quantity;
        }
        
        return 0;
    }
    
    /**
     * التحقق من صحة بيانات الحركة
     * 
     * @param array $data
     * @throws \Exception
     */
    private function validateMovementData(array $data): void
    {
        $required = ['product_id', 'branch_id', 'movement_type', 'qty_units'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("الحقل {$field} مطلوب");
            }
        }
        
        if ($data['qty_units'] <= 0) {
            throw new \Exception("الكمية يجب أن تكون أكبر من صفر");
        }
        
        $validTypes = ['ADD', 'ISSUE', 'RETURN', 'TRANSFER_OUT', 'TRANSFER_IN'];
        if (!in_array($data['movement_type'], $validTypes)) {
            throw new \Exception("نوع الحركة غير صحيح");
        }
    }
}
