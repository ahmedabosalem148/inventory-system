<?php

namespace App\Services;

use App\Models\IssueVoucher;
use App\Models\InventoryMovement;
use App\Models\ProductBranchStock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * خدمة التحويلات بين الفروع
 * Transfer Service between branches
 * 
 * يقوم بإنشاء حركتي مخزون متزامنتين:
 * Creates two synchronized inventory movements:
 * 1. TRANSFER_OUT: خصم من الفرع المصدر
 * 2. TRANSFER_IN: إضافة للفرع المستهدف
 * 
 * @author Inventory System Team
 * @version 1.0
 */
class TransferService
{
    /**
     * إنشاء تحويل بين فرعين
     * Create transfer between two branches
     * 
     * @param IssueVoucher $voucher - إذن الصرف الذي يمثل التحويل
     * @param User $user - المستخدم الذي يعتمد التحويل
     * @return void
     * @throws \Exception
     */
    public function createTransfer(IssueVoucher $voucher, User $user): void
    {
        // التحقق من أن هذا إذن تحويل
        if (!$voucher->is_transfer) {
            throw new \Exception('هذا الإذن ليس إذن تحويل');
        }

        // التحقق من وجود فرع مستهدف
        if (!$voucher->target_branch_id) {
            throw new \Exception('يجب تحديد الفرع المستهدف');
        }

        // التحقق من عدم التحويل للنفس الفرع
        if ($voucher->branch_id === $voucher->target_branch_id) {
            throw new \Exception('لا يمكن التحويل من الفرع إلى نفسه');
        }

        // استخدام transaction لضمان تنفيذ العمليتين معاً أو رفضهما معاً
        DB::transaction(function () use ($voucher, $user) {
            foreach ($voucher->items as $item) {
                // 1. حركة الخصم من الفرع المصدر (TRANSFER_OUT)
                $this->createTransferOut($voucher, $item, $user);

                // 2. حركة الإضافة للفرع المستهدف (TRANSFER_IN)
                $this->createTransferIn($voucher, $item, $user);

                // تحديث أرصدة المخزون
                $this->updateStockBalances($voucher, $item);
            }

            Log::info('Transfer completed successfully', [
                'voucher_id' => $voucher->id,
                'voucher_number' => $voucher->voucher_number,
                'from_branch_id' => $voucher->branch_id,
                'to_branch_id' => $voucher->target_branch_id,
                'items_count' => $voucher->items->count(),
                'user_id' => $user->id,
            ]);
        });
    }

    /**
     * إنشاء حركة الخصم من الفرع المصدر
     * Create TRANSFER_OUT movement from source branch
     */
    protected function createTransferOut(IssueVoucher $voucher, $item, User $user): InventoryMovement
    {
        return InventoryMovement::create([
            'product_id' => $item->product_id,
            'branch_id' => $voucher->branch_id, // الفرع المصدر
            'movement_type' => 'TRANSFER_OUT',
            'qty_units' => -abs($item->quantity), // سالب للخصم
            'unit_price_snapshot' => $item->unit_price ?? 0,
            'ref_table' => 'issue_vouchers',
            'ref_id' => $voucher->id,
            'notes' => sprintf(
                'تحويل إلى فرع %s - إذن رقم %s',
                $voucher->targetBranch->name ?? 'غير محدد',
                $voucher->voucher_number
            ),
        ]);
    }

    /**
     * إنشاء حركة الإضافة للفرع المستهدف
     * Create TRANSFER_IN movement to target branch
     */
    protected function createTransferIn(IssueVoucher $voucher, $item, User $user): InventoryMovement
    {
        return InventoryMovement::create([
            'product_id' => $item->product_id,
            'branch_id' => $voucher->target_branch_id, // الفرع المستهدف
            'movement_type' => 'TRANSFER_IN',
            'qty_units' => abs($item->quantity), // موجب للإضافة
            'unit_price_snapshot' => $item->unit_price ?? 0,
            'ref_table' => 'issue_vouchers',
            'ref_id' => $voucher->id,
            'notes' => sprintf(
                'تحويل من فرع %s - إذن رقم %s',
                $voucher->branch->name ?? 'غير محدد',
                $voucher->voucher_number
            ),
        ]);
    }

    /**
     * تحديث أرصدة المخزون للفرعين
     * Update stock balances for both branches
     */
    protected function updateStockBalances(IssueVoucher $voucher, $item): void
    {
        // 1. خصم من الفرع المصدر
        $sourceStock = ProductBranchStock::firstOrCreate(
            [
                'product_id' => $item->product_id,
                'branch_id' => $voucher->branch_id,
            ],
            [
                'current_stock' => 0,
                'reserved_stock' => 0,
                'min_qty' => 0,
            ]
        );

        $sourceStock->decrement('current_stock', abs($item->quantity));

        // 2. إضافة للفرع المستهدف
        $targetStock = ProductBranchStock::firstOrCreate(
            [
                'product_id' => $item->product_id,
                'branch_id' => $voucher->target_branch_id,
            ],
            [
                'current_stock' => 0,
                'reserved_stock' => 0,
                'min_qty' => 0,
            ]
        );

        $targetStock->increment('current_stock', abs($item->quantity));

        Log::debug('Stock balances updated', [
            'product_id' => $item->product_id,
            'source_branch_id' => $voucher->branch_id,
            'source_new_qty' => $sourceStock->fresh()->current_stock,
            'target_branch_id' => $voucher->target_branch_id,
            'target_new_qty' => $targetStock->fresh()->current_stock,
            'transfer_qty' => abs($item->quantity),
        ]);
    }

    /**
     * الحصول على إحصائيات التحويلات بين الفروع
     * Get transfer statistics between branches
     * 
     * @param int|null $sourceBranchId - الفرع المصدر (اختياري)
     * @param int|null $targetBranchId - الفرع المستهدف (اختياري)
     * @param string|null $fromDate - من تاريخ (اختياري)
     * @param string|null $toDate - إلى تاريخ (اختياري)
     * @return array
     */
    public function getTransferStatistics(
        ?int $sourceBranchId = null,
        ?int $targetBranchId = null,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $query = IssueVoucher::query()
            ->where('is_transfer', true)
            ->where('status', 'completed');

        // تصفية بالفرع المصدر
        if ($sourceBranchId) {
            $query->where('branch_id', $sourceBranchId);
        }

        // تصفية بالفرع المستهدف
        if ($targetBranchId) {
            $query->where('target_branch_id', $targetBranchId);
        }

        // تصفية بالتاريخ
        if ($fromDate) {
            $query->where('issue_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('issue_date', '<=', $toDate);
        }

        $transfers = $query->with(['branch', 'targetBranch', 'items.product'])->get();

        return [
            'total_transfers' => $transfers->count(),
            'total_items_transferred' => $transfers->sum(fn($v) => $v->items->sum('quantity')),
            'total_value' => $transfers->sum('total_amount'),
            'by_source_branch' => $transfers->groupBy('branch_id')->map(function ($group) {
                return [
                    'branch_id' => $group->first()->branch_id,
                    'branch_name' => $group->first()->branch->name ?? 'غير محدد',
                    'count' => $group->count(),
                    'total_value' => $group->sum('total_amount'),
                ];
            })->values(),
            'by_target_branch' => $transfers->groupBy('target_branch_id')->map(function ($group) {
                return [
                    'branch_id' => $group->first()->target_branch_id,
                    'branch_name' => $group->first()->targetBranch->name ?? 'غير محدد',
                    'count' => $group->count(),
                    'total_value' => $group->sum('total_amount'),
                ];
            })->values(),
            'recent_transfers' => $transfers->take(10)->map(function ($voucher) {
                return [
                    'id' => $voucher->id,
                    'voucher_number' => $voucher->voucher_number,
                    'from_branch' => $voucher->branch->name ?? 'غير محدد',
                    'to_branch' => $voucher->targetBranch->name ?? 'غير محدد',
                    'issue_date' => $voucher->issue_date->format('Y-m-d'),
                    'total_amount' => $voucher->total_amount,
                    'items_count' => $voucher->items->count(),
                ];
            }),
        ];
    }
}
