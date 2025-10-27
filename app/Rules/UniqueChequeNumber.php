<?php

namespace App\Rules;

use App\Models\Cheque;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueChequeNumber implements ValidationRule
{
    public function __construct(
        private string $bankName,
        private ?int $excludeChequeId = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if cheque number exists for the same bank
        $query = Cheque::where('cheque_number', $value)
                      ->where('bank_name', $this->bankName);
        
        if ($this->excludeChequeId) {
            $query->where('id', '!=', $this->excludeChequeId);
        }
        
        if ($query->exists()) {
            $fail("رقم الشيك ({$value}) مستخدم بالفعل لنفس البنك ({$this->bankName})");
        }
    }
}
