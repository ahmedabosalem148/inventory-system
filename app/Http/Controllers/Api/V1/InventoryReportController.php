<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InventoryReportService;
use App\Http\Requests\InventoryReportRequest;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function __construct(
        private InventoryReportService $reportService
    ) {}

    /**
     * تقرير إجمالي المخزون
     * Total Inventory Report
     * 
     * GET /api/v1/reports/inventory/total
     */
    public function totalInventory(InventoryReportRequest $request)
    {
        $filters = $request->only(['branch_id', 'category_id']);

        $report = $this->reportService->getTotalInventoryReport($filters);

        return response()->json($report);
    }

    /**
     * تقرير حركة منتج
     * Product Movement Report
     * 
     * GET /api/v1/reports/inventory/product-movement/{productId}
     */
    public function productMovement(InventoryReportRequest $request, int $productId)
    {
        $filters = $request->only(['from_date', 'to_date', 'type']);
        $branchId = $request->input('branch_id');

        $report = $this->reportService->getProductMovementReport($productId, $branchId, $filters);

        return response()->json($report);
    }

    /**
     * تقرير المخزون المنخفض
     * Low Stock Report
     * 
     * GET /api/v1/reports/inventory/low-stock
     */
    public function lowStock(InventoryReportRequest $request)
    {
        $filters = $request->only(['branch_id', 'category_id', 'threshold']);

        $report = $this->reportService->getLowStockReport($filters);

        return response()->json($report);
    }

    /**
     * ملخص المخزون السريع
     * Quick Inventory Summary
     * 
     * GET /api/v1/reports/inventory/summary
     */
    public function summary(InventoryReportRequest $request)
    {
        $summary = $this->reportService->getInventorySummary();

        return response()->json($summary);
    }
}
