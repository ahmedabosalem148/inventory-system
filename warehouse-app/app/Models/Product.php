<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Product model for warehouse management
 * 
 * @property int $id
 * @property string $name
 * @property int $carton_size
 * @property bool $active
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'carton_size',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
        'carton_size' => 'integer'
    ];

    /**
     * Get warehouse inventory records for this product
     */
    public function warehouseInventory(): HasMany
    {
        return $this->hasMany(WarehouseInventory::class);
    }

    /**
     * Get movements for this product across all warehouses
     */
    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    /**
     * Get movements through warehouse inventory (optional)
     */
    public function warehouseMovements(): HasManyThrough
    {
        return $this->hasManyThrough(
            Movement::class,
            WarehouseInventory::class,
            'product_id',
            'product_id',
            'id',
            'product_id'
        );
    }
}
