<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'unit',
        'pack_size',
        'purchase_price',
        'sale_price',
        'min_stock',
        'reorder_level',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'min_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * علاقة: المنتج ينتمي لتصنيف واحد
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * علاقة: المنتج لديه مخزون في فروع كثيرة
     */
    public function branchStocks()
    {
        return $this->hasMany(ProductBranchStock::class);
    }

    /**
     * علاقة many-to-many: المنتج موجود في فروع كثيرة
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'product_branch_stock')
                    ->withPivot('current_stock')
                    ->withTimestamps();
    }

    /**
     * الحصول على إجمالي المخزون في جميع الفروع
     */
    public function getTotalStockAttribute()
    {
        return $this->branchStocks()->sum('current_stock');
    }

    /**
     * Scope للمنتجات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للبحث عن منتج بالاسم
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%");
    }
}

