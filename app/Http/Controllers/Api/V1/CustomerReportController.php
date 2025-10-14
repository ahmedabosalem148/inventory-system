<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CustomerReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerReportController extends Controller
{
    protected $reportService;

    public function __construct(CustomerReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * تقرير أرصدة جميع العملاء
     * GET /api/v1/reports/customers/balances
     */
    public function balances(Request $request): JsonResponse
    {
        $filters = $request->only(['customer_id']);

        $report = $this->reportService->getCustomerBalancesReport($filters);

        return response()->json($report);
    }

    /**
     * كشف حساب عميل واحد
     * GET /api/v1/reports/customers/{customerId}/statement
     */
    public function statement(Request $request, int $customerId): JsonResponse
    {
        $filters = $request->only(['from_date', 'to_date']);

        $report = $this->reportService->getCustomerStatement($customerId, $filters);

        return response()->json($report);
    }

    /**
     * مقارنة أرصدة العملاء بين فترتين
     * GET /api/v1/reports/customers/comparison
     */
    public function comparison(Request $request): JsonResponse
    {
        $filters = $request->only(['current_end', 'previous_end']);

        $report = $this->reportService->compareCustomerBalances($filters);

        return response()->json($report);
    }

    /**
     * إحصائيات نشاط العملاء
     * GET /api/v1/reports/customers/activity
     */
    public function activity(Request $request): JsonResponse
    {
        $report = $this->reportService->getCustomerActivityStatistics();

        return response()->json($report);
    }
}
