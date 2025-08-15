<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Warehouse model
 * 
 * @property int $id
 * @property string $name
 * @property string|null $password
 */
class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'password'
    ];

    protected $hidden = [
        'password'
    ];

    /**
     * Get inventory records for this warehouse
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(WarehouseInventory::class);
    }

    /**
     * Get movements for this warehouse
     */
    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }
}
