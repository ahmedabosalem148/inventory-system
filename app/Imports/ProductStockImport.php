<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Branch;
use App\Models\ProductBranch;
use App\Services\InventoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductStockImport
{

    protected $results = [];
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function importFromCsv($filePath)
    {
        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Skip header row
        
        DB::transaction(function () use ($file) {
            $rowNumber = 1;
            
            while (($row = fgetcsv($file)) !== false) {
                $rowNumber++;
                
                try {
                    // Expecting: branch_code, sku, qty_units
                    if (count($row) < 3) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => 'بيانات ناقصة: يجب وجود 3 أعمدة (كود الفرع، SKU، الكمية)',
                        ];
                        continue;
                    }

                    $branchCode = trim($row[0]);
                    $sku = trim($row[1]);
                    $qty = trim($row[2]);

                    // Validation
                    if (empty($branchCode) || empty($sku) || empty($qty)) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => 'بيانات ناقصة: يجب ملء جميع الحقول',
                        ];
                        continue;
                    }

                    if (!is_numeric($qty) || $qty < 0) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => "الكمية يجب أن تكون رقم موجب (القيمة الحالية: {$qty})",
                        ];
                        continue;
                    }

                    // Find branch
                    $branch = Branch::where('code', $branchCode)->first();
                    if (!$branch) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => "كود الفرع غير موجود: {$branchCode}",
                        ];
                        continue;
                    }

                    // Find product
                    $product = Product::where('sku', $sku)->first();
                    if (!$product) {
                        $this->results[] = [
                            'row' => $rowNumber,
                            'status' => 'error',
                            'message' => "كود المنتج (SKU) غير موجود: {$sku}",
                        ];
                        continue;
                    }

                    // Apply inventory movement
                    $this->inventoryService->applyMovement(
                        type: 'ADD',
                        branch: $branch,
                        product: $product,
                        qty: (int) $qty,
                        refTable: 'OPENING',
                        refId: 0,
                        unitPrice: 0
                    );

                    $this->results[] = [
                        'row' => $rowNumber,
                        'status' => 'success',
                        'message' => "تم استيراد {$qty} وحدة من {$product->name} للفرع {$branch->name}",
                    ];

                } catch (\Exception $e) {
                    $this->results[] = [
                        'row' => $rowNumber,
                        'status' => 'error',
                        'message' => 'خطأ: ' . $e->getMessage(),
                    ];
                    Log::error('Import error on row ' . $rowNumber, [
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
