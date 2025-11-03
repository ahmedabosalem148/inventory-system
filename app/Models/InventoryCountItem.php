<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCountItem extends Model
{
    protected $fillable = [
        'inventory_count_id',
        'product_id',
        'system_quantity',
        'physical_quantity',
        'difference',
        'notes',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:3',
        'physical_quantity' => 'decimal:3',
        'difference' => 'decimal:3',
    ];

    /**
     * Get the parent count
     */
    public function inventoryCount(): BelongsTo
    {
        return $this->belongsTo(InventoryCount::class);
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate difference automatically
     */
    protected static function booted()
    {
        static::saving(function ($item) {
            $item->difference = $item->physical_quantity - $item->system_quantity;
        });
    }

    /**
     * Check if there's a shortage
     */
    public function hasShortage(): bool
    {
        return $this->difference < 0;
    }

    /**
     * Check if there's an excess
     */
    public function hasExcess(): bool
    {
        return $this->difference > 0;
    }

    /**
     * Get absolute difference
     */
    public function getAbsDifference(): float
    {
        return abs($this->difference);
    }
}
