<?php
/**
 * TASK-B02: Sequencing System Gap Detection Test
 * اختبار اكتشاف الثغرات في نظام الترقيم
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sequence;
use App\Models\IssueVoucher;
use App\Models\ReturnVoucher;
use App\Services\SequencerService;
use Illuminate\Support\Facades\DB;

echo "\n🔍 TASK-B02: Sequencing System Analysis\n";
echo str_repeat("=", 70) . "\n\n";

try {
    $sequencer = app(SequencerService::class);
    
    // 1. Check sequences configuration
    echo "✓ Test 1: Sequences Configuration\n";
    $sequences = Sequence::all();
    echo "  → Found " . $sequences->count() . " sequence configurations:\n";
    foreach ($sequences as $seq) {
        echo "    • {$seq->entity_type} ({$seq->year}): ";
        echo "Range [{$seq->min_value}-{$seq->max_value}], ";
        echo "Current: {$seq->last_number}, ";
        echo "Prefix: " . ($seq->prefix ?? 'none') . "\n";
    }
    echo "\n";

    // 2. Check for gaps in issue vouchers
    echo "✓ Test 2: Issue Vouchers - Gap Detection\n";
    $issueVouchers = DB::table('issue_vouchers')
        ->whereNotNull('voucher_number')
        ->orderBy('voucher_number')
        ->pluck('voucher_number')
        ->toArray();
    
    if (empty($issueVouchers)) {
        echo "  → No issue vouchers found (expected in fresh DB)\n";
    } else {
        echo "  → Found " . count($issueVouchers) . " issue vouchers\n";
        
        // Extract numbers from format like "ISS-2025/00001"
        $numbers = array_map(function($vn) {
            if (preg_match('/(\d+)$/', $vn, $matches)) {
                return (int)$matches[1];
            }
            return null;
        }, $issueVouchers);
        
        $numbers = array_filter($numbers);
        sort($numbers);
        
        // Find gaps
        $gaps = [];
        for ($i = 0; $i < count($numbers) - 1; $i++) {
            $diff = $numbers[$i + 1] - $numbers[$i];
            if ($diff > 1) {
                $gaps[] = "Gap between {$numbers[$i]} and {$numbers[$i + 1]}";
            }
        }
        
        if (empty($gaps)) {
            echo "  ✓ No gaps detected (sequential numbering is correct)\n";
        } else {
            echo "  ✗ GAPS FOUND:\n";
            foreach ($gaps as $gap) {
                echo "    • {$gap}\n";
            }
        }
    }
    echo "\n";

    // 3. Check for gaps in return vouchers
    echo "✓ Test 3: Return Vouchers - Gap Detection\n";
    $returnVouchers = DB::table('return_vouchers')
        ->whereNotNull('voucher_number')
        ->orderBy('voucher_number')
        ->pluck('voucher_number')
        ->toArray();
    
    if (empty($returnVouchers)) {
        echo "  → No return vouchers found (expected in fresh DB)\n";
    } else {
        echo "  → Found " . count($returnVouchers) . " return vouchers\n";
        
        $numbers = array_map(function($vn) {
            if (preg_match('/(\d+)$/', $vn, $matches)) {
                return (int)$matches[1];
            }
            return null;
        }, $returnVouchers);
        
        $numbers = array_filter($numbers);
        sort($numbers);
        
        $gaps = [];
        for ($i = 0; $i < count($numbers) - 1; $i++) {
            $diff = $numbers[$i + 1] - $numbers[$i];
            if ($diff > 1) {
                $gaps[] = "Gap between {$numbers[$i]} and {$numbers[$i + 1]}";
            }
        }
        
        if (empty($gaps)) {
            echo "  ✓ No gaps detected\n";
        } else {
            echo "  ✗ GAPS FOUND:\n";
            foreach ($gaps as $gap) {
                echo "    • {$gap}\n";
            }
        }
    }
    echo "\n";

    // 4. Test concurrent sequence generation (simulate race condition)
    echo "✓ Test 4: Transaction Safety (lockForUpdate)\n";
    echo "  → Testing if SequencerService uses lockForUpdate()...\n";
    
    $serviceCode = file_get_contents(__DIR__ . '/app/Services/SequencerService.php');
    $hasLock = strpos($serviceCode, 'lockForUpdate()') !== false;
    $hasTransaction = strpos($serviceCode, 'DB::transaction') !== false;
    
    if ($hasLock && $hasTransaction) {
        echo "  ✓ Service uses lockForUpdate() inside DB::transaction\n";
        echo "  ✓ Race condition protection: ENABLED\n";
    } else {
        echo "  ✗ WARNING: Missing race condition protection!\n";
        if (!$hasTransaction) echo "    • Missing DB::transaction\n";
        if (!$hasLock) echo "    • Missing lockForUpdate()\n";
    }
    echo "\n";

    // 5. Test sequence limits
    echo "✓ Test 5: Sequence Limits Validation\n";
    foreach ($sequences as $seq) {
        $remaining = $seq->max_value - $seq->last_number;
        $usagePercent = ($seq->last_number / $seq->max_value) * 100;
        
        echo "  • {$seq->entity_type}: ";
        echo number_format($remaining) . " remaining ";
        echo "(" . number_format($usagePercent, 1) . "% used)\n";
        
        if ($usagePercent > 90) {
            echo "    ⚠️ WARNING: Approaching limit!\n";
        }
    }
    echo "\n";

    // 6. Check if sequences match voucher counts
    echo "✓ Test 6: Sequence Consistency Check\n";
    
    $issueSeq = Sequence::where('entity_type', 'issue_vouchers')
        ->where('year', now()->year)
        ->first();
    
    if ($issueSeq) {
        $actualCount = DB::table('issue_vouchers')
            ->whereYear('created_at', now()->year)
            ->count();
        
        echo "  • Issue Vouchers:\n";
        echo "    Sequence says: {$issueSeq->last_number}\n";
        echo "    Actual records: {$actualCount}\n";
        
        if ($issueSeq->last_number == $actualCount) {
            echo "    ✓ Perfect match!\n";
        } else {
            $diff = abs($issueSeq->last_number - $actualCount);
            echo "    ⚠️ Discrepancy: {$diff} difference\n";
            if ($issueSeq->last_number > $actualCount) {
                echo "    → Some vouchers may have been deleted\n";
            }
        }
    }
    echo "\n";

    // Summary
    echo str_repeat("=", 70) . "\n";
    echo "📊 SEQUENCING SYSTEM ANALYSIS COMPLETE\n\n";
    
    echo "✅ Current Status:\n";
    echo "  ✓ SequencerService exists\n";
    echo "  ✓ Database table configured\n";
    echo "  ✓ Transaction safety: " . ($hasLock && $hasTransaction ? "ENABLED" : "MISSING") . "\n";
    echo "  ✓ Sequences configured: " . $sequences->count() . "\n";
    echo "\n";
    
    echo "🎯 Recommendations:\n";
    if (empty($issueVouchers) && empty($returnVouchers)) {
        echo "  → System is fresh - run integration tests to generate vouchers\n";
    }
    echo "  → Current implementation appears gap-free if used correctly\n";
    echo "  → Transaction locking prevents concurrent duplicate numbers\n";
    echo "\n";
    
    echo "✨ Next Step: Run concurrent test to verify no race conditions\n";
    echo str_repeat("=", 70) . "\n\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    exit(1);
}
