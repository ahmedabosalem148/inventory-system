<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * Product Classification Constants
     */
    public const CLASSIFICATION_FINISHED = 'finished_product';
    public const CLASSIFICATION_SEMI_FINISHED = 'semi_finished';
    public const CLASSIFICATION_RAW_MATERIAL = 'raw_material';
    public const CLASSIFICATION_PARTS = 'parts';
    public const CLASSIFICATION_PLASTIC_PARTS = 'plastic_parts';
    public const CLASSIFICATION_ALUMINUM_PARTS = 'aluminum_parts';
    public const CLASSIFICATION_OTHER = 'other';

    public const CLASSIFICATIONS = [
        self::CLASSIFICATION_FINISHED,
        self::CLASSIFICATION_SEMI_FINISHED,
        self::CLASSIFICATION_RAW_MATERIAL,
        self::CLASSIFICATION_PARTS,
        self::CLASSIFICATION_PLASTIC_PARTS,
        self::CLASSIFICATION_ALUMINUM_PARTS,
        self::CLASSIFICATION_OTHER,
    ];

    public const CLASSIFICATION_LABELS = [
        self::CLASSIFICATION_FINISHED => 'منتج تام',
        self::CLASSIFICATION_SEMI_FINISHED => 'منتج غير تام',
        self::CLASSIFICATION_RAW_MATERIAL => 'مواد خام',
        self::CLASSIFICATION_PARTS => 'أجزاء',
        self::CLASSIFICATION_PLASTIC_PARTS => 'بلاستيك',
        self::CLASSIFICATION_ALUMINUM_PARTS => 'ألومنيوم',
        self::CLASSIFICATION_OTHER => 'أخرى',
    ];

    public const CLASSIFICATION_SKU_PREFIXES = [
        self::CLASSIFICATION_FINISHED => 'FIN',
        self::CLASSIFICATION_SEMI_FINISHED => 'SEM',
        self::CLASSIFICATION_RAW_MATERIAL => 'RAW',
        self::CLASSIFICATION_PARTS => 'PRT',
        self::CLASSIFICATION_PLASTIC_PARTS => 'PLS',
        self::CLASSIFICATION_ALUMINUM_PARTS => 'ALU',
        self::CLASSIFICATION_OTHER => 'OTH',
    ];

    protected $fillable = [
        'category_id',
        'product_classification',
        'sku',
        'name',
        'brand',
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
                    ->withPivot('current_stock', 'reserved_stock', 'min_qty')
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

    /**
     * Scope للفلترة حسب التصنيف
     */
    public function scopeByClassification($query, $classification)
    {
        return $query->where('product_classification', $classification);
    }

    /**
     * Scope للأجزاء المصنعية (parts, plastic, aluminum)
     */
    public function scopeFactoryParts($query)
    {
        return $query->whereIn('product_classification', [
            self::CLASSIFICATION_PARTS,
            self::CLASSIFICATION_PLASTIC_PARTS,
            self::CLASSIFICATION_ALUMINUM_PARTS,
        ]);
    }

    /**
     * Accessor: الحصول على اسم التصنيف بالعربية
     */
    public function getClassificationLabelAttribute()
    {
        return self::CLASSIFICATION_LABELS[$this->product_classification] ?? 'غير محدد';
    }

    /**
     * Accessor: الحصول على SKU prefix حسب التصنيف
     */
    public function getSkuPrefixAttribute()
    {
        return self::CLASSIFICATION_SKU_PREFIXES[$this->product_classification] ?? 'OTH';
    }

    /**
     * Check if product requires pack_size
     */
    public function requiresPackSize(): bool
    {
        return in_array($this->product_classification, [
            self::CLASSIFICATION_PARTS,
            self::CLASSIFICATION_PLASTIC_PARTS,
            self::CLASSIFICATION_ALUMINUM_PARTS,
        ]);
    }

    /**
     * Get valid units for this product classification
     */
    public function getValidUnits(): array
    {
        return match($this->product_classification) {
            self::CLASSIFICATION_PARTS => ['pcs', 'piece', 'unit', 'قطعة'],
            self::CLASSIFICATION_PLASTIC_PARTS, 
            self::CLASSIFICATION_ALUMINUM_PARTS => ['kg', 'gram', 'ton', 'pcs', 'piece', 'كجم', 'جرام', 'قطعة', 'طن'],
            default => ['pcs', 'kg', 'liter', 'meter', 'قطعة', 'كجم', 'لتر', 'متر'],
        };
    }
}

