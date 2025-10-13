<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
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
    Route::apiResource('customers', CustomerController::class)->names('api.customers');

    // ========================================================================
    // Vouchers & Transactions
    // ========================================================================
    Route::apiResource('issue-vouchers', IssueVoucherController::class)
        ->names('api.issue-vouchers');
    
    Route::apiResource('return-vouchers', ReturnVoucherController::class)
        ->names('api.return-vouchers');
    
    // Voucher actions
    Route::post('issue-vouchers/{voucher}/print', [IssueVoucherController::class, 'print'])
        ->name('api.issue-vouchers.print');
    Route::post('return-vouchers/{voucher}/print', [ReturnVoucherController::class, 'print'])
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
