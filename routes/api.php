<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InventoryCountController;
use App\Http\Controllers\Api\V1\InventoryMovementController;
use App\Http\Controllers\Api\V1\IssueVoucherController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ReturnVoucherController;
use App\Http\Controllers\Api\V1\UserBranchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Version 1)
|--------------------------------------------------------------------------
|
| RESTful API for Inventory Management System
| Base URL: /api/v1
| Authentication: Laravel Sanctum (Personal Access Tokens)
| Rate Limiting: 60 requests per minute
|
*/

// ============================================================================
// Public Routes (لا تحتاج authentication)
// ============================================================================
Route::prefix('v1')->group(function () {
    
    // Authentication endpoints
    Route::post('auth/login', [AuthController::class, 'login'])->name('api.login');
    
    // Health check
    Route::get('health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => 'v1',
        ]);
    })->name('api.health');
});

// ============================================================================
// Protected Routes (تحتاج authentication token)
// ============================================================================
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->group(function () {
    
    // ========================================================================
    // Authentication & User Profile
    // ========================================================================
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::put('profile', [AuthController::class, 'updateProfile'])->name('profile.update');
        Route::post('change-password', [AuthController::class, 'changePassword'])->name('password.change');
    });

    // ========================================================================
    // User Branch Management (إدارة مخازن المستخدم)
    // ========================================================================
    Route::prefix('user')->name('api.user.')->group(function () {
        Route::get('branches', [UserBranchController::class, 'index'])->name('branches');
        Route::post('switch-branch', [UserBranchController::class, 'switchBranch'])->name('switch-branch');
        Route::get('current-branch', [UserBranchController::class, 'currentBranch'])->name('current-branch');
    });

    // ========================================================================
    // Dashboard & Analytics
    // ========================================================================
    Route::prefix('dashboard')->name('api.dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('stats', [DashboardController::class, 'stats'])->name('stats');
        Route::get('low-stock', [DashboardController::class, 'lowStock'])->name('low-stock');
    });

    // ========================================================================
    // Core Resources (CRUD operations)
    // ========================================================================
    Route::apiResource('branches', BranchController::class)->names('api.branches');
    
    // Categories
    Route::get('categories', function () {
        return response()->json([
            'data' => \App\Models\Category::orderBy('name')->get(),
        ]);
    })->name('api.categories.index');
    
    // Product branch minimum stock management
    Route::get('products/{product}/branch-min-stock', [ProductController::class, 'getBranchMinStock'])
        ->name('api.products.branch-min-stock.index');
    Route::put('products/{product}/branch-min-stock', [ProductController::class, 'updateBranchMinStock'])
        ->name('api.products.branch-min-stock.update');
    
    Route::apiResource('products', ProductController::class)->names('api.products');

    // Customer Management (TASK-009) - Must be before apiResource
    Route::get('customers-balances', [CustomerController::class, 'getCustomersWithBalances'])
        ->name('api.customers.balances');
    Route::get('customers-statistics', [CustomerController::class, 'getStatistics'])
        ->name('api.customers.statistics');
    Route::get('customers/{customer}/statement', [CustomerController::class, 'getStatement'])
        ->name('api.customers.statement');
    Route::get('customers/{customer}/statement/pdf', [CustomerController::class, 'exportStatementPDF'])
        ->name('api.customers.statement.pdf');
    Route::get('customers/{customer}/statement/excel', [CustomerController::class, 'exportStatementExcel'])
        ->name('api.customers.statement.excel');
    Route::get('customers/{customer}/balance', [CustomerController::class, 'getBalance'])
        ->name('api.customers.balance');
    Route::get('customers/{customer}/activity', [CustomerController::class, 'getActivity'])
        ->name('api.customers.activity');
    Route::get('customers/{customer}/ledger', [CustomerController::class, 'getLedger'])
        ->name('api.customers.ledger');
    
    Route::apiResource('customers', CustomerController::class)->names('api.customers');

    // ========================================================================
    // Suppliers & Purchase Orders
    // ========================================================================
    Route::apiResource('suppliers', SupplierController::class)->names('api.suppliers');
    Route::get('suppliers-statistics', [SupplierController::class, 'statistics'])
        ->name('api.suppliers.statistics');
    
    Route::apiResource('purchase-orders', PurchaseOrderController::class)->names('api.purchase-orders');

    // ========================================================================
    // Vouchers & Transactions
    // ========================================================================
    Route::apiResource('issue-vouchers', IssueVoucherController::class)
        ->names('api.issue-vouchers');
    
    Route::apiResource('return-vouchers', ReturnVoucherController::class)
        ->names('api.return-vouchers');
    
    // Voucher actions
    Route::get('issue-vouchers/{issueVoucher}/print', [IssueVoucherController::class, 'print'])
        ->name('api.issue-vouchers.print');
    
    // Issue vouchers statistics
    Route::get('issue-vouchers-stats', [IssueVoucherController::class, 'stats'])
        ->name('api.issue-vouchers.stats');
    Route::get('return-vouchers/{returnVoucher}/print', [ReturnVoucherController::class, 'print'])
        ->name('api.return-vouchers.print');

    // ========================================================================
    // Inventory Counting
    // ========================================================================
    Route::apiResource('inventory-counts', InventoryCountController::class)->names('api.inventory-counts');
    Route::post('inventory-counts/{inventoryCount}/submit', [InventoryCountController::class, 'submit'])
        ->name('api.inventory-counts.submit');
    Route::post('inventory-counts/{inventoryCount}/approve', [InventoryCountController::class, 'approve'])
        ->name('api.inventory-counts.approve');
    Route::post('inventory-counts/{inventoryCount}/reject', [InventoryCountController::class, 'reject'])
        ->name('api.inventory-counts.reject');

    // ========================================================================
    // Payments & Cheques
    // ========================================================================
    Route::apiResource('payments', PaymentController::class)->names('api.payments');
    
    Route::prefix('cheques')->name('api.cheques.')->group(function () {
        Route::get('/', [PaymentController::class, 'getCheques'])->name('index');
        Route::get('stats', [PaymentController::class, 'chequeStats'])->name('stats');
        Route::get('pending', [PaymentController::class, 'pendingCheques'])->name('pending');
        Route::get('overdue', [PaymentController::class, 'overdueCheques'])->name('overdue');
        Route::get('cleared', [PaymentController::class, 'clearedCheques'])->name('cleared');
        Route::post('{cheque}/clear', [PaymentController::class, 'clearCheque'])->name('clear');
        Route::post('{cheque}/bounce', [PaymentController::class, 'bounceСheque'])->name('bounce');
    });

    // ========================================================================
    // Inventory Movements
    // ========================================================================
    Route::prefix('inventory-movements')->name('api.inventory-movements.')->group(function () {
        // List and view movements
        Route::get('/', [InventoryMovementController::class, 'index'])->name('index');
        Route::get('{inventoryMovement}', [InventoryMovementController::class, 'show'])->name('show');
        
        // Stock operations
        Route::post('add', [InventoryMovementController::class, 'addStock'])->name('add');
        Route::post('issue', [InventoryMovementController::class, 'issueStock'])->name('issue');
        Route::post('transfer', [InventoryMovementController::class, 'transferStock'])->name('transfer');
        Route::post('adjust', [InventoryMovementController::class, 'adjustStock'])->name('adjust');
        
        // Reports and summaries
        Route::get('reports/summary', [InventoryMovementController::class, 'summary'])->name('summary');
        Route::get('reports/low-stock', [InventoryMovementController::class, 'lowStock'])->name('low-stock');
    });

    // ========================================================================
    // Reports
    // ========================================================================
    Route::prefix('reports')->name('api.reports.')->group(function () {
        // Inventory reports
        Route::get('inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('inventory/movements', [ReportController::class, 'movements'])->name('movements');
        
        // Stock Valuation Report
        Route::get('stock-valuation', [ReportController::class, 'stockValuation'])->name('stock-valuation');
        Route::get('stock-valuation/pdf', [ReportController::class, 'stockValuationPDF'])->name('stock-valuation-pdf');
        Route::get('stock-valuation/excel', [ReportController::class, 'stockValuationExcel'])->name('stock-valuation-excel');
        
        // Stock Summary Report
        Route::get('stock-summary', [ReportController::class, 'stockSummary'])->name('stock-summary');
        Route::get('stock-summary/pdf', [ReportController::class, 'stockSummaryPDF'])->name('stock-summary-pdf');
        Route::get('stock-summary/excel', [ReportController::class, 'stockSummaryExcel'])->name('stock-summary-excel');
        
        // Low Stock Report
        Route::get('low-stock', [ReportController::class, 'lowStock'])->name('low-stock');
        Route::get('low-stock/pdf', [ReportController::class, 'lowStockPDF'])->name('low-stock-pdf');
        Route::get('low-stock/excel', [ReportController::class, 'lowStockExcel'])->name('low-stock-excel');
        
        // Product Movement Report
        Route::get('product-movement', [ReportController::class, 'productMovement'])->name('product-movement');
        Route::get('product-movement/pdf', [ReportController::class, 'productMovementPDF'])->name('product-movement-pdf');
        Route::get('product-movement/excel', [ReportController::class, 'productMovementExcel'])->name('product-movement-excel');
        
        // Customer Balances Report
        Route::get('customer-balances', [ReportController::class, 'customerBalances'])->name('customer-balances');
        Route::get('customer-balances/pdf', [ReportController::class, 'customerBalancesPDF'])->name('customer-balances-pdf');
        Route::get('customer-balances/excel', [ReportController::class, 'customerBalancesExcel'])->name('customer-balances-excel');
        
        // Customer Statement Report
        Route::get('customers/{customer}/statement', [ReportController::class, 'customerStatement'])
            ->name('customer-statement');
        
        // Sales Report
        Route::get('sales-summary', [ReportController::class, 'salesReport'])->name('sales-report');
        Route::get('sales-summary/pdf', [ReportController::class, 'salesReportPDF'])->name('sales-report-pdf');
        Route::get('sales-summary/excel', [ReportController::class, 'salesReportExcel'])->name('sales-report-excel');
        
        // Old routes - keeping for compatibility
        Route::get('customers/balances', [ReportController::class, 'customerBalances'])
            ->name('customer-balances-old');
        
        // Sales reports
        Route::get('sales/summary', [ReportController::class, 'salesSummary'])->name('sales-summary');
        Route::get('sales/by-product', [ReportController::class, 'salesByProduct'])->name('sales-by-product');
        Route::get('sales/by-branch', [ReportController::class, 'salesByBranch'])->name('sales-by-branch');
        
        // Financial reports
        Route::get('financial/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        
        // Inventory reports
        Route::get('inventory/total', [\App\Http\Controllers\Api\V1\InventoryReportController::class, 'totalInventory'])->name('inventory-total');
        Route::get('inventory/product-movement/{productId}', [\App\Http\Controllers\Api\V1\InventoryReportController::class, 'productMovement'])->name('product-movement');
        Route::get('inventory/low-stock', [\App\Http\Controllers\Api\V1\InventoryReportController::class, 'lowStock'])->name('low-stock');
        Route::get('inventory/summary', [\App\Http\Controllers\Api\V1\InventoryReportController::class, 'summary'])->name('inventory-summary');
        
        // Customer reports
        Route::get('customers/balances', [\App\Http\Controllers\Api\V1\CustomerReportController::class, 'balances'])->name('customers-balances');
        Route::get('customers/{customerId}/statement', [\App\Http\Controllers\Api\V1\CustomerReportController::class, 'statement'])->name('customer-statement');
        Route::get('customers/comparison', [\App\Http\Controllers\Api\V1\CustomerReportController::class, 'comparison'])->name('customers-comparison');
        Route::get('customers/activity', [\App\Http\Controllers\Api\V1\CustomerReportController::class, 'activity'])->name('customers-activity');
        
        // Sales reports
        Route::get('sales/period', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'byPeriod'])->name('sales-period');
        Route::get('sales/by-product', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'byProduct'])->name('sales-by-product');
        Route::get('sales/by-category', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'byCategory'])->name('sales-by-category');
        Route::get('sales/comparison', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'comparison'])->name('sales-comparison');
        Route::get('sales/top-customers', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'topCustomers'])->name('sales-top-customers');
        Route::get('sales/summary', [\App\Http\Controllers\Api\V1\SalesReportController::class, 'summary'])->name('sales-summary');
    });

    // ========================================================================
    // Print System (VALIDATION-PHASE-0)
    // ========================================================================
    Route::prefix('print')->name('api.print.')->group(function () {
        Route::get('issue-voucher/{id}', [\App\Http\Controllers\Api\V1\PrintController::class, 'printIssueVoucher'])
            ->name('issue-voucher')
            ->middleware('can:print-issue-vouchers');
        
        Route::get('return-voucher/{id}', [\App\Http\Controllers\Api\V1\PrintController::class, 'printReturnVoucher'])
            ->name('return-voucher')
            ->middleware('can:print-return-vouchers');
        
        Route::get('purchase-order/{id}', [\App\Http\Controllers\Api\V1\PrintController::class, 'printPurchaseOrder'])
            ->name('purchase-order')
            ->middleware('can:print-purchase-orders');
        
        Route::get('customer-statement/{customerId}', [\App\Http\Controllers\Api\V1\PrintController::class, 'printCustomerStatement'])
            ->name('customer-statement')
            ->middleware('can:print-customer-statements');
        
        Route::get('cheque/{id}', [\App\Http\Controllers\Api\V1\PrintController::class, 'printCheque'])
            ->name('cheque')
            ->middleware('can:print-cheques');
        
        Route::post('bulk', [\App\Http\Controllers\Api\V1\PrintController::class, 'bulkPrint'])
            ->name('bulk')
            ->middleware('can:bulk-print');
    });

    // ========================================================================
    // Search & Autocomplete
    // ========================================================================
    Route::prefix('search')->name('api.search.')->group(function () {
        Route::get('products', [ProductController::class, 'search'])->name('products');
        Route::get('customers', [CustomerController::class, 'search'])->name('customers');
    });
});

// ============================================================================
// Fallback for unmatched API routes
// ============================================================================
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found',
        'status' => 404,
    ], 404);
});
