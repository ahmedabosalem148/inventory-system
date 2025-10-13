<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnVoucherItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_voucher_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Boot method
     * حساب total_price تلقائياً عند الإضافة أو التعديل
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->total_price = $item->quantity * $item->unit_price;
        });

        static::updating(function ($item) {
            $item->total_price = $item->quantity * $item->unit_price;
        });
    }

    /**
     * العلاقة مع إذن الإرجاع
     */
    public function returnVoucher()
    {
        return $this->belongsTo(ReturnVoucher::class);
    }

    /**
     * العلاقة مع المنتج
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
