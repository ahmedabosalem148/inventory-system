<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// PIN Login Page
Route::view('/', 'auth.pin-login')->name('login');

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

// Warehouse Views
Route::get('/warehouses', [InventoryController::class, 'warehousesView']);
Route::get('/warehouses/{warehouse}/login', [InventoryController::class, 'showWarehouseLogin']);
Route::post('/warehouses/{warehouse}/login', [InventoryController::class, 'authenticateWarehouse']);
Route::post('/warehouses/{warehouse}/logout', [InventoryController::class, 'logoutWarehouse']);

// Protected Warehouse Routes
Route::middleware('warehouse.auth')->group(function () {
    Route::get('/warehouses/{warehouse}', [InventoryController::class, 'showWarehouse']);
});

// API Routes with Rate Limiting
Route::middleware('throttle:30,1')->group(function() {
    Route::get('/api/warehouses', [InventoryController::class, 'warehouses']);
    Route::get('/api/warehouses/{warehouse}/inventory', [InventoryController::class, 'index']);
    Route::post('/api/inventory/add', [InventoryController::class, 'add']);
    Route::post('/api/inventory/withdraw', [InventoryController::class, 'withdraw']);
    Route::patch('/api/inventory/set-min', [InventoryController::class, 'setMin']);
});
