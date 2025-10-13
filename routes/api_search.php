<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QuickSearchController;

/*
|--------------------------------------------------------------------------
| Quick Search API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('api/search')->name('api.search.')->group(function () {
    // البحث في المنتجات
    Route::get('/products', [QuickSearchController::class, 'products'])->name('products');
    
    // البحث في العملاء
    Route::get('/customers', [QuickSearchController::class, 'customers'])->name('customers');
    
    // البحث في المخزون حسب الفرع
    Route::get('/stock', [QuickSearchController::class, 'stockByBranch'])->name('stock');
    
    // البحث العام (في كل شيء)
    Route::get('/global', [QuickSearchController::class, 'global'])->name('global');
});
