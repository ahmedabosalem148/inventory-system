<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Products Table Structure ===\n";
    
    $columns = Schema::getColumnListing('products');
    
    foreach ($columns as $column) {
        echo "- {$column}\n";
    }
    
    echo "\n=== Sample Data ===\n";
    $products = DB::table('products')->limit(3)->get();
    
    foreach ($products as $product) {
        echo "ID: {$product->id}, Name: {$product->name}\n";
    }
    
    echo "\nTotal products: " . DB::table('products')->count() . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}