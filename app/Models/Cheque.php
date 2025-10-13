<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Cheque extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'customer_id',
        'cheque_number',
        'bank_name',
        'due_date',
        'amount',
        'status',
        'cleared_at',
        'return_reason',
        'issue_voucher_id',
        'notes',
        'created_by',
        'cleared_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'cleared_at' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * تكوين Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['customer_id', 'cheque_number', 'bank_name', 'amount', 'status', 'due_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "شيك: {$eventName}");
    }

    /**
     * العلاقة مع العميل
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * العلاقة مع إذن الصرف (اختياري)
     */
    public function issueVoucher()
    {
        return $this->belongsTo(IssueVoucher::class);
    }

    /**
     * العلاقة مع المستخدم المسجل
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع المستخدم الذي صرف الشيك
     */
    public function clearedBy()
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }

    /**
     * العلاقة مع المدفوعات (شيك واحد قد يربط بمدفوع واحد)
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Scope للشيكات المعلقة (لم تصرف بعد)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope للشيكات المصروفة
     */
    public function scopeCleared($query)
    {
        return $query->where('status', 'CLEARED');
    }

    /**
     * Scope للشيكات المرتجعة
     */
    public function scopeReturned($query)
    {
        return $query->where('status', 'RETURNED');
    }

    /**
     * Scope للشيكات المستحقة خلال فترة معينة
     */
    public function scopeDueSoon($query, $days = 7)
    {
        return $query->where('status', 'PENDING')
                    ->where('due_date', '<=', now()->addDays($days))
                    ->where('due_date', '>=', now());
    }

    /**
     * Scope للشيكات المتأخرة (تجاوزت الاستحقاق ولم تصرف)
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'PENDING')
                    ->where('due_date', '<', now());
    }

    /**
     * Scope للتصفية حسب العميل
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope للتصفية بفترة استحقاق
     */
    public function scopeDueDateRange($query, $from, $to)
    {
        return $query->whereBetween('due_date', [$from, $to]);
    }

    /**
     * Accessor لاسم حالة الشيك بالعربية
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            'PENDING' => 'معلق',
            'CLEARED' => 'مصروف',
            'RETURNED' => 'مرتجع',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Accessor لأيقونة حالة الشيك
     */
    public function getStatusIconAttribute()
    {
        $icons = [
            'PENDING' => 'bi-hourglass-split',
            'CLEARED' => 'bi-check-circle',
            'RETURNED' => 'bi-x-circle',
        ];

        return $icons[$this->status] ?? 'bi-question-circle';
    }

    /**
     * Accessor للون badge حالة الشيك
     */
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'PENDING' => 'bg-warning',
            'CLEARED' => 'bg-success',
            'RETURNED' => 'bg-danger',
        ];

        return $badges[$this->status] ?? 'bg-secondary';
    }

    /**
     * Helper: تحقق إذا كان الشيك متأخر
     */
    public function isOverdue()
    {
        return $this->status === 'PENDING' && $this->due_date < now();
    }

    /**
     * Helper: تحقق إذا كان الشيك مستحق قريباً
     */
    public function isDueSoon($days = 7)
    {
        return $this->status === 'PENDING' 
            && $this->due_date >= now() 
            && $this->due_date <= now()->addDays($days);
    }
}
