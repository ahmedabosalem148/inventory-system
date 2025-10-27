<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'supplier_id',
        'branch_id',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_percentage',
        'tax_amount',
        'shipping_cost',
        'total_amount',
        'status',
        'receiving_status',
        'payment_status',
        'notes',
        'cancellation_reason',
        'created_by',
        'approved_by',
        'approved_at',
        'print_count',
        'last_printed_at',
        'last_printed_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'last_printed_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * علاقة مع المورد
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * علاقة مع الفرع
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * علاقة مع أصناف الطلب
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * علاقة مع المستخدم الذي أنشأ الطلب
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
     * علاقة مع المستخدم الذي اعتمد الطلب
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
     * Scope للطلبات المعتمدة
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    /**
     * Scope للطلبات المعلقة
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope للبحث برقم الطلب
     */
    public function scopeSearchByNumber($query, $search)
    {
        return $query->where('order_number', 'like', "%{$search}%");
    }

    /**
     * حساب نسبة الاستلام
     */
    public function getReceivingPercentageAttribute()
    {
        $totalOrdered = $this->items->sum('quantity_ordered');
        $totalReceived = $this->items->sum('quantity_received');
        
        if ($totalOrdered == 0) return 0;
        
        return ($totalReceived / $totalOrdered) * 100;
    }

    /**
     * التحقق من إمكانية التعديل
     */
    public function isEditable()
    {
        return in_array($this->status, ['DRAFT', 'PENDING']);
    }

    /**
     * التحقق من إمكانية الاعتماد
     */
    public function isApprovable()
    {
        return $this->status === 'PENDING';
    }

    /**
     * التحقق من إمكانية الاستلام
     */
    public function isReceivable()
    {
        return $this->status === 'APPROVED' && $this->receiving_status !== 'FULLY_RECEIVED';
    }
}
