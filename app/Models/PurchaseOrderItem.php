<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity_ordered',
        'quantity_received',
        'unit_price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'subtotal',
        'total',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * علاقة مع أمر الشراء
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * علاقة مع المنتج
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * حساب الكمية المتبقية للاستلام
     */
    public function getRemainingQuantityAttribute()
    {
        return $this->quantity_ordered - $this->quantity_received;
    }

    /**
     * التحقق من اكتمال الاستلام
     */
    public function isFullyReceived()
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }
}
