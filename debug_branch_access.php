<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Debugging Branch Access...\n\n";

// Get test user
$user = App\Models\User::where('email', 'test@example.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "👤 User: {$user->name} ({$user->email})\n";
echo "Assigned Branch ID: " . ($user->assigned_branch_id ?? 'NULL') . "\n";
echo "Current Branch ID: " . ($user->current_branch_id ?? 'NULL') . "\n\n";

// Test getActiveBranch()
echo "🔧 Testing getActiveBranch():\n";
try {
    $activeBranch = $user->getActiveBranch();
    if ($activeBranch) {
        echo "✅ Active Branch: {$activeBranch->name} (ID: {$activeBranch->id})\n";
    } else {
        echo "❌ getActiveBranch() returned NULL!\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test relationships
echo "\n📋 Testing Relationships:\n";

// 1. assignedBranch
try {
    $assigned = $user->assignedBranch;
    echo "Assigned Branch: " . ($assigned ? $assigned->name : 'NULL') . "\n";
} catch (\Exception $e) {
    echo "Assigned Branch Error: " . $e->getMessage() . "\n";
}

// 2. currentBranch
try {
    $current = $user->currentBranch;
    echo "Current Branch: " . ($current ? $current->name : 'NULL') . "\n";
} catch (\Exception $e) {
    echo "Current Branch Error: " . $e->getMessage() . "\n";
}

// 3. authorizedBranches
try {
    $authorized = $user->authorizedBranches;
    echo "Authorized Branches: " . $authorized->count() . "\n";
    foreach ($authorized as $branch) {
        echo "  - {$branch->name} (permission: {$branch->pivot->permission_level})\n";
    }
} catch (\Exception $e) {
    echo "Authorized Branches Error: " . $e->getMessage() . "\n";
}

// 4. Test canAccessBranch
echo "\n🔐 Testing canAccessBranch(1):\n";
try {
    $canAccess = $user->canAccessBranch(1);
    echo "Can Access Branch 1: " . ($canAccess ? 'YES ✅' : 'NO ❌') . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Recommendation:\n";
if (!$user->current_branch_id) {
    echo "❌ User has no current_branch_id set!\n";
    echo "Run: php assign_user_branch.php\n";
} elseif (!$activeBranch) {
    echo "❌ getActiveBranch() not working properly!\n";
    echo "Check User model's getActiveBranch() method.\n";
}
