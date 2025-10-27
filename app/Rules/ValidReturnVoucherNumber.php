<?php

namespace App\Rules;

use App\Models\ReturnVoucher;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidReturnVoucherNumber implements ValidationRule
{
    public function __construct(
        private int $branchId,
        private ?int $excludeVoucherId = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if voucher number already exists
        $query = ReturnVoucher::where('voucher_number', $value);
        
        if ($this->excludeVoucherId) {
            $query->where('id', '!=', $this->excludeVoucherId);
        }
        
        $existingVoucher = $query->first();
        
        if ($existingVoucher) {
            // Check if it belongs to a different branch
            if ($existingVoucher->branch_id != $this->branchId) {
                $fail("رقم الإذن ({$value}) مستخدم بالفعل في فرع آخر");
            } else {
                $fail("رقم الإذن ({$value}) مستخدم بالفعل");
            }
        }
        
        // Validate format: RV-XXXXXX (6 digits)
        if (!preg_match('/^RV-\d{6}$/', $value)) {
            $fail("رقم الإذن يجب أن يكون بالصيغة RV-XXXXXX (مثال: RV-000001)");
        }
    }
}
