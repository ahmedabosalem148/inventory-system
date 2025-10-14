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
        'voucher_type',
        'notes',
        'is_transfer',
        'target_branch_id',
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
        'is_transfer' => 'boolean',
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
     * العلاقة مع الفرع المستهدف (في حالة التحويل)
     */
    public function targetBranch()
    {
        return $this->belongsTo(Branch::class, 'target_branch_id');
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
     * اعتماد الإذن وإعطاء رقم تسلسلي + تسجيل في دفتر العميل أو التحويل بين الفروع
     */
    public function approve(User $user): self
    {
        if ($this->isApproved()) {
            throw new \Exception('الإذن معتمد بالفعل');
        }

        return \DB::transaction(function () use ($user) {
            // ✅ التحقق من توفر المخزون قبل الاعتماد
            $stockValidation = app(\App\Services\StockValidationService::class);
            
            if ($this->is_transfer) {
                // التحقق من إمكانية التحويل
                $result = $stockValidation->canTransfer(
                    $this->items,
                    $this->branch_id,
                    $this->target_branch_id
                );
                
                if (!$result['can_transfer']) {
                    throw new \Exception($result['message'] . "\n\n" . implode("\n", $result['validation']['messages'] ?? []));
                }
            } else {
                // التحقق من إمكانية الصرف
                $result = $stockValidation->canIssueVoucher($this->items, $this->branch_id);
                
                if (!$result['can_issue']) {
                    $errorMessage = $result['message'] . "\n\n";
                    $errorMessage .= implode("\n", $result['validation']['messages'] ?? []);
                    
                    // إضافة الاقتراحات إذا وجدت
                    if (!empty($result['suggestions'])) {
                        $errorMessage .= "\n\nالاقتراحات البديلة:\n";
                        foreach ($result['suggestions'] as $productId => $suggestions) {
                            foreach ($suggestions as $suggestion) {
                                $errorMessage .= "- " . $suggestion['message'] . "\n";
                            }
                        }
                    }
                    
                    throw new \Exception($errorMessage);
                }
            }
            
            // إعطاء رقم تسلسلي
            $sequencerService = app(\App\Services\SequencerService::class);
            $this->voucher_number = $sequencerService->getNextSequence('issue_vouchers');
            
            // تسجيل الاعتماد
            $this->approved_at = now();
            $this->approved_by = $user->id;
            $this->status = 'completed'; // الحالة النهائية
            
            $this->save();

            // إذا كان الإذن تحويل بين فروع
            if ($this->is_transfer) {
                $transferService = app(\App\Services\TransferService::class);
                $transferService->createTransfer($this, $user);
            }
            // وإلا فهو بيع عادي
            else {
                // تسجيل حركة المخزون العادية (خصم من المخزون)
                foreach ($this->items as $item) {
                    \App\Models\InventoryMovement::create([
                        'product_id' => $item->product_id,
                        'branch_id' => $this->branch_id,
                        'movement_type' => 'ISSUE',
                        'qty_units' => -abs($item->quantity), // سالب للخصم
                        'unit_price_snapshot' => $item->unit_price ?? 0,
                        'ref_table' => 'issue_vouchers',
                        'ref_id' => $this->id,
                        'notes' => "بيع - إذن رقم {$this->voucher_number}",
                    ]);

                    // تحديث رصيد المخزون
                    $stock = \App\Models\ProductBranchStock::firstOrCreate(
                        [
                            'product_id' => $item->product_id,
                            'branch_id' => $this->branch_id,
                        ],
                        [
                            'current_stock' => 0,
                            'reserved_stock' => 0,
                            'min_qty' => 0,
                        ]
                    );
                    $stock->decrement('current_stock', abs($item->quantity));
                }

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
