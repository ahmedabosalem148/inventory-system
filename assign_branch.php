<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get first user
$user = App\Models\User::first();
echo "User: {$user->name} (ID: {$user->id})\n";
echo "Current Branch ID: " . ($user->current_branch_id ?? 'None') . "\n";
echo "Assigned Branch ID: " . ($user->assigned_branch_id ?? 'None') . "\n";

// Get first branch
$branch = App\Models\Branch::first();
echo "\nAvailable Branch: {$branch->name} (ID: {$branch->id})\n";

// Assign branch to user
$user->assigned_branch_id = $branch->id;
$user->current_branch_id = $branch->id;
$user->save();

echo "\n✅ Successfully assigned branch '{$branch->name}' to user '{$user->name}'\n";

// Also add to branch_user pivot table if needed
if (!$user->authorizedBranches()->where('branch_id', $branch->id)->exists()) {
    $user->authorizedBranches()->attach($branch->id, [
        'permission_level' => 'full_access',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "✅ Added branch to authorized branches with full access\n";
}

echo "\nUser branches:\n";
foreach ($user->authorizedBranches as $b) {
    echo "  - {$b->name} (ID: {$b->id})\n";
}
