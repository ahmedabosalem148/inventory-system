<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'customer_id',
        'payment_date',
        'amount',
        'payment_method',
        'cheque_id',
        'issue_voucher_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * تكوين Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['customer_id', 'amount', 'payment_method', 'payment_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "سداد: {$eventName}");
    }

    /**
     * العلاقة مع العميل
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * العلاقة مع الشيك (إن كان الدفع بشيك)
     */
    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }

    /**
     * العلاقة مع إذن الصرف (اختياري)
     */
    public function issueVoucher()
    {
        return $this->belongsTo(IssueVoucher::class);
    }

    /**
     * العلاقة مع إذونات الصرف من خلال pivot table
     */
    public function issueVouchers()
    {
        return $this->belongsToMany(
            IssueVoucher::class, 
            'payment_voucher', 
            'payment_id', 
            'voucher_id'
        )
        ->wherePivot('voucher_type', 'issue_voucher')
        ->withPivot('allocated_amount')
        ->withTimestamps();
    }

    /**
     * العلاقة مع إذونات الإرجاع من خلال pivot table
     */
    public function returnVouchers()
    {
        return $this->belongsToMany(
            ReturnVoucher::class, 
            'payment_voucher', 
            'payment_id', 
            'voucher_id'
        )
        ->wherePivot('voucher_type', 'return_voucher')
        ->withPivot('allocated_amount')
        ->withTimestamps();
    }

    /**
     * العلاقة مع المستخدم المسجل
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope للحصول على المدفوعات النقدية
     */
    public function scopeCash($query)
    {
        return $query->where('payment_method', 'CASH');
    }

    /**
     * Scope للحصول على المدفوعات بالشيكات
     */
    public function scopeChequePayments($query)
    {
        return $query->where('payment_method', 'CHEQUE');
    }

    /**
     * Scope للتصفية حسب العميل
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope للتصفية بفترة زمنية
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('payment_date', [$from, $to]);
    }

    /**
     * Accessor لاسم طريقة الدفع بالعربية
     */
    public function getPaymentMethodNameAttribute()
    {
        return $this->payment_method === 'CASH' ? 'نقدي' : 'شيك';
    }

    /**
     * Accessor لأيقونة طريقة الدفع
     */
    public function getPaymentMethodIconAttribute()
    {
        return $this->payment_method === 'CASH' ? 'bi-cash-coin' : 'bi-credit-card-2-front';
    }

    /**
     * Accessor للون badge طريقة الدفع
     */
    public function getPaymentMethodBadgeAttribute()
    {
        return $this->payment_method === 'CASH' ? 'bg-success' : 'bg-info';
    }
}
