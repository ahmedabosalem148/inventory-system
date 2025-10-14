<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SalesReportController extends Controller
{
    protected $reportService;

    public function __construct(SalesReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * تقرير المبيعات حسب الفترة
     * GET /api/v1/reports/sales/period
     */
    public function byPeriod(Request $request): JsonResponse
    {
        $filters = $request->only([
            'from_date',
            'to_date',
            'branch_id',
            'customer_id',
            'voucher_type'
        ]);

        $report = $this->reportService->getSalesByPeriod($filters);

        return response()->json($report);
    }

    /**
     * تقرير المبيعات حسب المنتج
     * GET /api/v1/reports/sales/by-product
     */
    public function byProduct(Request $request): JsonResponse
    {
        $filters = $request->only([
            'from_date',
            'to_date',
            'branch_id',
            'category_id'
        ]);

        $report = $this->reportService->getSalesByProduct($filters);

        return response()->json($report);
    }

    /**
     * تقرير المبيعات حسب الفئة
     * GET /api/v1/reports/sales/by-category
     */
    public function byCategory(Request $request): JsonResponse
    {
        $filters = $request->only([
            'from_date',
            'to_date',
            'branch_id'
        ]);

        $report = $this->reportService->getSalesByCategory($filters);

        return response()->json($report);
    }

    /**
     * مقارنة المبيعات بين فترتين
     * GET /api/v1/reports/sales/comparison
     */
    public function comparison(Request $request): JsonResponse
    {
        $filters = $request->only([
            'current_from',
            'current_to',
            'branch_id'
        ]);

        $report = $this->reportService->compareSalesBetweenPeriods($filters);

        return response()->json($report);
    }

    /**
     * أفضل العملاء
     * GET /api/v1/reports/sales/top-customers
     */
    public function topCustomers(Request $request): JsonResponse
    {
        $filters = $request->only([
            'from_date',
            'to_date',
            'branch_id',
            'limit'
        ]);

        $report = $this->reportService->getTopCustomers($filters);

        return response()->json($report);
    }

    /**
     * ملخص المبيعات
     * GET /api/v1/reports/sales/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $filters = $request->only(['branch_id']);

        $report = $this->reportService->getSalesSummary($filters);

        return response()->json($report);
    }
}
