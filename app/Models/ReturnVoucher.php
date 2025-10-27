<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ReturnVoucher extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'voucher_number',
        'customer_id',
        'customer_name',
        'branch_id',
        'return_date',
        'voucher_type',
        'subtotal',
        'discount_amount',
        'net_total',
        'total_amount',
        'status',
        'reason',
        'reason_category',
        'notes',
        'approved_at',
        'approved_by',
        'created_by',
        'print_count',
        'last_printed_at',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * تكوين Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['voucher_number', 'customer_id', 'branch_id', 'total_amount', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "إذن مرتجع: {$eventName}");
    }

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
     * العلاقة مع أصناف الإذن
     */
    public function items()
    {
        return $this->hasMany(ReturnVoucherItem::class);
    }

    /**
     * العلاقة مع المستخدم المسجل
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias for creator relationship
     */
    public function createdBy()
    {
        return $this->creator();
    }

    /**
     * العلاقة مع المستخدم الذي اعتمد الإذن
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Alias for approver relationship
     */
    public function approvedBy()
    {
        return $this->approver();
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
        ->wherePivot('voucher_type', 'return_voucher')
        ->withPivot('allocated_amount')
        ->withTimestamps();
    }

    /**
     * Scope للإذونات المكتملة
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope للإذونات الملغاة
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

    /**
     * Accessor لعرض اسم العميل
     * يعطي أولوية للعميل المسجل، ثم الاسم النصي
     */
    public function getCustomerDisplayNameAttribute()
    {
        if ($this->customer_id && $this->customer) {
            return $this->customer->name;
        }
        
        return $this->customer_name ?? 'غير محدد';
    }

    /**
     * اعتماد إذن الإرجاع وإعطاء رقم تسلسلي من النطاق الخاص (100001-125000) + تسجيل في دفتر العميل
     */
    public function approve(User $user): self
    {
        $returnService = app(\App\Services\ReturnService::class);
        return $returnService->processReturn($this, $user);
    }

    /**
     * التحقق من اعتماد الإذن
     */
    public function isApproved(): bool
    {
        return !is_null($this->approved_at);
    }

    /**
     * التحقق من إمكانية اعتماد الإذن
     */
    public function canBeApproved(): bool
    {
        return !$this->isApproved() && $this->status !== 'cancelled';
    }
}
