<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxDiscountValue implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        private string $discountType,
        private float $totalAmount
    ) {}

    /**
     * Run the validation rule.
     * 
     * التحقق من أن قيمة الخصم لا تتجاوز المبلغ الإجمالي
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If discount type is 'none' or null, no validation needed
        if (empty($this->discountType) || $this->discountType === 'none') {
            return;
        }

        // Convert value to float
        $discountValue = (float) $value;

        // Validate based on discount type
        if ($this->discountType === 'fixed') {
            // Fixed discount cannot exceed total amount
            if ($discountValue > $this->totalAmount) {
                $fail("الخصم الثابت ({$discountValue}) لا يمكن أن يتجاوز الإجمالي ({$this->totalAmount})");
            }

            // Fixed discount cannot make total negative
            if ($this->totalAmount - $discountValue < 0) {
                $fail('الخصم لا يمكن أن يجعل الإجمالي سالباً');
            }
        } elseif ($this->discountType === 'percentage') {
            // Percentage discount must be between 0 and 100
            if ($discountValue < 0 || $discountValue > 100) {
                $fail('نسبة الخصم يجب أن تكون بين 0 و 100');
            }
        }
    }
}
