<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssueVoucherItem extends Model
{
    protected $fillable = [
        'issue_voucher_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'net_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_price' => 'decimal:2',
    ];

    /**
     * العلاقة مع الإذن الرئيسي
     */
    public function voucher()
    {
        return $this->belongsTo(IssueVoucher::class, 'issue_voucher_id');
    }

    /**
     * العلاقة مع المنتج
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * حساب الإجمالي تلقائياً قبل الحفظ
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
}
