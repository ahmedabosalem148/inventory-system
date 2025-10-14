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
        'total_amount',
        'status',
        'notes',
        'approved_at',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
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
     * العلاقة مع المستخدم الذي اعتمد الإذن
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
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
        if ($this->isApproved()) {
            throw new \Exception('الإذن معتمد بالفعل');
        }

        return \DB::transaction(function () use ($user) {
            // إعطاء رقم تسلسلي من النطاق الخاص للإرجاع
            $sequencerService = app(\App\Services\SequencerService::class);
            $this->voucher_number = $sequencerService->getNextReturnNumber();
            
            // تسجيل الاعتماد
            $this->approved_at = now();
            $this->approved_by = $user->id;
            $this->status = 'completed'; // الحالة النهائية
            
            $this->save();

            // تسجيل في دفتر العميل (قيد "له" - خصم من المديونية)
            if ($this->customer_id) {
                $ledgerService = app(\App\Services\CustomerLedgerService::class);
                
                // حساب المبلغ النهائي
                $totalAmount = $this->total_amount ?? 0;
                
                // قيد "له" للارتجاع - يخصم من مديونية العميل
                $ledgerService->addEntry(
                    customerId: $this->customer_id,
                    description: "ارتجاع رقم {$this->voucher_number}",
                    debitAliah: 0,
                    creditLah: $totalAmount, // له - خصم من المديونية
                    refTable: 'return_vouchers',
                    refId: $this->id,
                    notes: $this->reason ?? 'ارتجاع بضاعة',
                    createdBy: $user->id
                );
            }

            return $this;
        });
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
