<?php

namespace App\Services;

use App\Models\Sequence;
use Illuminate\Support\Facades\DB;

class SequencerService
{
    /**
     * Configure sequence settings for entity type
     *
     * @param string $entityType
     * @param array $config
     * @return void
     */
    public static function configure(string $entityType, array $config): void
    {
        $year = $config['year'] ?? now()->year;
        
        Sequence::updateOrCreate(
            [
                'entity_type' => $entityType,
                'year' => $year,
            ],
            [
                'prefix' => $config['prefix'] ?? null,
                'last_number' => $config['current_value'] ?? 0,
                'min_value' => $config['min_value'] ?? 1,
                'max_value' => $config['max_value'] ?? 999999,
                'increment_by' => $config['increment_by'] ?? 1,
                'auto_reset' => $config['auto_reset'] ?? true,
            ]
        );
    }

    /**
     * Get next sequence number for entity type
     *
     * @param string $entityType (issue_vouchers, return_vouchers, transfer_vouchers, payments, customers, cheques)
     * @param int|null $year
     * @return string (format: PREFIX-2025/00001 or 2025/1)
     */
    public function getNextSequence(string $entityType, ?int $year = null): string
    {
        $year = $year ?? now()->year;

        // Use database transaction with row locking to prevent race conditions
        return DB::transaction(function () use ($entityType, $year) {
            // Get sequence record with exclusive lock
            $sequence = Sequence::where('entity_type', $entityType)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                throw new \RuntimeException("Sequence not configured for {$entityType} in year {$year}. Please run seeder.");
            }

            // Calculate next number
            $nextNumber = $sequence->last_number + $sequence->increment_by;

            // Check if we've reached the maximum
            if ($nextNumber > $sequence->max_value) {
                throw new \RuntimeException("Sequence limit reached for {$entityType} in year {$year}. Max: {$sequence->max_value}");
            }

            // Check if we're below minimum (for return vouchers special case)
            if ($nextNumber < $sequence->min_value) {
                $nextNumber = $sequence->min_value;
            }

            // Update the sequence
            $sequence->update(['last_number' => $nextNumber]);

            // Format the number with prefix if configured
            $formattedNumber = $sequence->prefix 
                ? "{$sequence->prefix}{$year}/" . str_pad($nextNumber, 5, '0', STR_PAD_LEFT)
                : "{$year}/{$nextNumber}";

            return $formattedNumber;
        });
    }

    /**
     * Get next return voucher number (special handling for 100001-125000 range)
     *
     * @param int|null $year
     * @return string
     */
    public function getNextReturnNumber(?int $year = null): string
    {
        return $this->getNextSequence('return_vouchers', $year);
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
     * Validate if a number is within the allowed range for entity type
     *
     * @param string $entityType
     * @param int $value
     * @param int|null $year
     * @return bool
     */
    public function validateRange(string $entityType, int $value, ?int $year = null): bool
    {
        $year = $year ?? now()->year;
        
        $sequence = Sequence::where('entity_type', $entityType)
            ->where('year', $year)
            ->first();

        if (!$sequence) {
            return false;
        }

        return $value >= $sequence->min_value && $value <= $sequence->max_value;
    }

    /**
     * Get sequence configuration for entity type
     *
     * @param string $entityType
     * @param int|null $year
     * @return array|null
     */
    public function getSequenceConfig(string $entityType, ?int $year = null): ?array
    {
        $year = $year ?? now()->year;
        
        $sequence = Sequence::where('entity_type', $entityType)
            ->where('year', $year)
            ->first();

        if (!$sequence) {
            return null;
        }

        return [
            'entity_type' => $sequence->entity_type,
            'year' => $sequence->year,
            'prefix' => $sequence->prefix,
            'current_value' => $sequence->last_number,
            'min_value' => $sequence->min_value,
            'max_value' => $sequence->max_value,
            'increment_by' => $sequence->increment_by,
            'auto_reset' => $sequence->auto_reset,
            'remaining' => $sequence->max_value - $sequence->last_number,
        ];
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
        $sequence = Sequence::where('entity_type', $entityType)
            ->where('year', $year - 1)
            ->first();

        if ($sequence && $sequence->auto_reset) {
            // Create new sequence for the new year based on previous config
            self::configure($entityType, [
                'year' => $year,
                'prefix' => $sequence->prefix,
                'current_value' => $sequence->min_value - $sequence->increment_by, // Will start at min_value
                'min_value' => $sequence->min_value,
                'max_value' => $sequence->max_value,
                'increment_by' => $sequence->increment_by,
                'auto_reset' => $sequence->auto_reset,
            ]);
        }
    }
}
