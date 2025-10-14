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
        'approved_at',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'net_total' => 'decimal:2',
        'approved_at' => 'datetime',
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
     * اعتماد الإذن وإعطاء رقم تسلسلي + تسجيل في دفتر العميل
     */
    public function approve(User $user): self
    {
        if ($this->isApproved()) {
            throw new \Exception('الإذن معتمد بالفعل');
        }

        return \DB::transaction(function () use ($user) {
            // إعطاء رقم تسلسلي
            $sequencerService = app(\App\Services\SequencerService::class);
            $this->voucher_number = $sequencerService->getNextSequence('issue_vouchers');
            
            // تسجيل الاعتماد
            $this->approved_at = now();
            $this->approved_by = $user->id;
            $this->status = 'completed'; // الحالة النهائية
            
            $this->save();

            // تسجيل في دفتر العميل (إذا كان هناك عميل)
            if ($this->customer_id) {
                $ledgerService = app(\App\Services\CustomerLedgerService::class);
                
                // حساب المبلغ النهائي
                $totalAmount = $this->net_total ?? $this->total_amount ?? 0;
                
                // تحديد نوع البيع (نقدي أو آجل) من الملاحظات أو الحقول
                $isCashSale = stripos($this->notes ?? '', 'نقدي') !== false;
                
                // قيد "علية" للبيع (سواء نقدي أو آجل)
                $ledgerService->addEntry(
                    customerId: $this->customer_id,
                    description: "فاتورة رقم {$this->voucher_number}",
                    debitAliah: $totalAmount, // علية - مديونية على العميل
                    creditLah: 0,
                    refTable: 'issue_vouchers',
                    refId: $this->id,
                    notes: $this->notes,
                    createdBy: $user->id
                );
                
                // إذا كان البيع نقدي، نضيف قيد "له" فوري
                if ($isCashSale) {
                    $ledgerService->addEntry(
                        customerId: $this->customer_id,
                        description: "سداد نقدي لفاتورة رقم {$this->voucher_number}",
                        debitAliah: 0,
                        creditLah: $totalAmount, // له - دفعة نقدية
                        refTable: 'issue_vouchers',
                        refId: $this->id,
                        notes: 'سداد نقدي فوري',
                        createdBy: $user->id
                    );
                }
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
