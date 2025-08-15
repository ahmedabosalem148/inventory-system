<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WarehouseInventory model
 * 
 * @property int $id
 * @property int $product_id
 * @property int $warehouse_id
 * @property int $closed_cartons
 * @property int $loose_units
 * @property int $min_threshold
 * @property int $version
 */
class WarehouseInventory extends Model
{
    use HasFactory;

    protected $table = 'warehouse_inventory';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'closed_cartons',
        'loose_units',
        'min_threshold',
        'version'
    ];

    protected $casts = [
        'product_id' => 'integer',
        'warehouse_id' => 'integer',
        'closed_cartons' => 'integer',
        'loose_units' => 'integer',
        'min_threshold' => 'integer',
        'version' => 'integer'
    ];

    /**
     * Get the product for this inventory record
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse for this inventory record
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get total units available (closed cartons + loose units)
     */
    public function getTotalUnitsAttribute(): int
    {
        return ($this->closed_cartons * $this->product->carton_size) + $this->loose_units;
    }
}
