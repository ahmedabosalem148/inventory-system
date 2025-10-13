<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * علاقة: التصنيف لديه منتجات كثيرة
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope للتصنيفات النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
