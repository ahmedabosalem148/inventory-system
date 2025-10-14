<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InventoryMovementController;
use App\Http\Controllers\Api\V1\IssueVoucherController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
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
    Route::apiResource('products', ProductController::class)->names('api.products');

    // Customer Management (TASK-009) - Must be before apiResource
    Route::get('customers-balances', [CustomerController::class, 'getCustomersWithBalances'])
        ->name('api.customers.balances');
    Route::get('customers-statistics', [CustomerController::class, 'getStatistics'])
        ->name('api.customers.statistics');
    Route::get('customers/{customer}/statement', [CustomerController::class, 'getStatement'])
        ->name('api.customers.statement');
    Route::get('customers/{customer}/balance', [CustomerController::class, 'getBalance'])
        ->name('api.customers.balance');
    Route::get('customers/{customer}/activity', [CustomerController::class, 'getActivity'])
        ->name('api.customers.activity');
    
    Route::apiResource('customers', CustomerController::class)->names('api.customers');

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
    // Payments & Cheques
    // ========================================================================
    Route::apiResource('payments', PaymentController::class)->names('api.payments');
    
    Route::prefix('cheques')->name('api.cheques.')->group(function () {
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
        Route::get('inventory/low-stock', [ReportController::class, 'lowStock'])->name('low-stock');
        Route::get('inventory/movements', [ReportController::class, 'movements'])->name('movements');
        
        // Customer reports
        Route::get('customers/{customer}/statement', [ReportController::class, 'customerStatement'])
            ->name('customer-statement');
        Route::get('customers/balances', [ReportController::class, 'customerBalances'])
            ->name('customer-balances');
        
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
