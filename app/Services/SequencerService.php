<?php

namespace App\Services;

use App\Models\Sequence;
use Illuminate\Support\Facades\DB;

class SequencerService
{
    /**
     * Get next sequence number for entity type
     *
     * @param string $entityType (issue_vouchers, return_vouchers, transfer_vouchers, payments, customers, cheques)
     * @param int|null $year
     * @return string (format: 2025/1)
     */
    public function getNextSequence(string $entityType, ?int $year = null): string
    {
        $year = $year ?? now()->year;

        // Use database transaction with row locking to prevent race conditions
        return DB::transaction(function () use ($entityType, $year) {
            // Get or create sequence record with exclusive lock
            $sequence = Sequence::where('entity_type', $entityType)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                // Create new sequence for this year
                $sequence = Sequence::create([
                    'entity_type' => $entityType,
                    'year' => $year,
                    'last_number' => 1,
                ]);

                return "{$year}/1";
            }

            // Check if we've reached the maximum
            if ($sequence->last_number >= 999999) {
                throw new \RuntimeException("Sequence limit reached for {$entityType} in year {$year}");
            }

            // Increment the sequence
            $nextNumber = $sequence->last_number + 1;
            $sequence->update(['last_number' => $nextNumber]);

            return "{$year}/{$nextNumber}";
        });
    }

    /**
     * Get current sequence number without incrementing
     *
     * @param string $entityType
     * @param int|null $year
     * @return string|null
     */
    public function getCurrentSequence(string $entityType, ?int $year = null): ?string
    {
        $year = $year ?? now()->year;

        $sequence = Sequence::where('entity_type', $entityType)
            ->where('year', $year)
            ->first();

        return $sequence ? "{$year}/{$sequence->last_number}" : null;
    }

    /**
     * Reset sequence for new year (optional maintenance)
     *
     * @param string $entityType
     * @param int $year
     * @return void
     */
    public function resetSequence(string $entityType, int $year): void
    {
        Sequence::where('entity_type', $entityType)
            ->where('year', $year)
            ->delete();
    }
}
