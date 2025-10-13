<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'phone',
        'address',
        'balance',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * العلاقة مع دفتر الحساب (سيُضاف لاحقاً في TASK-012)
     */
    public function ledgerEntries()
    {
        return $this->hasMany(CustomerLedger::class);
    }

    /**
     * العلاقة مع السدادات
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * الحصول على الرصيد المنسق
     */
    public function getFormattedBalanceAttribute(): string
    {
        $balance = $this->balance;
        $formatted = number_format(abs($balance), 2);
        
        if ($balance > 0) {
            return "{$formatted} ج.م (له)";
        } elseif ($balance < 0) {
            return "{$formatted} ج.م (عليه)";
        } else {
            return "0.00 ج.م (متزن)";
        }
    }

    /**
     * Scope للعملاء النشطين فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope للبحث بالاسم أو الهاتف
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    /**
     * Scope للعملاء الذين لهم رصيد (دائن)
     */
    public function scopeWithCredit($query)
    {
        return $query->where('balance', '>', 0);
    }

    /**
     * Scope للعملاء الذين عليهم رصيد (مدين)
     */
    public function scopeWithDebit($query)
    {
        return $query->where('balance', '<', 0);
    }
}
