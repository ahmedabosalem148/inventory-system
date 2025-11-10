<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerLedgerEntry Model
 * 
 * نموذج دفتر العملاء (علية/له)
 * يطبق نظام المحاسبة ذات القيد المزدوج
 * 
 * المعادلة: رصيد العميل = Σ(علية) - Σ(له)
 * - علية (debit_aliah): مبالغ مستحقة على العميل (مبيعات آجلة)
 * - له (credit_lah): مبالغ مدفوعة أو مرتجعة (خصم من المديونية)
 * 
 * @property int $id
 * @property int $customer_id
 * @property string $transaction_date
 * @property string $description
 * @property float $debit_aliah علية - مديونية على العميل
 * @property float $credit_lah له - دائنية للعميل
 * @property string|null $ref_table
 * @property int|null $ref_id
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class CustomerLedgerEntry extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerLedgerEntryFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'customer_ledger';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'customer_id',
        'transaction_date',
        'transaction_type',
        'reference_number',
        'reference_id',
        'debit',
        'credit',
        'balance',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'customer_id' => 'integer',
        'ref_id' => 'integer',
        'created_by' => 'integer',
    ];

    /**
     * علاقة مع العميل
     * كل قيد ينتمي إلى عميل واحد
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * علاقة مع المستخدم الذي أنشأ القيد
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * حساب صافي القيد (علية - له)
     * 
     * @return float
     */
    public function getNetAmountAttribute(): float
    {
        return $this->debit - $this->credit;
    }

    /**
     * تحديد نوع القيد
     * 
     * @return string 'debit'|'credit'|'zero'
     */
    public function getEntryTypeAttribute(): string
    {
        if ($this->debit > 0 && $this->credit == 0) {
            return 'debit'; // قيد علية (مديونية)
        } elseif ($this->credit > 0 && $this->debit == 0) {
            return 'credit'; // قيد له (دائنية)
        } else {
            return 'zero'; // قيد صفري
        }
    }

    /**
     * الحصول على نص نوع القيد بالعربية
     * 
     * @return string
     */
    public function getEntryTypeArabicAttribute(): string
    {
        return match($this->entry_type) {
            'debit' => 'علية',
            'credit' => 'له',
            default => 'صفر'
        };
    }

    /**
     * Scope: تصفية القيود حسب العميل
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope: تصفية القيود في فترة زمنية
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope: فقط قيود علية (مديونية)
     */
    public function scopeDebitsOnly($query)
    {
        return $query->where('debit', '>', 0);
    }

    /**
     * Scope: فقط قيود له (دائنية)
     */
    public function scopeCreditsOnly($query)
    {
        return $query->where('credit', '>', 0);
    }
}

