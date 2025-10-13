<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Assigning branch to test user...\n\n";

// Get test user
$user = App\Models\User::where('email', 'test@example.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "👤 User: {$user->name} ({$user->email})\n";

// Check current branches
$currentBranches = $user->authorizedBranches()->get();
echo "📋 Current branches: " . ($currentBranches->count() > 0 ? $currentBranches->pluck('name')->implode(', ') : 'NONE') . "\n\n";

// Get or create main branch
$mainBranch = App\Models\Branch::firstOrCreate(
    ['name' => 'الفرع الرئيسي'],
    [
        'code' => 'BR-001',
        'address' => 'القاهرة، مصر',
        'phone' => '01000000000',
        'is_active' => true,
    ]
);

echo "🏢 Main Branch: {$mainBranch->name} (ID: {$mainBranch->id})\n\n";

// Create user branch permission (not just attach)
$existingPermission = App\Models\UserBranchPermission::where('user_id', $user->id)
    ->where('branch_id', $mainBranch->id)
    ->first();

if (!$existingPermission) {
    App\Models\UserBranchPermission::create([
        'user_id' => $user->id,
        'branch_id' => $mainBranch->id,
        'permission_level' => 'full_access',
    ]);
    echo "✅ User assigned to branch with full_access: {$mainBranch->name}\n";
} else {
    echo "⏭️  User already assigned to this branch\n";
}

// Set as assigned and current branch
$user->update([
    'assigned_branch_id' => $mainBranch->id,
    'current_branch_id' => $mainBranch->id,
]);
echo "✅ Set as assigned and current branch\n";

// Verify
$user->refresh();
$branches = $user->authorizedBranches()->get();
echo "\n✅ Done! User now has access to " . $branches->count() . " branch(es):\n";
foreach ($branches as $branch) {
    echo "  - {$branch->name} (ID: {$branch->id})\n";
}

echo "\n🎯 Next step: Logout and login again in the frontend.\n";
