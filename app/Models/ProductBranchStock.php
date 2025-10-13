<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBranchStock extends Model
{
    use HasFactory;

    protected $table = 'product_branch_stock';

    protected $fillable = [
        'product_id',
        'branch_id',
        'current_stock',
    ];

    protected $casts = [
        'current_stock' => 'integer',
    ];

    /**
     * علاقة: المخزون ينتمي لمنتج واحد
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * علاقة: المخزون ينتمي لفرع واحد
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Scope للمخزون المنخفض (أقل من الحد الأدنى)
     */
    public function scopeLowStock($query)
    {
        return $query->whereHas('product', function($q) {
            $q->whereRaw('product_branch_stock.current_stock < products.min_stock');
        });
    }

    /**
     * Scope للمخزون الموجود (أكبر من 0)
     */
    public function scopeInStock($query)
    {
        return $query->where('current_stock', '>', 0);
    }

    /**
     * Scope للمخزون المنتهي
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', '<=', 0);
    }
}
