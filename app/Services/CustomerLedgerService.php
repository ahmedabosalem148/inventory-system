<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * CustomerLedgerService
 * 
 * خدمة إدارة دفتر العملاء (علية/له)
 * تطبق نظام المحاسبة ذات القيد المزدوج (Double Entry Bookkeeping)
 * المعادلة الأساسية: رصيد العميل = Σ(علية) - Σ(له)
 * 
 * مطابق تماماً لملف Excel الحالي:
 * - علية (debit_aliah): مبالغ مستحقة على العميل (مبيعات آجلة)
 * - له (credit_lah): مبالغ مدفوعة أو مرتجعة (خصم من المديونية)
 */
class CustomerLedgerService
{
    /**
     * إضافة قيد جديد لدفتر العميل
     * 
     * @param int $customerId معرف العميل
     * @param string $description وصف القيد (مثال: فاتورة رقم 4134، دفعة نقدية)
     * @param float $debitAliah مبلغ علية (مديونية على العميل)
     * @param float $creditLah مبلغ له (دائنية للعميل)
     * @param string|null $refTable جدول المستند المصدر
     * @param int|null $refId معرف المستند المصدر
     * @param string|null $notes ملاحظات إضافية
     * @param int|null $createdBy المستخدم الذي أنشأ القيد
     * @return CustomerLedgerEntry
     */
    public function addEntry(
        int $customerId,
        string $description,
        float $debitAliah = 0,
        float $creditLah = 0,
        ?string $refTable = null,
        ?int $refId = null,
        ?string $notes = null,
        ?int $createdBy = null
    ): CustomerLedgerEntry {
        // التحقق من صحة المدخلات
        if ($debitAliah < 0 || $creditLah < 0) {
            throw new \InvalidArgumentException('المبالغ يجب أن تكون موجبة');
        }

        if ($debitAliah == 0 && $creditLah == 0) {
            throw new \InvalidArgumentException('يجب أن يكون أحد المبلغين (علية أو له) أكبر من صفر');
        }

        // التحقق من وجود العميل
        $customer = Customer::findOrFail($customerId);

        // إنشاء القيد
        $entry = CustomerLedgerEntry::create([
            'customer_id' => $customerId,
            'entry_date' => now()->format('Y-m-d'),
            'description' => $description,
            'debit_aliah' => $debitAliah,
            'credit_lah' => $creditLah,
            'ref_table' => $refTable,
            'ref_id' => $refId,
            'notes' => $notes,
            'created_by' => $createdBy ?? auth()->id(),
        ]);

        // تحديث آخر نشاط للعميل
        $customer->update([
            'last_activity_at' => now()
        ]);

        return $entry;
    }

    /**
     * حساب رصيد العميل
     * المعادلة: Σ(علية) - Σ(له)
     * 
     * @param int $customerId معرف العميل
     * @param string|null $upToDate حساب الرصيد حتى تاريخ محدد (اختياري)
     * @return float
     */
    public function calculateBalance(int $customerId, ?string $upToDate = null): float
    {
        $query = CustomerLedgerEntry::where('customer_id', $customerId);

        if ($upToDate) {
            $query->where('entry_date', '<=', $upToDate);
        }

        $result = $query->selectRaw('
            SUM(debit_aliah) as total_debit,
            SUM(credit_lah) as total_credit
        ')->first();

        $totalDebit = $result->total_debit ?? 0;
        $totalCredit = $result->total_credit ?? 0;

        return round($totalDebit - $totalCredit, 2);
    }

    /**
     * كشف حساب العميل لفترة محددة
     * مطابق تماماً لكشف الحساب في Excel
     * 
     * @param int $customerId معرف العميل
     * @param string $fromDate التاريخ من
     * @param string $toDate التاريخ إلى
     * @param bool $includeBalance تضمين الرصيد المتحرك
     * @return Collection
     */
    public function getCustomerStatement(
        int $customerId,
        string $fromDate,
        string $toDate,
        bool $includeBalance = true
    ): Collection {
        // جلب الرصيد الافتتاحي (قبل فترة الكشف)
        $openingBalance = $this->calculateBalance($customerId, 
            Carbon::parse($fromDate)->subDay()->format('Y-m-d')
        );

        // جلب القيود في الفترة المحددة
        $entries = CustomerLedgerEntry::where('customer_id', $customerId)
            ->whereBetween('entry_date', [$fromDate, $toDate])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        // إذا كان مطلوب حساب الرصيد المتحرك
        if ($includeBalance) {
            $runningBalance = $openingBalance;
            
            $entries = $entries->map(function ($entry) use (&$runningBalance) {
                $runningBalance += $entry->debit_aliah - $entry->credit_lah;
                $entry->running_balance = round($runningBalance, 2);
                return $entry;
            });
        }

        return collect([
            'customer_id' => $customerId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'opening_balance' => round($openingBalance, 2),
            'entries' => $entries,
            'closing_balance' => $this->calculateBalance($customerId, $toDate),
            'total_debit' => round($entries->sum('debit_aliah'), 2),
            'total_credit' => round($entries->sum('credit_lah'), 2),
        ]);
    }

    /**
     * الحصول على قائمة العملاء مع أرصدتهم
     * مطابق لتقرير "مراجعة الدفتر" في Excel
     * 
     * @param bool $onlyWithBalance فقط العملاء الذين لديهم رصيد
     * @param string $sortBy ترتيب حسب: 'name', 'balance', 'last_activity'
     * @return Collection
     */
    public function getCustomersBalances(
        bool $onlyWithBalance = false,
        string $sortBy = 'name'
    ): Collection {
        $customers = Customer::where('is_active', true)
            ->with(['ledgerEntries' => function ($query) {
                $query->selectRaw('
                    customer_id,
                    SUM(debit_aliah) as total_debit,
                    SUM(credit_lah) as total_credit,
                    MAX(entry_date) as last_entry_date
                ')->groupBy('customer_id');
            }])
            ->get();

        $result = $customers->map(function ($customer) {
            $ledgerSummary = $customer->ledgerEntries->first();
            
            $totalDebit = $ledgerSummary->total_debit ?? 0;
            $totalCredit = $ledgerSummary->total_credit ?? 0;
            $balance = round($totalDebit - $totalCredit, 2);

            return [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'balance' => $balance,
                'total_debit' => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
                'last_entry_date' => $ledgerSummary->last_entry_date ?? null,
                'last_activity_at' => $customer->last_activity_at,
                'status' => $balance > 0 ? 'debtor' : ($balance < 0 ? 'creditor' : 'zero'),
                'status_arabic' => $balance > 0 ? 'مدين' : ($balance < 0 ? 'دائن' : 'متوازن'),
            ];
        });

        // تصفية العملاء الذين لديهم رصيد فقط
        if ($onlyWithBalance) {
            $result = $result->filter(fn($item) => abs($item['balance']) > 0.01);
        }

        // الترتيب
        $result = match($sortBy) {
            'balance' => $result->sortByDesc('balance'),
            'last_activity' => $result->sortByDesc('last_activity_at'),
            default => $result->sortBy('name')
        };

        return $result->values();
    }

    /**
     * إجمالي المديونيات (العملاء المدينين)
     * 
     * @return float
     */
    public function getTotalDebtors(): float
    {
        $balances = $this->getCustomersBalances();
        return round($balances->where('balance', '>', 0)->sum('balance'), 2);
    }

    /**
     * إجمالي الدائنيات (العملاء الدائنين)
     * 
     * @return float
     */
    public function getTotalCreditors(): float
    {
        $balances = $this->getCustomersBalances();
        return round(abs($balances->where('balance', '<', 0)->sum('balance')), 2);
    }

    /**
     * إحصائيات دفتر العملاء
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        $balances = $this->getCustomersBalances();

        return [
            'total_customers' => $balances->count(),
            'customers_with_balance' => $balances->filter(fn($c) => abs($c['balance']) > 0.01)->count(),
            'debtors_count' => $balances->where('balance', '>', 0)->count(),
            'creditors_count' => $balances->where('balance', '<', 0)->count(),
            'zero_balance_count' => $balances->filter(fn($c) => abs($c['balance']) <= 0.01)->count(),
            'total_debtors_amount' => $this->getTotalDebtors(),
            'total_creditors_amount' => $this->getTotalCreditors(),
            'net_balance' => round($this->getTotalDebtors() - $this->getTotalCreditors(), 2),
        ];
    }

    /**
     * تصحيح رصيد عميل (لحالات استثنائية)
     * 
     * @param int $customerId
     * @param float $correctBalance الرصيد الصحيح
     * @param string $reason سبب التصحيح
     * @param int|null $userId
     * @return CustomerLedgerEntry
     */
    public function correctBalance(
        int $customerId,
        float $correctBalance,
        string $reason,
        ?int $userId = null
    ): CustomerLedgerEntry {
        $currentBalance = $this->calculateBalance($customerId);
        $difference = $correctBalance - $currentBalance;

        if (abs($difference) < 0.01) {
            throw new \InvalidArgumentException('الرصيد الحالي مطابق للرصيد المطلوب');
        }

        $description = "تصحيح الرصيد: {$reason}";
        
        return $this->addEntry(
            customerId: $customerId,
            description: $description,
            debitAliah: $difference > 0 ? $difference : 0,
            creditLah: $difference < 0 ? abs($difference) : 0,
            refTable: 'balance_correction',
            refId: null,
            notes: "تصحيح من {$currentBalance} إلى {$correctBalance}",
            createdBy: $userId
        );
    }
}
