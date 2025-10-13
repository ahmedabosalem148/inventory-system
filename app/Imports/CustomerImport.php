<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerImport
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
                    // Expecting: customer_code, name, phone, address, opening_balance
                    if (count($row) < 5) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => 'بيانات ناقصة: يجب وجود 5 أعمدة (الكود، الاسم، الهاتف، العنوان، الرصيد الافتتاحي)',
                        ];
                        continue;
                    }

                    $customerCode = trim($row[0]);
                    $name = trim($row[1]);
                    $phone = trim($row[2]);
                    $address = trim($row[3]);
                    $openingBalance = trim($row[4]);

                    // Validation
                    if (empty($customerCode) || empty($name)) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => 'الكود والاسم مطلوبان',
                        ];
                        continue;
                    }

                    // Check if customer code already exists
                    if (Customer::where('code', $customerCode)->exists()) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => "كود العميل موجود مسبقاً: {$customerCode}",
                        ];
                        continue;
                    }

                    // Validate opening balance
                    if (!is_numeric($openingBalance)) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => "الرصيد الافتتاحي يجب أن يكون رقم (القيمة الحالية: {$openingBalance})",
                        ];
                        continue;
                    }

                    $openingBalance = (float) $openingBalance;

                    // Create customer
                    $customer = Customer::create([
                        'code' => $customerCode,
                        'name' => $name,
                        'phone' => $phone ?: null,
                        'address' => $address ?: null,
                        'is_active' => true,
                        'last_activity_at' => now(),
                    ]);

                    // Create opening balance entry if not zero
                    if ($openingBalance != 0) {
                        $description = 'رصيد افتتاحي';
                        
                        // Positive balance = debit (customer owes us)
                        // Negative balance = credit (we owe customer)
                        LedgerEntry::create([
                            'customer_id' => $customer->id,
                            'type' => $openingBalance > 0 ? 'debit' : 'credit',
                            'amount' => abs($openingBalance),
                            'description' => $description,
                            'reference_type' => 'OPENING',
                            'reference_id' => 0,
                        ]);

                        $balanceMsg = $openingBalance > 0 
                            ? "رصيد افتتاحي علية: " . abs($openingBalance) . " ج.م"
                            : "رصيد افتتاحي له: " . abs($openingBalance) . " ج.م";
                    } else {
                        $balanceMsg = "بدون رصيد افتتاحي";
                    }

                    $this->results[] = [
                        'row' => $rowNumber,
                        'status' => 'success',
                        'message' => "تم إنشاء العميل: {$name} ({$customerCode}) - {$balanceMsg}",
                    ];

                } catch (\Exception $e) {
                    $this->results[] = [
                        'row' => $rowNumber,
                        'status' => 'error',
                        'message' => 'خطأ: ' . $e->getMessage(),
                    ];
                    Log::error('Customer import error on row ' . $rowNumber, [
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
