<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseManager\WarehouseManagerController;
use Illuminate\Support\Facades\Route;

// PIN Login Page
Route::view('/', 'auth.pin-login')->name('login');

// CSRF Token endpoint
Route::get('/csrf-token', function() {
    return response()->json(['token' => csrf_token()]);
});

// Admin Authentication
Route::post('/admin/login', [AuthController::class, 'login']);
Route::post('/admin/logout', [AuthController::class, 'logout']);

// Admin Protected Routes
Route::middleware('admin')->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/summary-flat', [DashboardController::class, 'summaryFlat']);
    
    // Products Management (partial resource)
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
});

// Warehouse Manager Routes
Route::get('/warehouse-manager', [WarehouseManagerController::class, 'showLogin'])->name('warehouse-manager.login');
Route::post('/warehouse-manager/login', [WarehouseManagerController::class, 'login']);
Route::post('/warehouse-manager/logout', [WarehouseManagerController::class, 'logout']);

// Warehouse Manager Protected Routes
Route::middleware('warehouse.manager')->prefix('warehouse-manager')->group(function () {
    Route::get('/dashboard', [WarehouseManagerController::class, 'dashboard']);
    Route::get('/summary-flat', [WarehouseManagerController::class, 'summaryFlat']);
});

// Warehouse Views
Route::get('/warehouses', [InventoryController::class, 'warehousesView']);
Route::get('/warehouses/{warehouse}/login', [InventoryController::class, 'showWarehouseLogin']);
Route::post('/warehouses/{warehouse}/login', [InventoryController::class, 'authenticateWarehouse']);
Route::post('/warehouses/{warehouse}/logout', [InventoryController::class, 'logoutWarehouse']);

// Protected Warehouse Routes
Route::middleware('warehouse.auth')->group(function () {
    Route::get('/warehouses/{warehouse}', [InventoryController::class, 'showWarehouse']);
    Route::get('/warehouses/{warehouse}/products/create', [InventoryController::class, 'createProduct']);
    Route::post('/warehouses/{warehouse}/products', [InventoryController::class, 'storeProduct']);
});

// API Routes with Rate Limiting
Route::middleware('throttle:30,1')->group(function() {
    Route::get('/api/warehouses', [InventoryController::class, 'warehouses']);
    Route::get('/api/warehouses/{warehouse}/inventory', [InventoryController::class, 'index']);
    Route::post('/api/inventory/add', [InventoryController::class, 'add']);
    Route::post('/api/inventory/withdraw', [InventoryController::class, 'withdraw']);
    Route::patch('/api/inventory/set-min', [InventoryController::class, 'setMin']);
    Route::delete('/api/products/{product}', [InventoryController::class, 'deleteProduct']);
});

// Test route for debugging (remove in production)
Route::post('/test/warehouses/{warehouse}/products', [InventoryController::class, 'storeProduct']);

// Test route without CSRF (for debugging only)
Route::post('/test-no-csrf/warehouses/{warehouse}/products', [InventoryController::class, 'storeProduct'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Test route without warehouse auth middleware (for debugging)
Route::post('/test-no-auth/warehouses/{warehouse}/products', [InventoryController::class, 'storeProduct'])
    ->withoutMiddleware(['warehouse.auth']);

// Debug session route (remove in production)
Route::get('/debug/session/{warehouse}', function($warehouseId) {
    return response()->json([
        'warehouse_id' => $warehouseId,
        'session_key' => "warehouse_{$warehouseId}_auth",
        'is_authenticated' => session("warehouse_{$warehouseId}_auth") ? true : false,
        'session_data' => session()->all()
    ]);
});

// CSRF token refresh route
Route::get('/csrf-token', function() {
    return response()->json(['token' => csrf_token()]);
});
