<?php

namespace App\Services;

use App\Models\Cheque;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class ChequeService
{
    public function __construct(
        private LedgerService $ledgerService
    ) {}

    /**
     * إنشاء شيك جديد (حالة: PENDING)
     * 
     * @param array $data
     * @return Cheque
     * @throws Exception
     */
    public function createCheque(array $data): Cheque
    {
        DB::beginTransaction();
        
        try {
            // Validate customer exists
            $customer = Customer::findOrFail($data['customer_id']);
            
            // Create cheque in PENDING status
            $cheque = Cheque::create([
                'customer_id' => $data['customer_id'],
                'cheque_number' => $data['cheque_number'],
                'bank_name' => $data['bank_name'],
                'due_date' => $data['due_date'],
                'amount' => $data['amount'],
                'status' => 'PENDING',
                'issue_voucher_id' => $data['issue_voucher_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // عند إنشاء الشيك، نسجل دين على العميل (عليه)
            // لأن الشيك يمثل مبلغ مستحق من العميل
            $this->ledgerService->recordChequeReceived(
                customerId: $customer->id,
                amount: $cheque->amount,
                chequeId: $cheque->id,
                description: "شيك رقم {$cheque->cheque_number} - {$cheque->bank_name}",
                date: $cheque->due_date
            );

            DB::commit();
            
            return $cheque->load(['customer', 'creator']);
            
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("فشل إنشاء الشيك: " . $e->getMessage());
        }
    }

    /**
     * صرف شيك (PENDING → CLEARED)
     * 
     * @param Cheque $cheque
     * @param array $data
     * @return Cheque
     * @throws Exception
     */
    public function clearCheque(Cheque $cheque, array $data = []): Cheque
    {
        DB::beginTransaction();
        
        try {
            // Validate current status
            if ($cheque->status !== 'PENDING') {
                throw new Exception("لا يمكن صرف الشيك. الحالة الحالية: {$cheque->status}");
            }

            // Update cheque status
            $cheque->update([
                'status' => 'CLEARED',
                'cleared_at' => $data['cleared_at'] ?? now()->toDateString(),
                'cleared_by' => Auth::id(),
                'notes' => $data['notes'] ?? $cheque->notes,
            ]);

            // تسجيل في دفتر العميل: الشيك تم صرفه (سداد - له)
            // الشيك عند صرفه يقلل من الدين على العميل
            $this->ledgerService->recordChequeCleared(
                customerId: $cheque->customer_id,
                amount: $cheque->amount,
                chequeId: $cheque->id,
                description: "صرف شيك رقم {$cheque->cheque_number} - {$cheque->bank_name}",
                date: $cheque->cleared_at
            );

            DB::commit();
            
            return $cheque->fresh(['customer', 'creator', 'clearedBy']);
            
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("فشل صرف الشيك: " . $e->getMessage());
        }
    }

    /**
     * إرجاع شيك (PENDING → RETURNED)
     * 
     * @param Cheque $cheque
     * @param string $reason
     * @param array $data
     * @return Cheque
     * @throws Exception
     */
    public function returnCheque(Cheque $cheque, string $reason, array $data = []): Cheque
    {
        DB::beginTransaction();
        
        try {
            // Validate current status
            if ($cheque->status !== 'PENDING') {
                throw new Exception("لا يمكن إرجاع الشيك. الحالة الحالية: {$cheque->status}");
            }

            // Update cheque status
            $cheque->update([
                'status' => 'RETURNED',
                'return_reason' => $reason,
                'notes' => $data['notes'] ?? $cheque->notes,
            ]);

            // تسجيل في دفتر العميل: الشيك مرتجع (عكس القيد الأصلي)
            // عند إرجاع الشيك، نحتاج نعكس القيد الأول ونسجل الإرجاع
            $this->ledgerService->recordChequeReturned(
                customerId: $cheque->customer_id,
                amount: $cheque->amount,
                chequeId: $cheque->id,
                description: "إرجاع شيك رقم {$cheque->cheque_number} - السبب: {$reason}",
                date: now()->toDateString()
            );

            DB::commit();
            
            return $cheque->fresh(['customer', 'creator']);
            
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("فشل إرجاع الشيك: " . $e->getMessage());
        }
    }

    /**
     * إلغاء شيك (حذف + عكس القيود)
     * 
     * @param Cheque $cheque
     * @return bool
     * @throws Exception
     */
    public function cancelCheque(Cheque $cheque): bool
    {
        DB::beginTransaction();
        
        try {
            // Only pending cheques can be cancelled
            if ($cheque->status !== 'PENDING') {
                throw new Exception("يمكن إلغاء الشيكات في حالة PENDING فقط");
            }

            // عكس القيد في دفتر العميل
            $this->ledgerService->reverseChequeEntry(
                customerId: $cheque->customer_id,
                amount: $cheque->amount,
                chequeId: $cheque->id,
                description: "إلغاء شيك رقم {$cheque->cheque_number}",
                date: now()->toDateString()
            );

            // Delete the cheque
            $cheque->delete();

            DB::commit();
            
            return true;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("فشل إلغاء الشيك: " . $e->getMessage());
        }
    }

    /**
     * Get cheque statistics for a customer
     * 
     * @param int $customerId
     * @return array
     */
    public function getCustomerChequeStats(int $customerId): array
    {
        $cheques = Cheque::where('customer_id', $customerId)->get();

        return [
            'total_cheques' => $cheques->count(),
            'pending_count' => $cheques->where('status', 'PENDING')->count(),
            'cleared_count' => $cheques->where('status', 'CLEARED')->count(),
            'returned_count' => $cheques->where('status', 'RETURNED')->count(),
            'pending_amount' => $cheques->where('status', 'PENDING')->sum('amount'),
            'cleared_amount' => $cheques->where('status', 'CLEARED')->sum('amount'),
            'returned_amount' => $cheques->where('status', 'RETURNED')->sum('amount'),
            'total_amount' => $cheques->sum('amount'),
        ];
    }

    /**
     * Get overdue cheques (due date passed but still PENDING)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverdueCheques()
    {
        return Cheque::with(['customer', 'creator'])
            ->where('status', 'PENDING')
            ->where('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Get upcoming cheques (due within X days)
     * 
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUpcomingCheques(int $days = 7)
    {
        return Cheque::with(['customer', 'creator'])
            ->where('status', 'PENDING')
            ->whereBetween('due_date', [
                now()->toDateString(),
                now()->addDays($days)->toDateString()
            ])
            ->orderBy('due_date')
            ->get();
    }
}
