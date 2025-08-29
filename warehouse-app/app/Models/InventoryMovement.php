<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'movement_type',
        'quantity',
        'cartons',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'cartons' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Movement types
     */
    const TYPE_ADD = 'add';
    const TYPE_WITHDRAW = 'withdraw';
    const TYPE_ADJUST = 'adjust';

    /**
     * Get the product that owns the movement.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse that owns the movement.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Scope for add movements
     */
    public function scopeAdditions($query)
    {
        return $query->where('movement_type', self::TYPE_ADD);
    }

    /**
     * Scope for withdrawal movements
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('movement_type', self::TYPE_WITHDRAW);
    }

    /**
     * Scope for adjustments
     */
    public function scopeAdjustments($query)
    {
        return $query->where('movement_type', self::TYPE_ADJUST);
    }

    /**
     * Get formatted movement type
     */
    public function getFormattedTypeAttribute()
    {
        return match($this->movement_type) {
            self::TYPE_ADD => 'إضافة',
            self::TYPE_WITHDRAW => 'سحب',
            self::TYPE_ADJUST => 'تعديل',
            default => $this->movement_type
        };
    }
}
