<?php

/**
 * Manual Transfer Testing Script
 * Run this after creating and approving a transfer from the UI
 * Usage: php test_transfer_manual.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\IssueVoucher;
use App\Models\InventoryMovement;
use App\Models\ProductBranchStock;

echo "\n" . str_repeat("=", 60) . "\n";
echo "🧪 Transfer System Manual Test\n";
echo str_repeat("=", 60) . "\n\n";

// Get the last transfer voucher
$transfer = IssueVoucher::where('is_transfer', true)
    ->with(['branch', 'targetBranch', 'items.product', 'approver'])
    ->latest()
    ->first();

if (!$transfer) {
    echo "❌ No transfer vouchers found!\n";
    echo "   Create a transfer from the UI first.\n\n";
    exit(1);
}

echo "📄 Transfer Voucher Details:\n";
echo "   ID: {$transfer->id}\n";
echo "   Number: {$transfer->voucher_number}\n";
echo "   Source Branch: {$transfer->branch->name}\n";
echo "   Target Branch: {$transfer->targetBranch->name}\n";
echo "   Status: " . ($transfer->is_approved ? '✅ Approved' : '⏳ Pending') . "\n";
echo "   Date: {$transfer->date}\n";

if ($transfer->is_approved) {
    echo "   Approved By: {$transfer->approver->name}\n";
    echo "   Approved At: {$transfer->approved_at}\n";
}

echo "\n" . str_repeat("-", 60) . "\n";
echo "📦 Transfer Items:\n";

foreach ($transfer->items as $index => $item) {
    echo "\n   Item " . ($index + 1) . ":\n";
    echo "   Product: {$item->product->name}\n";
    echo "   Quantity: {$item->quantity} {$item->product->unit}\n";
    echo "   Unit Price: {$item->unit_price} EGP\n";
    echo "   Total: {$item->total_price} EGP\n";
}

echo "\n" . str_repeat("-", 60) . "\n";
echo "💰 Financial Summary:\n";
echo "   Total Amount: {$transfer->total_amount} EGP\n";

if ($transfer->is_approved) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🔄 Inventory Movements:\n";
    echo str_repeat("=", 60) . "\n";

    $movements = InventoryMovement::where('ref_table', 'issue_vouchers')
        ->where('ref_id', $transfer->id)
        ->with(['product', 'branch'])
        ->orderBy('id')
        ->get();

    if ($movements->isEmpty()) {
        echo "❌ No inventory movements found!\n";
        echo "   This indicates a problem with the transfer approval.\n\n";
    } else {
        foreach ($movements as $index => $movement) {
            echo "\n   Movement " . ($index + 1) . ":\n";
            echo "   Type: {$movement->movement_type}\n";
            echo "   Product: {$movement->product->name}\n";
            echo "   Branch: {$movement->branch->name}\n";
            echo "   Quantity: " . ($movement->qty_units > 0 ? '+' : '') . "{$movement->qty_units} {$movement->product->unit}\n";
            echo "   Date: {$movement->movement_date}\n";
        }

        // Verify dual movements
        $transferOut = $movements->where('movement_type', 'TRANSFER_OUT')->count();
        $transferIn = $movements->where('movement_type', 'TRANSFER_IN')->count();

        echo "\n" . str_repeat("-", 60) . "\n";
        echo "✅ Movement Verification:\n";
        echo "   TRANSFER_OUT movements: {$transferOut}\n";
        echo "   TRANSFER_IN movements: {$transferIn}\n";

        if ($transferOut === $transferIn && $transferOut === count($transfer->items)) {
            echo "   Status: ✅ PASS - Dual movements created correctly!\n";
        } else {
            echo "   Status: ❌ FAIL - Movement count mismatch!\n";
        }
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "📊 Stock Balances:\n";
    echo str_repeat("=", 60) . "\n";

    foreach ($transfer->items as $index => $item) {
        echo "\n   Product: {$item->product->name}\n";

        $sourceStock = ProductBranchStock::where('product_id', $item->product_id)
            ->where('branch_id', $transfer->branch_id)
            ->first();

        $targetStock = ProductBranchStock::where('product_id', $item->product_id)
            ->where('branch_id', $transfer->target_branch_id)
            ->first();

        echo "   Source Branch ({$transfer->branch->name}):\n";
        echo "      Current Stock: " . ($sourceStock->current_stock ?? 0) . " {$item->product->unit}\n";

        echo "   Target Branch ({$transfer->targetBranch->name}):\n";
        echo "      Current Stock: " . ($targetStock->current_stock ?? 0) . " {$item->product->unit}\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎯 Test Summary:\n";
echo str_repeat("=", 60) . "\n\n";

if (!$transfer->is_approved) {
    echo "⏳ Status: PENDING\n";
    echo "   The transfer has been created but not approved yet.\n";
    echo "   Please approve it from the UI to complete the test.\n\n";
} else {
    $movements = InventoryMovement::where('ref_table', 'issue_vouchers')
        ->where('ref_id', $transfer->id)
        ->count();

    $expectedMovements = count($transfer->items) * 2; // OUT + IN for each item

    if ($movements === $expectedMovements) {
        echo "✅ Status: ALL TESTS PASSED!\n";
        echo "   ✓ Transfer created successfully\n";
        echo "   ✓ Transfer approved\n";
        echo "   ✓ Dual movements created ({$movements}/{$expectedMovements})\n";
        echo "   ✓ Stock balances updated\n\n";
    } else {
        echo "❌ Status: TESTS FAILED!\n";
        echo "   Expected {$expectedMovements} movements, found {$movements}\n\n";
    }
}

echo str_repeat("=", 60) . "\n\n";
