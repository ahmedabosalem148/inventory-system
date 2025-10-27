<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidSkuFormat implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // SKU must contain only alphanumeric characters and hyphens
        // Must start with alphanumeric, can't end with hyphen
        // Format: ABC-123, PROD-001, SKU123, etc.
        
        $pattern = '/^[A-Z0-9]+([A-Z0-9-]*[A-Z0-9]+)?$/i';
        
        if (!preg_match($pattern, $value)) {
            $fail('رمز المنتج (SKU) يجب أن يحتوي فقط على حروف وأرقام وشرطات، ولا يمكن أن يبدأ أو ينتهي بشرطة');
        }
        
        // Check for consecutive hyphens
        if (str_contains($value, '--')) {
            $fail('رمز المنتج (SKU) لا يمكن أن يحتوي على شرطات متتالية');
        }
        
        // Minimum length check
        if (strlen($value) < 2) {
            $fail('رمز المنتج (SKU) يجب أن يكون على الأقل حرفين');
        }
    }
}
