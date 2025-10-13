<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\IssueVoucherController;
use App\Http\Controllers\ReturnVoucherController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ChequeController;

// الصفحة الرئيسية - تحويل إلى لوحة التحكم
Route::get('/', function () {
    return redirect('/dashboard');
});

// لوحة التحكم
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// إدارة الفروع
Route::resource('branches', BranchController::class);

// إدارة التصنيفات
Route::resource('categories', CategoryController::class);

// إدارة المنتجات
Route::get('/reports/low-stock', [ProductController::class, 'lowStockReport'])->name('reports.low-stock');
Route::resource('products', ProductController::class);

// إدارة العملاء
Route::resource('customers', CustomerController::class);

// أذون الصرف
Route::resource('issue-vouchers', IssueVoucherController::class)->except(['edit', 'update']);

// أذون الإرجاع
Route::resource('return-vouchers', ReturnVoucherController::class)->except(['edit', 'update']);

// المدفوعات
Route::resource('payments', PaymentController::class)->except(['edit', 'update']);

// الشيكات
Route::get('/cheques/pending', [ChequeController::class, 'pending'])->name('cheques.pending');
Route::post('/cheques/{cheque}/clear', [ChequeController::class, 'clear'])->name('cheques.clear');
Route::post('/cheques/{cheque}/return', [ChequeController::class, 'return'])->name('cheques.return');
Route::resource('cheques', ChequeController::class)->only(['index', 'show']);

// PDF Print Routes
Route::get('/issue-vouchers/{id}/print', [IssueVoucherController::class, 'print'])->name('issue-vouchers.print');
Route::get('/return-vouchers/{id}/print', [ReturnVoucherController::class, 'print'])->name('return-vouchers.print');

// Import/Export Routes
Route::get('/imports', [App\Http\Controllers\ImportController::class, 'index'])->name('imports.index');
Route::get('/imports/template', [App\Http\Controllers\ImportController::class, 'downloadTemplate'])->name('imports.template');
Route::post('/imports/preview', [App\Http\Controllers\ImportController::class, 'preview'])->name('imports.preview');
Route::post('/imports/execute', [App\Http\Controllers\ImportController::class, 'execute'])->name('imports.execute');
Route::get('/imports/customers/template', [App\Http\Controllers\ImportController::class, 'downloadCustomerTemplate'])->name('imports.customers.template');
Route::post('/imports/customers/execute', [App\Http\Controllers\ImportController::class, 'executeCustomerImport'])->name('imports.customers.execute');
Route::get('/imports/cheques/template', [App\Http\Controllers\ImportController::class, 'downloadChequeTemplate'])->name('imports.cheques.template');
Route::post('/imports/cheques/execute', [App\Http\Controllers\ImportController::class, 'executeChequeImport'])->name('imports.cheques.execute');

// Reports Routes
Route::get('/reports/inventory', [App\Http\Controllers\ReportController::class, 'inventorySummary'])->name('reports.inventory');
Route::get('/reports/inventory/csv', [App\Http\Controllers\ReportController::class, 'inventorySummaryCSV'])->name('reports.inventory.csv');
Route::get('/reports/inventory/pdf', [App\Http\Controllers\ReportController::class, 'inventorySummaryPDF'])->name('reports.inventory.pdf');
Route::get('/reports/product-movement', [App\Http\Controllers\ReportController::class, 'productMovement'])->name('reports.product.movement');
Route::get('/reports/customer-balances', [App\Http\Controllers\ReportController::class, 'customerBalances'])->name('reports.customer.balances');
Route::get('/reports/inactive-customers', [App\Http\Controllers\ReportController::class, 'inactiveCustomers'])->name('reports.inactive.customers');

// Customer Statement PDF (TASK-024)
Route::get('/customers/{id}/statement', [App\Http\Controllers\ReportController::class, 'customerStatement'])
    ->name('customers.statement');