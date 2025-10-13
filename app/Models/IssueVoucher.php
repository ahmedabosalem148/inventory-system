<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssueVoucher extends Model
{
    protected $fillable = [
        'voucher_number',
        'customer_id',
        'customer_name',
        'branch_id',
        'issue_date',
        'notes',
        'total_amount',
        'discount_type',
        'discount_value',
        'discount_amount',
        'subtotal',
        'net_total',
        'status',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'net_total' => 'decimal:2',
    ];

    /**
     * العلاقة مع العميل
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * العلاقة مع الفرع
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * العلاقة مع الأصناف (items)
     */
    public function items()
    {
        return $this->hasMany(IssueVoucherItem::class);
    }

    /**
     * العلاقة مع المستخدم الذي أنشأ الإذن
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع السدادات من خلال pivot table
     */
    public function payments()
    {
        return $this->belongsToMany(
            Payment::class, 
            'payment_voucher', 
            'voucher_id', 
            'payment_id'
        )
        ->wherePivot('voucher_type', 'issue_voucher')
        ->withPivot('allocated_amount')
        ->withTimestamps();
    }

    /**
     * الحصول على اسم العميل (سواء مسجل أو كاش)
     */
    public function getCustomerDisplayNameAttribute(): string
    {
        return $this->customer?->name ?? $this->customer_name ?? 'عميل نقدي';
    }

    /**
     * Scope للأذونات المكتملة
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope للأذونات الملغاة
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope للبحث برقم الإذن
     */
    public function scopeSearchByNumber($query, $number)
    {
        return $query->where('voucher_number', 'like', "%{$number}%");
    }
}
