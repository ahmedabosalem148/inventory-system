<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "🎯 Final System Test - منتج تجريبي6\n";
echo str_repeat("=", 50) . "\n";

try {
    // Test direct API call
    $response = file_get_contents('http://127.0.0.1:8000/api/warehouses/4/inventory');
    $data = json_decode($response, true);
    
    if ($data && isset($data['data'])) {
        echo "✅ API Response: SUCCESS\n";
        
        // Find منتج تجريبي6
        $product6 = null;
        foreach ($data['data'] as $item) {
            if ($item['product']['name'] === 'منتج تجريبي6') {
                $product6 = $item;
                break;
            }
        }
        
        if ($product6) {
            echo "✅ منتج تجريبي6 found in API\n";
            echo "   - Closed Cartons: {$product6['closed_cartons']}\n";
            echo "   - Min Threshold: {$product6['min_threshold']}\n";
            echo "   - Below Min: " . ($product6['belowMin'] ? 'TRUE ✅' : 'FALSE ❌') . "\n";
            
            $expected = $product6['closed_cartons'] < $product6['min_threshold'];
            $actual = $product6['belowMin'];
            
            if ($expected === $actual) {
                echo "✅ Logic is CORRECT!\n";
            } else {
                echo "❌ Logic is WRONG! Expected: " . ($expected ? 'TRUE' : 'FALSE') . ", Got: " . ($actual ? 'TRUE' : 'FALSE') . "\n";
            }
        } else {
            echo "❌ منتج تجريبي6 NOT found in API\n";
        }
    } else {
        echo "❌ API Response: FAILED\n";
        echo "Response: " . substr($response, 0, 200) . "...\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test direct database
echo "\n📊 Direct Database Check:\n";
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=warehouse_app', 'root', '');
    $stmt = $pdo->query("
        SELECT wi.closed_cartons, wi.min_threshold, p.name_ar 
        FROM warehouse_inventory wi 
        JOIN products p ON wi.product_id = p.id 
        WHERE p.name_ar = 'منتج تجريبي6' 
        LIMIT 1
    ");
    
    if ($row = $stmt->fetch()) {
        $belowMin = $row['closed_cartons'] < $row['min_threshold'];
        echo "✅ Database: {$row['closed_cartons']} cartons < {$row['min_threshold']} = " . ($belowMin ? 'BELOW MIN ✅' : 'OK ❌') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 Test Complete! Check the results above.\n";
