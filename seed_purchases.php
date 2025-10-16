<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\Branch;
use App\Models\User;

echo "=== Creating Sample Suppliers ===\n\n";

$suppliers = [
    [
        'name' => 'شركة الإمارات للتجارة',
        'contact_name' => 'أحمد محمد',
        'phone' => '+971501234567',
        'email' => 'info@emirates-trade.com',
        'address' => 'دبي - الإمارات العربية المتحدة',
        'tax_number' => '100123456700003',
        'payment_terms' => 'NET_30',
        'credit_limit' => 100000,
        'status' => 'ACTIVE',
    ],
    [
        'name' => 'مؤسسة النخيل التجارية',
        'contact_name' => 'فاطمة علي',
        'phone' => '+966551234567',
        'email' => 'contact@alnakheel.sa',
        'address' => 'الرياض - المملكة العربية السعودية',
        'tax_number' => '300123456700001',
        'payment_terms' => 'NET_60',
        'credit_limit' => 150000,
        'status' => 'ACTIVE',
    ],
    [
        'name' => 'شركة الخليج للمواد الغذائية',
        'contact_name' => 'محمد سالم',
        'phone' => '+97142345678',
        'email' => 'sales@gulf-foods.com',
        'address' => 'أبوظبي - الإمارات العربية المتحدة',
        'tax_number' => '100987654300003',
        'payment_terms' => 'NET_15',
        'credit_limit' => 75000,
        'status' => 'ACTIVE',
    ],
];

foreach ($suppliers as $supplierData) {
    $supplier = Supplier::create($supplierData);
    echo "✅ Created supplier: {$supplier->name}\n";
}

echo "\n=== Creating Sample Purchase Order ===\n\n";

$supplier = Supplier::first();
$branch = Branch::first();
$user = User::first();
$products = Product::limit(3)->get();

if ($supplier && $branch && $user && $products->count() >= 3) {
    // Calculate items
    $items = [];
    $subtotal = 0;
    
    foreach ($products as $product) {
        $quantity = rand(10, 50);
        $unitPrice = $product->purchase_price ?? 100;
        $itemTotal = $quantity * $unitPrice;
        $subtotal += $itemTotal;
        
        $items[] = [
            'product_id' => $product->id,
            'quantity_ordered' => $quantity,
            'quantity_received' => 0,
            'unit_price' => $unitPrice,
            'discount_type' => 'NONE',
            'discount_value' => 0,
            'discount_amount' => 0,
            'subtotal' => $itemTotal,
            'total' => $itemTotal,
        ];
    }
    
    // Create purchase order
    $order = PurchaseOrder::create([
        'order_number' => 'PO-' . date('Ymd') . '-001',
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'order_date' => now(),
        'expected_delivery_date' => now()->addDays(7),
        'subtotal' => $subtotal,
        'discount_type' => 'NONE',
        'discount_value' => 0,
        'discount_amount' => 0,
        'tax_percentage' => 15,
        'tax_amount' => $subtotal * 0.15,
        'shipping_cost' => 500,
        'total_amount' => $subtotal + ($subtotal * 0.15) + 500,
        'status' => 'PENDING',
        'receiving_status' => 'NOT_RECEIVED',
        'payment_status' => 'UNPAID',
        'notes' => 'أمر شراء تجريبي للاختبار',
        'created_by' => $user->id,
    ]);
    
    // Add items
    foreach ($items as $itemData) {
        $order->items()->create($itemData);
    }
    
    echo "✅ Created purchase order: {$order->order_number}\n";
    echo "   Supplier: {$supplier->name}\n";
    echo "   Branch: {$branch->name}\n";
    echo "   Total Amount: {$order->total_amount} ر.س\n";
    echo "   Items: " . count($items) . "\n";
} else {
    echo "❌ Cannot create purchase order - missing required data\n";
    echo "   Supplier: " . ($supplier ? '✅' : '❌') . "\n";
    echo "   Branch: " . ($branch ? '✅' : '❌') . "\n";
    echo "   User: " . ($user ? '✅' : '❌') . "\n";
    echo "   Products: " . $products->count() . "/3\n";
}

echo "\n=== Summary ===\n";
echo "Suppliers: " . Supplier::count() . "\n";
echo "Purchase Orders: " . PurchaseOrder::count() . "\n";
echo "\n✅ Seeding completed!\n";
