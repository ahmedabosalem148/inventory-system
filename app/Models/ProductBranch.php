<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBranch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_branch_stock';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'branch_id',
        'current_stock',
        'reserved_stock',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_stock' => 'integer',
        'reserved_stock' => 'integer',
    ];

    /**
     * Get the product that owns the stock record.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the branch that owns the stock record.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get available stock (current - reserved).
     *
     * @return int
     */
    public function getAvailableStockAttribute(): int
    {
        return $this->current_stock - $this->reserved_stock;
    }

    /**
     * Backward compatibility: map 'quantity' to 'current_stock'
     */
    public function getQuantityAttribute(): int
    {
        return $this->current_stock;
    }

    public function setQuantityAttribute($value): void
    {
        $this->attributes['current_stock'] = $value;
    }
}
