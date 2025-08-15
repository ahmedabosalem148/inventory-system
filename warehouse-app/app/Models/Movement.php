<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movement model for tracking inventory movements
 * 
 * @property int $id
 * @property int $product_id
 * @property int $warehouse_id
 * @property string $type
 * @property int $quantity_units
 * @property string|null $note
 * @property string $created_by
 */
class Movement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'type',
        'quantity_units',
        'note',
        'created_by'
    ];

    protected $casts = [
        'product_id' => 'integer',
        'warehouse_id' => 'integer',
        'quantity_units' => 'integer'
    ];

    /**
     * Get the product for this movement
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse for this movement
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
