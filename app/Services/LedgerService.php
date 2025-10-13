<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Record a debit entry (customer owes money)
     *
     * @param int $customerId
     * @param float $amount
     * @param string $description
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @return LedgerEntry
     * @throws \InvalidArgumentException
     */
    public function recordDebit(
        int $customerId,
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): LedgerEntry {
        $this->validateAmount($amount);
        $this->validateCustomerExists($customerId);

        return LedgerEntry::create([
            'customer_id' => $customerId,
            'type' => 'debit',
            'amount' => $amount,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    /**
     * Record a credit entry (customer paid or received credit)
     *
     * @param int $customerId
     * @param float $amount
     * @param string $description
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @return LedgerEntry
     * @throws \InvalidArgumentException
     */
    public function recordCredit(
        int $customerId,
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): LedgerEntry {
        $this->validateAmount($amount);
        $this->validateCustomerExists($customerId);

        return LedgerEntry::create([
            'customer_id' => $customerId,
            'type' => 'credit',
            'amount' => $amount,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    /**
     * Get customer balance (debits - credits)
     *
     * @param int $customerId
     * @return float
     */
    public function getCustomerBalance(int $customerId): float
    {
        $debits = LedgerEntry::where('customer_id', $customerId)
            ->where('type', 'debit')
            ->sum('amount');

        $credits = LedgerEntry::where('customer_id', $customerId)
            ->where('type', 'credit')
            ->sum('amount');

        return $debits - $credits;
    }

    /**
     * Get ledger entries for customer
     *
     * @param int $customerId
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomerLedger(
        int $customerId,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ) {
        $query = LedgerEntry::where('customer_id', $customerId)
            ->orderBy('created_at', 'desc');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Get ledger entries with running balance
     *
     * @param int $customerId
     * @return array
     */
    public function getCustomerLedgerWithBalance(int $customerId): array
    {
        $entries = LedgerEntry::where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();

        $balance = 0;
        $result = [];

        // Process in reverse to calculate running balance
        foreach ($entries->reverse() as $entry) {
            if ($entry->type === 'debit') {
                $balance += $entry->amount;
            } else {
                $balance -= $entry->amount;
            }

            $result[] = [
                'entry' => $entry,
                'balance' => $balance,
            ];
        }

        // Reverse back to show latest first
        return array_reverse($result);
    }

    /**
     * Get customers with outstanding balance
     *
     * @param float $minBalance Minimum balance to include (default: 0.01)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomersWithOutstandingBalance(float $minBalance = 0.01)
    {
        // Get all customers with ledger entries
        $customerIds = LedgerEntry::distinct('customer_id')->pluck('customer_id');

        return Customer::whereIn('id', $customerIds)
            ->get()
            ->filter(function ($customer) use ($minBalance) {
                $balance = $this->getCustomerBalance($customer->id);
                return $balance >= $minBalance;
            });
    }

    /**
     * Validate amount is positive
     *
     * @param float $amount
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
    }

    /**
     * Validate customer exists
     *
     * @param int $customerId
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function validateCustomerExists(int $customerId): void
    {
        Customer::findOrFail($customerId);
    }

    /**
     * Get total outstanding balance across all customers
     *
     * @return float
     */
    public function getTotalOutstandingBalance(): float
    {
        $debits = LedgerEntry::where('type', 'debit')->sum('amount');
        $credits = LedgerEntry::where('type', 'credit')->sum('amount');

        return $debits - $credits;
    }
}
