<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "🧪 Quick System Health Check\n";
echo str_repeat("=", 40) . "\n";

try {
    // Test database connection
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=warehouse_app', 'root', '');
    echo "✅ Database connection: OK\n";
    
    // Check if key tables exist
    $tables = ['products', 'warehouses', 'warehouse_inventory', 'movements'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table': EXISTS\n";
        } else {
            echo "❌ Table '$table': MISSING\n";
        }
    }
    
    // Check data counts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $productCount = $stmt->fetch()['count'];
    echo "📊 Products: $productCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM warehouse_inventory");
    $inventoryCount = $stmt->fetch()['count'];
    echo "📊 Inventory records: $inventoryCount\n";
    
    // Test منتج تجريبي6
    $stmt = $pdo->query("
        SELECT wi.closed_cartons, wi.min_threshold, p.name_ar 
        FROM warehouse_inventory wi 
        JOIN products p ON wi.product_id = p.id 
        WHERE p.name_ar = 'منتج تجريبي6' 
        LIMIT 1
    ");
    
    if ($row = $stmt->fetch()) {
        $belowMin = $row['closed_cartons'] < $row['min_threshold'];
        echo "🎯 منتج تجريبي6: {$row['closed_cartons']} cartons < {$row['min_threshold']} = " . ($belowMin ? 'BELOW MIN ✅' : 'OK') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo str_repeat("=", 40) . "\n";
echo "🚀 Test completed!\n";
