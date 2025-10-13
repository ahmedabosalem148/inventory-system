<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerLedger extends Model
{
    use HasFactory;

    protected $table = 'customer_ledger';

    protected $fillable = [
        'customer_id',
        'transaction_type',
        'reference_number',
        'reference_id',
        'transaction_date',
        'debit',
        'credit',
        'balance',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    /**
     * العلاقة مع العميل
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * العلاقة مع المستخدم المسجل
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope للحصول على سجلات عميل معين
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope للحصول على نوع عملية معين
     */
    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope للحصول على العمليات المدينة (له)
     */
    public function scopeDebits($query)
    {
        return $query->where('debit', '>', 0);
    }

    /**
     * Scope للحصول على العمليات الدائنة (عليه)
     */
    public function scopeCredits($query)
    {
        return $query->where('credit', '>', 0);
    }

    /**
     * Scope للتصفية بفترة زمنية
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    /**
     * Accessor للحصول على نوع العملية بالعربية
     */
    public function getTransactionTypeNameAttribute()
    {
        $types = [
            'issue_voucher' => 'إذن صرف',
            'return_voucher' => 'إذن إرجاع',
            'payment' => 'سداد',
            'initial_balance' => 'رصيد افتتاحي',
        ];

        return $types[$this->transaction_type] ?? $this->transaction_type;
    }

    /**
     * Accessor للحصول على أيقونة نوع العملية
     */
    public function getTransactionTypeIconAttribute()
    {
        $icons = [
            'issue_voucher' => 'bi-box-arrow-right',
            'return_voucher' => 'bi-arrow-counterclockwise',
            'payment' => 'bi-cash-coin',
            'initial_balance' => 'bi-calendar-check',
        ];

        return $icons[$this->transaction_type] ?? 'bi-question-circle';
    }

    /**
     * Accessor للحصول على لون نوع العملية
     */
    public function getTransactionTypeBadgeAttribute()
    {
        $badges = [
            'issue_voucher' => 'bg-primary',
            'return_voucher' => 'bg-warning',
            'payment' => 'bg-success',
            'initial_balance' => 'bg-info',
        ];

        return $badges[$this->transaction_type] ?? 'bg-secondary';
    }

    /**
     * Helper: إنشاء سجل دفتر للعميل
     */
    public static function record(
        $customerId,
        $transactionType,
        $transactionDate,
        $debit,
        $credit,
        $referenceNumber = null,
        $referenceId = null,
        $notes = null
    ) {
        // Get customer's current balance
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            throw new \Exception("Customer not found with ID: {$customerId}");
        }

        // Calculate new balance
        // Debit increases balance (له), Credit decreases balance (عليه)
        $newBalance = $customer->balance + $debit - $credit;

        // Create ledger entry
        return self::create([
            'customer_id' => $customerId,
            'transaction_type' => $transactionType,
            'reference_number' => $referenceNumber,
            'reference_id' => $referenceId,
            'transaction_date' => $transactionDate,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $newBalance,
            'notes' => $notes,
            'created_by' => auth()->id() ?? 1,
        ]);
    }
}
