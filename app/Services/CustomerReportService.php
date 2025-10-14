<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LedgerEntry;
use App\Models\IssueVoucher;
use App\Models\ReturnVoucher;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerReportService
{
    /**
     * تقرير شامل لأرصدة جميع العملاء
     * Comprehensive Customer Balance Report
     * 
     * @param array $filters
     * @return array
     */
    public function getCustomerBalancesReport(array $filters = []): array
    {
        $query = Customer::with(['ledgerEntries'])
            ->where('is_active', true);

        // Filter by customer
        if (isset($filters['customer_id'])) {
            $query->where('id', $filters['customer_id']);
        }

        // Get customers with their ledger balances
        $customers = $query->get();

        $reportData = [];
        $summary = [
            'total_customers' => 0,
            'customers_with_balance' => 0,
            'total_debit' => 0,
            'total_credit' => 0,
            'net_balance' => 0,
            'active_customers' => 0,
            'inactive_customers' => 0,
        ];

        foreach ($customers as $customer) {
            // Calculate balance from ledger entries
            $debitTotal = $customer->ledgerEntries()->where('type', 'debit')->sum('amount');
            $creditTotal = $customer->ledgerEntries()->where('type', 'credit')->sum('amount');
            $balance = $debitTotal - $creditTotal;

            // Get last transaction date
            $lastTransaction = $customer->ledgerEntries()
                ->latest('transaction_date')
                ->first();

            // Count transactions
            $transactionCount = $customer->ledgerEntries()->count();

            // Classify customer activity
            $daysSinceLastTransaction = $lastTransaction 
                ? now()->diffInDays($lastTransaction->transaction_date) 
                : null;

            $activityStatus = 'غير نشط';
            if ($transactionCount > 0) {
                if ($daysSinceLastTransaction <= 30) {
                    $activityStatus = 'نشط جداً';
                } elseif ($daysSinceLastTransaction <= 90) {
                    $activityStatus = 'نشط';
                } elseif ($daysSinceLastTransaction <= 180) {
                    $activityStatus = 'متوسط النشاط';
                } else {
                    $activityStatus = 'خامل';
                }
            }

            // Balance classification
            $balanceStatus = 'متوازن';
            if ($balance > 1000) {
                $balanceStatus = 'مدين كبير';
            } elseif ($balance > 100) {
                $balanceStatus = 'مدين';
            } elseif ($balance < -1000) {
                $balanceStatus = 'دائن كبير';
            } elseif ($balance < -100) {
                $balanceStatus = 'دائن';
            }

            $reportData[] = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_code' => $customer->code,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'total_debit' => $debitTotal,
                'total_credit' => $creditTotal,
                'balance' => $balance,
                'transaction_count' => $transactionCount,
                'last_transaction_date' => $lastTransaction?->transaction_date?->toDateString(),
                'days_since_last_transaction' => $daysSinceLastTransaction,
                'activity_status' => $activityStatus,
                'balance_status' => $balanceStatus,
            ];

            // Update summary
            $summary['total_customers']++;
            if (abs($balance) > 0.01) {
                $summary['customers_with_balance']++;
            }
            $summary['total_debit'] += $debitTotal;
            $summary['total_credit'] += $creditTotal;
            $summary['net_balance'] += $balance;
            
            if ($activityStatus === 'نشط جداً' || $activityStatus === 'نشط') {
                $summary['active_customers']++;
            } else {
                $summary['inactive_customers']++;
            }
        }

        // Sort by balance (highest debit first)
        usort($reportData, function ($a, $b) {
            return $b['balance'] <=> $a['balance'];
        });

        return [
            'customers' => $reportData,
            'summary' => $summary,
            'generated_at' => now()->toDateTimeString(),
            'filters' => $filters,
        ];
    }

    /**
     * تقرير كشف حساب عميل واحد
     * Customer Statement Report
     * 
     * @param int $customerId
     * @param array $filters
     * @return array
     */
    public function getCustomerStatement(int $customerId, array $filters = []): array
    {
        $customer = Customer::findOrFail($customerId);

        $query = LedgerEntry::where('customer_id', $customerId);

        // Date range filter
        if (isset($filters['from_date'])) {
            $query->where('transaction_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('transaction_date', '<=', $filters['to_date']);
        }

        // Calculate opening balance (before from_date)
        $openingBalance = 0;
        if (isset($filters['from_date'])) {
            $openingDebit = LedgerEntry::where('customer_id', $customerId)
                ->where('transaction_date', '<', $filters['from_date'])
                ->where('type', 'debit')
                ->sum('amount');

            $openingCredit = LedgerEntry::where('customer_id', $customerId)
                ->where('transaction_date', '<', $filters['from_date'])
                ->where('type', 'credit')
                ->sum('amount');

            $openingBalance = $openingDebit - $openingCredit;
        }

        // Get entries
        $entries = $query->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate running balance
        $runningBalance = $openingBalance;
        $statementData = [];

        foreach ($entries as $entry) {
            if ($entry->type === 'debit') {
                $runningBalance += $entry->amount;
            } else {
                $runningBalance -= $entry->amount;
            }

            $statementData[] = [
                'id' => $entry->id,
                'transaction_date' => $entry->transaction_date->toDateString(),
                'type' => $entry->type,
                'type_arabic' => $entry->type === 'debit' ? 'علية (مدين)' : 'له (دائن)',
                'reference_type' => $entry->reference_type,
                'reference_id' => $entry->reference_id,
                'description' => $entry->description,
                'debit' => $entry->type === 'debit' ? $entry->amount : 0,
                'credit' => $entry->type === 'credit' ? $entry->amount : 0,
                'balance' => $runningBalance,
            ];
        }

        // Calculate totals
        $totalDebit = $entries->where('type', 'debit')->sum('amount');
        $totalCredit = $entries->where('type', 'credit')->sum('amount');

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'code' => $customer->code,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
            ],
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'entries' => $statementData,
            'totals' => [
                'debit' => $totalDebit,
                'credit' => $totalCredit,
                'net_movement' => $totalDebit - $totalCredit,
            ],
            'generated_at' => now()->toDateTimeString(),
            'filters' => $filters,
        ];
    }

    /**
     * مقارنة أرصدة العملاء بين فترتين
     * Customer Balance Comparison Between Periods
     * 
     * @param array $filters
     * @return array
     */
    public function compareCustomerBalances(array $filters = []): array
    {
        $currentPeriodEnd = isset($filters['current_end']) 
            ? Carbon::parse($filters['current_end']) 
            : now();

        $previousPeriodEnd = isset($filters['previous_end']) 
            ? Carbon::parse($filters['previous_end']) 
            : $currentPeriodEnd->copy()->subMonth();

        $customers = Customer::where('is_active', true)->get();

        $comparisonData = [];

        foreach ($customers as $customer) {
            // Calculate balance at current period end
            $currentDebit = LedgerEntry::where('customer_id', $customer->id)
                ->where('type', 'debit')
                ->where('transaction_date', '<=', $currentPeriodEnd)
                ->sum('amount');

            $currentCredit = LedgerEntry::where('customer_id', $customer->id)
                ->where('type', 'credit')
                ->where('transaction_date', '<=', $currentPeriodEnd)
                ->sum('amount');

            $currentBalance = $currentDebit - $currentCredit;

            // Calculate balance at previous period end
            $previousDebit = LedgerEntry::where('customer_id', $customer->id)
                ->where('type', 'debit')
                ->where('transaction_date', '<=', $previousPeriodEnd)
                ->sum('amount');

            $previousCredit = LedgerEntry::where('customer_id', $customer->id)
                ->where('type', 'credit')
                ->where('transaction_date', '<=', $previousPeriodEnd)
                ->sum('amount');

            $previousBalance = $previousDebit - $previousCredit;

            // Calculate change
            $change = $currentBalance - $previousBalance;
            $changePercentage = $previousBalance != 0 
                ? ($change / abs($previousBalance)) * 100 
                : 0;

            // Only include customers with transactions
            if ($currentBalance != 0 || $previousBalance != 0) {
                $comparisonData[] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_code' => $customer->code,
                    'previous_balance' => $previousBalance,
                    'current_balance' => $currentBalance,
                    'change' => $change,
                    'change_percentage' => round($changePercentage, 2),
                    'trend' => $change > 0 ? 'زيادة' : ($change < 0 ? 'نقصان' : 'ثابت'),
                ];
            }
        }

        // Sort by absolute change (largest changes first)
        usort($comparisonData, function ($a, $b) {
            return abs($b['change']) <=> abs($a['change']);
        });

        return [
            'comparisons' => $comparisonData,
            'period_info' => [
                'previous_period_end' => $previousPeriodEnd->toDateString(),
                'current_period_end' => $currentPeriodEnd->toDateString(),
            ],
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * إحصائيات نشاط العملاء
     * Customer Activity Statistics
     * 
     * @return array
     */
    public function getCustomerActivityStatistics(): array
    {
        $customers = Customer::where('is_active', true)->get();

        $stats = [
            'total_customers' => $customers->count(),
            'very_active' => 0,    // Last 30 days
            'active' => 0,          // Last 90 days
            'moderate' => 0,        // Last 180 days
            'inactive' => 0,        // More than 180 days
            'never_transacted' => 0,
            'by_balance_range' => [
                'large_debit' => 0,      // > 1000
                'debit' => 0,            // 100-1000
                'balanced' => 0,         // -100 to 100
                'credit' => 0,           // -1000 to -100
                'large_credit' => 0,     // < -1000
            ],
        ];

        foreach ($customers as $customer) {
            $lastTransaction = $customer->ledgerEntries()
                ->latest('transaction_date')
                ->first();

            if (!$lastTransaction) {
                $stats['never_transacted']++;
                $stats['inactive']++;
                continue;
            }

            $daysSince = now()->diffInDays($lastTransaction->transaction_date);

            if ($daysSince <= 30) {
                $stats['very_active']++;
            } elseif ($daysSince <= 90) {
                $stats['active']++;
            } elseif ($daysSince <= 180) {
                $stats['moderate']++;
            } else {
                $stats['inactive']++;
            }

            // Calculate balance
            $balance = $customer->ledgerEntries()
                ->sum(DB::raw("CASE WHEN type = 'debit' THEN amount ELSE -amount END"));

            if ($balance > 1000) {
                $stats['by_balance_range']['large_debit']++;
            } elseif ($balance > 100) {
                $stats['by_balance_range']['debit']++;
            } elseif ($balance >= -100) {
                $stats['by_balance_range']['balanced']++;
            } elseif ($balance >= -1000) {
                $stats['by_balance_range']['credit']++;
            } else {
                $stats['by_balance_range']['large_credit']++;
            }
        }

        return [
            'statistics' => $stats,
            'generated_at' => now()->toDateTimeString(),
        ];
    }
}
