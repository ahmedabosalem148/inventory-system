<?php

namespace App\Services;

use App\Models\ReturnVoucher;
use App\Models\InventoryMovement;
use App\Models\ProductBranchStock;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Return Service
 * 
 * Handles return voucher operations:
 * - Processing returns with inventory RETURN movements
 * - Updating stock balances (adds back to inventory)
 * - Integration with customer ledger (credit - له)
 * - Special numbering range (100001-125000)
 */
class ReturnService
{
    protected CustomerLedgerService $ledgerService;
    protected SequencerService $sequencerService;

    public function __construct(
        CustomerLedgerService $ledgerService,
        SequencerService $sequencerService
    ) {
        $this->ledgerService = $ledgerService;
        $this->sequencerService = $sequencerService;
    }

    /**
     * Process return voucher approval
     * Creates RETURN inventory movements and updates customer ledger
     *
     * @param ReturnVoucher $voucher
     * @param User $user
     * @return ReturnVoucher
     * @throws \Exception
     */
    public function processReturn(ReturnVoucher $voucher, User $user): ReturnVoucher
    {
        if ($voucher->isApproved()) {
            throw new \Exception('إذن الإرجاع معتمد بالفعل');
        }

        return DB::transaction(function () use ($voucher, $user) {
            // 1. Assign special return voucher number (100001-125000)
            $voucher->voucher_number = $this->sequencerService->getNextReturnNumber();
            
            // 2. Mark as approved
            $voucher->approved_at = now();
            $voucher->approved_by = $user->id;
            $voucher->status = 'completed';
            $voucher->save();

            // 3. Create inventory movements for each item (RETURN - adds to stock)
            foreach ($voucher->items as $item) {
                $this->createReturnMovement($voucher, $item, $user);
                $this->updateStockBalance($voucher, $item);
            }

            // 4. Update customer ledger (credit - له - reduces customer debt)
            if ($voucher->customer_id) {
                $this->ledgerService->addEntry(
                    customerId: $voucher->customer_id,
                    description: "ارتجاع رقم {$voucher->voucher_number}",
                    debitAliah: 0,
                    creditLah: $voucher->total_amount,
                    refTable: 'return_vouchers',
                    refId: $voucher->id,
                    notes: $voucher->reason ?? 'ارتجاع بضاعة',
                    createdBy: $user->id
                );
            }

            return $voucher->fresh(['items.product', 'customer', 'branch', 'approver']);
        });
    }

    /**
     * Create RETURN inventory movement (adds to stock)
     * الآن يستخدم InventoryMovementService للتسجيل الصحيح
     *
     * @param ReturnVoucher $voucher
     * @param \App\Models\ReturnVoucherItem $item
     * @param User $user
     * @return InventoryMovement
     */
    protected function createReturnMovement(
        ReturnVoucher $voucher,
        $item,
        User $user
    ): InventoryMovement {
        $movementService = app(\App\Services\InventoryMovementService::class);
        
        return $movementService->recordReturn(
            productId: $item->product_id,
            branchId: $voucher->branch_id,
            quantity: $item->quantity,
            unitPrice: $item->unit_price,
            returnVoucherId: $voucher->id,
            notes: "ارتجاع رقم {$voucher->voucher_number}" . 
                   ($voucher->reason ? " - {$voucher->reason}" : '')
        );
    }

    /**
     * Update product branch stock balance - الآن يتم التحديث من خلال InventoryMovementService
     * هذه الميثود لم تعد مطلوبة لأن recordReturn() يحدث المخزون تلقائياً
     *
     * @param ReturnVoucher $voucher
     * @param \App\Models\ReturnVoucherItem $item
     * @return void
     * @deprecated استخدم InventoryMovementService::recordReturn() بدلاً منه
     */
    protected function updateStockBalance(ReturnVoucher $voucher, $item): void
    {
        // لا حاجة لهذا الكود - InventoryMovementService يحدث المخزون تلقائياً
        // نبقي الميثود للتوافق مع الكود القديم
        return;
    }

    /**
     * Get return statistics for a branch or customer
     *
     * @param int|null $branchId
     * @param int|null $customerId
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return array
     */
    public function getReturnStatistics(
        ?int $branchId = null,
        ?int $customerId = null,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $query = ReturnVoucher::query()
            ->where('status', 'completed')
            ->whereNotNull('approved_at');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($fromDate) {
            $query->whereDate('return_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('return_date', '<=', $toDate);
        }

        $returns = $query->get();

        return [
            'total_returns' => $returns->count(),
            'total_amount' => $returns->sum('total_amount'),
            'average_return_value' => $returns->avg('total_amount'),
            'returns_by_branch' => $returns->groupBy('branch_id')->map->count(),
            'returns_by_customer' => $returns->groupBy('customer_id')->map->count(),
            'recent_returns' => $returns->sortByDesc('return_date')->take(10)->values(),
        ];
    }

    /**
     * Get most returned products
     *
     * @param int|null $branchId
     * @param string|null $fromDate
     * @param string|null $toDate
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getMostReturnedProducts(
        ?int $branchId = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $limit = 10
    ) {
        $query = DB::table('return_voucher_items')
            ->join('return_vouchers', 'return_voucher_items.return_voucher_id', '=', 'return_vouchers.id')
            ->join('products', 'return_voucher_items.product_id', '=', 'products.id')
            ->where('return_vouchers.status', 'completed')
            ->whereNotNull('return_vouchers.approved_at');

        if ($branchId) {
            $query->where('return_vouchers.branch_id', $branchId);
        }

        if ($fromDate) {
            $query->whereDate('return_vouchers.return_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('return_vouchers.return_date', '<=', $toDate);
        }

        return $query
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(return_voucher_items.quantity) as total_returned'),
                DB::raw('COUNT(DISTINCT return_vouchers.id) as return_count'),
                DB::raw('SUM(return_voucher_items.total_price) as total_value')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_returned')
            ->limit($limit)
            ->get();
    }

    /**
     * Validate return voucher before processing
     *
     * @param array $items
     * @return array
     */
    public function validateReturn(array $items): array
    {
        $errors = [];

        foreach ($items as $index => $item) {
            // Check if product exists
            $product = \App\Models\Product::find($item['product_id'] ?? null);
            if (!$product) {
                $errors["item_{$index}"] = "المنتج غير موجود";
                continue;
            }

            // Check quantity
            if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                $errors["item_{$index}_quantity"] = "الكمية يجب أن تكون أكبر من صفر";
            }

            // Check unit price
            if (!isset($item['unit_price']) || $item['unit_price'] < 0) {
                $errors["item_{$index}_price"] = "السعر يجب أن يكون صفر أو أكثر";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Cancel a return voucher (if not yet approved)
     *
     * @param ReturnVoucher $voucher
     * @param User $user
     * @param string $reason
     * @return ReturnVoucher
     * @throws \Exception
     */
    public function cancelReturn(ReturnVoucher $voucher, User $user, string $reason): ReturnVoucher
    {
        if ($voucher->isApproved()) {
            throw new \Exception('لا يمكن إلغاء إذن مرتجع معتمد بالفعل');
        }

        $voucher->status = 'cancelled';
        $voucher->notes = ($voucher->notes ?? '') . "\n[ملغى بواسطة {$user->name}]: {$reason}";
        $voucher->save();

        return $voucher;
    }
}
