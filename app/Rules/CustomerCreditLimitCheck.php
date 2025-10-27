<?php

namespace App\Rules;

use App\Models\Customer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CustomerCreditLimitCheck implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        private int $customerId,
        private float $newAmount,
        private bool $blockIfExceeded = false
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $customer = Customer::find($this->customerId);
        
        if (!$customer) {
            return;
        }
        
        // If customer has no credit limit, allow all
        if (!$customer->credit_limit || $customer->credit_limit <= 0) {
            return;
        }
        
        // Calculate total balance (all unpaid invoices)
        $currentBalance = $customer->ledgerEntries()
            ->where('type', 'debit') // Sales (debits increase balance)
            ->sum('amount') -
            $customer->ledgerEntries()
            ->where('type', 'credit') // Payments (credits decrease balance)
            ->sum('amount');
        
        $newBalance = $currentBalance + $this->newAmount;
        
        // Check if new balance exceeds credit limit
        if ($newBalance > $customer->credit_limit) {
            $exceededAmount = $newBalance - $customer->credit_limit;
            
            if ($this->blockIfExceeded) {
                // Hard validation error - block the transaction
                $fail(
                    "رصيد العميل ({$newBalance}) سيتجاوز حد الائتمان ({$customer->credit_limit}) " .
                    "بمبلغ {$exceededAmount}. لا يمكن إتمام العملية."
                );
            } else {
                // Soft warning - allow but warn
                // This should be handled in the controller as a warning, not an error
                // We'll add a session warning here
                session()->push('validation.warnings', [
                    'field' => 'customer_id',
                    'customer_name' => $customer->name,
                    'current_balance' => $currentBalance,
                    'new_balance' => $newBalance,
                    'credit_limit' => $customer->credit_limit,
                    'exceeded_amount' => $exceededAmount,
                    'message' => "تحذير: رصيد العميل '{$customer->name}' ({$newBalance}) سيتجاوز حد الائتمان ({$customer->credit_limit}) بمبلغ {$exceededAmount}"
                ]);
            }
        }
    }
}
