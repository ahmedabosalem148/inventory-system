<?php

namespace App\Services;

use App\Models\ProductBranchStock;
use App\Models\Product;
use App\Models\Branch;
use Illuminate\Support\Collection;

/**
 * خدمة التحقق من المخزون
 * Stock Validation Service
 * 
 * يتحقق من توفر المخزون قبل اعتماد أذون الصرف والتحويلات
 * Validates stock availability before approving issue vouchers and transfers
 * 
 * @author Inventory System Team
 * @version 1.0
 */
class StockValidationService
{
    /**
     * التحقق من توفر المخزون لصنف واحد
     * Validate stock availability for a single item
     * 
     * @param int $productId - معرف المنتج
     * @param int $branchId - معرف الفرع
     * @param int $requestedQty - الكمية المطلوبة
     * @return array ['valid' => bool, 'available' => int, 'shortage' => int, 'message' => string]
     */
    public function validateSingleItem(int $productId, int $branchId, int $requestedQty): array
    {
        // الحصول على الرصيد الحالي
        $stock = ProductBranchStock::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        $availableQty = $stock ? $stock->current_stock : 0;

        // التحقق من الكمية
        if ($availableQty >= $requestedQty) {
            return [
                'valid' => true,
                'product_id' => $productId,
                'branch_id' => $branchId,
                'available' => $availableQty,
                'requested' => $requestedQty,
                'shortage' => 0,
                'message' => 'المخزون كافي',
            ];
        }

        // حساب النقص
        $shortage = $requestedQty - $availableQty;

        $product = Product::find($productId);
        $branch = Branch::find($branchId);

        return [
            'valid' => false,
            'product_id' => $productId,
            'branch_id' => $branchId,
            'product_name' => $product?->name ?? 'غير معروف',
            'branch_name' => $branch?->name ?? 'غير معروف',
            'available' => $availableQty,
            'requested' => $requestedQty,
            'shortage' => $shortage,
            'message' => sprintf(
                'المخزون غير كافي للصنف "%s" في فرع "%s". متوفر: %d، مطلوب: %d، النقص: %d',
                $product?->name ?? 'غير معروف',
                $branch?->name ?? 'غير معروف',
                $availableQty,
                $requestedQty,
                $shortage
            ),
        ];
    }

    /**
     * التحقق من توفر المخزون لمجموعة أصناف
     * Validate stock availability for multiple items
     * 
     * @param array $items - [['product_id' => 1, 'quantity' => 10], ...]
     * @param int $branchId - معرف الفرع
     * @return array ['valid' => bool, 'items' => [...], 'messages' => [...]]
     */
    public function validateMultipleItems(array $items, int $branchId): array
    {
        $results = [];
        $allValid = true;
        $messages = [];

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 0;

            if (!$productId || $quantity <= 0) {
                continue;
            }

            $result = $this->validateSingleItem($productId, $branchId, $quantity);
            $results[] = $result;

            if (!$result['valid']) {
                $allValid = false;
                $messages[] = $result['message'];
            }
        }

        return [
            'valid' => $allValid,
            'items' => $results,
            'messages' => $messages,
            'invalid_count' => count($messages),
        ];
    }

    /**
     * الحصول على اقتراحات بديلة عند نقص المخزون
     * Get alternative suggestions when stock is insufficient
     * 
     * @param int $productId - معرف المنتج
     * @param int $currentBranchId - الفرع الحالي
     * @param int $requestedQty - الكمية المطلوبة
     * @return array
     */
    public function getSuggestions(int $productId, int $currentBranchId, int $requestedQty): array
    {
        // البحث عن الفروع الأخرى التي لديها المخزون
        $otherBranches = ProductBranchStock::where('product_id', $productId)
            ->where('branch_id', '!=', $currentBranchId)
            ->where('current_stock', '>=', $requestedQty)
            ->with('branch')
            ->get();

        $suggestions = [];

        // اقتراح 1: التحويل من فرع آخر
        if ($otherBranches->isNotEmpty()) {
            $suggestions[] = [
                'type' => 'transfer',
                'message' => 'يمكن التحويل من الفروع التالية:',
                'branches' => $otherBranches->map(function ($stock) use ($requestedQty) {
                    return [
                        'branch_id' => $stock->branch_id,
                        'branch_name' => $stock->branch->name ?? 'غير معروف',
                        'available' => $stock->current_stock,
                        'can_fulfill' => $stock->current_stock >= $requestedQty,
                    ];
                })->toArray(),
            ];
        }

        // اقتراح 2: تقسيم الطلب على عدة فروع
        $allBranches = ProductBranchStock::where('product_id', $productId)
            ->where('branch_id', '!=', $currentBranchId)
            ->where('current_stock', '>', 0)
            ->with('branch')
            ->get();

        $totalAvailable = $allBranches->sum('current_stock');

        if ($totalAvailable >= $requestedQty && $allBranches->count() > 1) {
            $suggestions[] = [
                'type' => 'split',
                'message' => 'يمكن تقسيم الطلب على عدة فروع:',
                'total_available' => $totalAvailable,
                'branches' => $allBranches->map(function ($stock) {
                    return [
                        'branch_id' => $stock->branch_id,
                        'branch_name' => $stock->branch->name ?? 'غير معروف',
                        'available' => $stock->current_stock,
                    ];
                })->toArray(),
            ];
        }

        // اقتراح 3: تقليل الكمية
        $currentStock = ProductBranchStock::where('product_id', $productId)
            ->where('branch_id', $currentBranchId)
            ->first();

        if ($currentStock && $currentStock->current_stock > 0) {
            $suggestions[] = [
                'type' => 'reduce',
                'message' => 'يمكن تقليل الكمية المطلوبة',
                'max_available' => $currentStock->current_stock,
                'requested' => $requestedQty,
                'shortage' => $requestedQty - $currentStock->current_stock,
            ];
        }

        // اقتراح 4: طلب توريد
        if (empty($suggestions)) {
            $suggestions[] = [
                'type' => 'purchase',
                'message' => 'لا يوجد مخزون كافي في أي فرع. يرجى إنشاء طلب توريد.',
                'required_qty' => $requestedQty,
            ];
        }

        return $suggestions;
    }

    /**
     * التحقق من إمكانية صرف إذن كامل
     * Validate if a complete voucher can be issued
     * 
     * @param Collection $items - مجموعة أصناف الإذن
     * @param int $branchId - معرف الفرع
     * @return array ['can_issue' => bool, 'validation' => array, 'suggestions' => array]
     */
    public function canIssueVoucher(Collection $items, int $branchId): array
    {
        $itemsArray = $items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ];
        })->toArray();

        $validation = $this->validateMultipleItems($itemsArray, $branchId);

        // إذا كان كل شيء صحيح
        if ($validation['valid']) {
            return [
                'can_issue' => true,
                'validation' => $validation,
                'suggestions' => [],
                'message' => 'يمكن اعتماد الإذن - المخزون كافي لجميع الأصناف',
            ];
        }

        // الحصول على اقتراحات للأصناف الناقصة
        $suggestions = [];
        foreach ($validation['items'] as $item) {
            if (!$item['valid']) {
                $suggestions[$item['product_id']] = $this->getSuggestions(
                    $item['product_id'],
                    $branchId,
                    $item['requested']
                );
            }
        }

        return [
            'can_issue' => false,
            'validation' => $validation,
            'suggestions' => $suggestions,
            'message' => sprintf(
                'لا يمكن اعتماد الإذن - %d صنف غير متوفر بالكمية المطلوبة',
                $validation['invalid_count']
            ),
        ];
    }

    /**
     * التحقق من إمكانية تحويل بين فرعين
     * Validate if a transfer between branches is possible
     * 
     * @param Collection $items - مجموعة الأصناف
     * @param int $sourceBranchId - الفرع المصدر
     * @param int $targetBranchId - الفرع المستهدف
     * @return array
     */
    public function canTransfer(Collection $items, int $sourceBranchId, int $targetBranchId): array
    {
        // التحقق من أن الفرعين مختلفين
        if ($sourceBranchId === $targetBranchId) {
            return [
                'can_transfer' => false,
                'validation' => null,
                'message' => 'لا يمكن التحويل من الفرع إلى نفسه',
            ];
        }

        // التحقق من توفر المخزون في الفرع المصدر
        $itemsArray = $items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
            ];
        })->toArray();

        $validation = $this->validateMultipleItems($itemsArray, $sourceBranchId);

        if ($validation['valid']) {
            return [
                'can_transfer' => true,
                'validation' => $validation,
                'message' => 'يمكن إجراء التحويل - المخزون كافي في الفرع المصدر',
            ];
        }

        return [
            'can_transfer' => false,
            'validation' => $validation,
            'message' => sprintf(
                'لا يمكن إجراء التحويل - %d صنف غير متوفر بالكمية المطلوبة في الفرع المصدر',
                $validation['invalid_count']
            ),
        ];
    }

    /**
     * الحصول على تقرير بالأصناف المنخفضة في فرع
     * Get report of low stock items in a branch
     * 
     * @param int $branchId - معرف الفرع
     * @return Collection
     */
    public function getLowStockItems(int $branchId): Collection
    {
        return ProductBranchStock::where('branch_id', $branchId)
            ->whereColumn('current_stock', '<=', 'min_qty')
            ->where('min_qty', '>', 0)
            ->with(['product', 'branch'])
            ->get()
            ->map(function ($stock) {
                return [
                    'product_id' => $stock->product_id,
                    'product_name' => $stock->product->name ?? 'غير معروف',
                    'branch_id' => $stock->branch_id,
                    'branch_name' => $stock->branch->name ?? 'غير معروف',
                    'current_stock' => $stock->current_stock,
                    'min_qty' => $stock->min_qty,
                    'shortage' => max(0, $stock->min_qty - $stock->current_stock),
                    'status' => $stock->current_stock == 0 ? 'نفذ' : 'منخفض',
                ];
            });
    }

    /**
     * الحصول على تقرير بالأصناف الصفرية في فرع
     * Get report of out-of-stock items in a branch
     * 
     * @param int $branchId - معرف الفرع
     * @return Collection
     */
    public function getOutOfStockItems(int $branchId): Collection
    {
        return ProductBranchStock::where('branch_id', $branchId)
            ->where('current_stock', '<=', 0)
            ->with(['product', 'branch'])
            ->get()
            ->map(function ($stock) {
                return [
                    'product_id' => $stock->product_id,
                    'product_name' => $stock->product->name ?? 'غير معروف',
                    'branch_id' => $stock->branch_id,
                    'branch_name' => $stock->branch->name ?? 'غير معروف',
                    'min_qty' => $stock->min_qty,
                ];
            });
    }
}
