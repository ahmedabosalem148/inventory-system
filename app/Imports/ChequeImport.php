<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Cheque;
use App\Models\IssueVoucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChequeImport
{
    protected $results = [];

    public function importFromCsv($filePath)
    {
        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Skip header row
        
        DB::transaction(function () use ($file) {
            $rowNumber = 1;
            
            while (($row = fgetcsv($file)) !== false) {
                $rowNumber++;
                
                try {
                    // Expecting: customer_code, cheque_number, bank, due_date, amount, linked_issue_voucher_id
                    if (count($row) < 5) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => 'بيانات ناقصة: يجب وجود 5 أعمدة على الأقل (كود العميل، رقم الشيك، البنك، تاريخ الاستحقاق، المبلغ)',
                        ];
                        continue;
                    }

                    $customerCode = trim($row[0]);
                    $chequeNumber = trim($row[1]);
                    $bank = trim($row[2]);
                    $dueDate = trim($row[3]);
                    $amount = trim($row[4]);
                    $linkedVoucherId = isset($row[5]) ? trim($row[5]) : null;

                    // Validation
                    if (empty($customerCode) || empty($chequeNumber) || empty($bank) || empty($dueDate) || empty($amount)) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => 'الحقول الأساسية مطلوبة (كود العميل، رقم الشيك، البنك، تاريخ الاستحقاق، المبلغ)',
                        ];
                        continue;
                    }

                    // Find customer
                    $customer = Customer::where('code', $customerCode)->first();
                    if (!$customer) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => "كود العميل غير موجود: {$customerCode}",
                        ];
                        continue;
                    }

                    // Check if cheque number already exists
                    if (Cheque::where('cheque_number', $chequeNumber)->exists()) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => "رقم الشيك موجود مسبقاً: {$chequeNumber}",
                        ];
                        continue;
                    }

                    // Validate amount
                    if (!is_numeric($amount) || $amount <= 0) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => "المبلغ يجب أن يكون رقم موجب (القيمة الحالية: {$amount})",
                        ];
                        continue;
                    }

                    // Parse due date
                    try {
                        $parsedDueDate = Carbon::parse($dueDate);
                    } catch (\Exception $e) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => "تاريخ الاستحقاق غير صحيح: {$dueDate}. استخدم صيغة: YYYY-MM-DD",
                        ];
                        continue;
                    }

                    // Validate linked voucher if provided
                    $linkedVoucher = null;
                    if (!empty($linkedVoucherId)) {
                        $linkedVoucher = IssueVoucher::find($linkedVoucherId);
                        if (!$linkedVoucher) {
                            $this->results[] = [
                                'row' => $rowNumber,
                                'status' => 'warning',
                                'message' => "رقم الفاتورة {$linkedVoucherId} غير موجود - سيتم إنشاء الشيك بدون ربط",
                            ];
                            $linkedVoucherId = null;
                        }
                    }

                    // Create cheque
                    $cheque = Cheque::create([
                        'customer_id' => $customer->id,
                        'cheque_number' => $chequeNumber,
                        'bank' => $bank,
                        'due_date' => $parsedDueDate,
                        'amount' => (float) $amount,
                        'status' => 'PENDING',
                        'linked_issue_voucher_id' => $linkedVoucherId,
                        'cleared_at' => null,
                    ]);

                    $voucherMsg = $linkedVoucherId ? " (مرتبط بفاتورة #{$linkedVoucherId})" : "";
                    
                    $this->results[] = [
                        'row' => $rowNumber,
                        'status' => 'success',
                        'message' => "تم إنشاء الشيك: {$chequeNumber} - {$customer->name} - {$amount} ج.م - استحقاق: {$parsedDueDate->format('Y-m-d')}{$voucherMsg}",
                    ];

                } catch (\Exception $e) {
                    $this->results[] = [
                        'row' => $rowNumber,
                        'status' => 'error',
                        'message' => 'خطأ: ' . $e->getMessage(),
                    ];
                    Log::error('Cheque import error on row ' . $rowNumber, [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
        
        fclose($file);
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
