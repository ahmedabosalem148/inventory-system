<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'name_ar',
        'carton_size',
        'active'
    ];

    protected $casts = [
        'active' => 'boolean',
        'carton_size' => 'integer'
    ];

    /**
     * Get the product name (accessor for backwards compatibility)
     */
    public function getNameAttribute()
    {
        return $this->name_ar;
    }

    /**
     * Get units per carton (accessor for backwards compatibility)
     */
    public function getUnitsPerCartonAttribute()
    {
        return $this->carton_size;
    }

    /**
     * Get warehouse inventory records for this product
     */
    public function warehouseInventory(): HasMany
    {
        return $this->hasMany(WarehouseInventory::class);
    }

    /**
     * Get warehouses for this product with inventory data
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_inventory')
                    ->withPivot(['closed_cartons', 'loose_units', 'min_threshold'])
                    ->withTimestamps();
    }

    /**
     * Get movements for this product across all warehouses
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Get movements through warehouse inventory (optional)
     */
    public function warehouseMovements(): HasManyThrough
    {
        return $this->hasManyThrough(
            InventoryMovement::class,
            WarehouseInventory::class,
            'product_id',
            'product_id',
            'id',
            'product_id'
        );
    }
}
