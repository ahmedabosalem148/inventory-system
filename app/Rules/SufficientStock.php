<?php

namespace App\Rules;

use App\Models\ProductBranch;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SufficientStock implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        private int $productId,
        private int $branchId,
        private ?int $excludeVoucherId = null
    ) {}

    /**
     * Run the validation rule.
     * 
     * التحقق من أن الكمية المطلوبة لا تتجاوز المخزون المتاح
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Get current stock for this product in this branch
        $productBranch = ProductBranch::where('product_id', $this->productId)
            ->where('branch_id', $this->branchId)
            ->first();

        // If no stock record exists, stock is 0
        $currentStock = $productBranch ? $productBranch->current_stock : 0;

        // Check if requested quantity exceeds available stock
        if ($value > $currentStock) {
            $fail("الكمية المطلوبة ({$value}) تتجاوز المخزون المتاح ({$currentStock})");
        }

        // Additional check: ensure stock won't go negative
        if ($currentStock - $value < 0) {
            $fail('لا يمكن إنشاء إذن صرف يؤدي إلى رصيد سالب');
        }
    }
}

