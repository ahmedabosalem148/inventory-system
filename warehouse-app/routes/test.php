<?php

// Simple test route
Route::get('/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Laravel is working!',
        'timestamp' => now(),
        'database' => 'testing...'
    ]);
});

// Test database connection
Route::get('/test-db', function () {
    try {
        $products = DB::table('products')->count();
        return response()->json([
            'status' => 'success',
            'database' => 'connected',
            'products_count' => $products
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'database' => 'failed',
            'error' => $e->getMessage()
        ], 500);
    }
});
