<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

// Check if columns exist
$columns = DB::select("PRAGMA table_info(users)");
$columnNames = array_column($columns, 'name');

echo "📋 Users table columns:\n";
foreach ($columns as $col) {
    echo "  - {$col->name} ({$col->type})\n";
}
echo "\n";

// Check if branch columns exist
$hasAssignedBranch = in_array('assigned_branch_id', $columnNames);
$hasCurrentBranch = in_array('current_branch_id', $columnNames);

echo "✅ Has assigned_branch_id: " . ($hasAssignedBranch ? 'YES' : 'NO') . "\n";
echo "✅ Has current_branch_id: " . ($hasCurrentBranch ? 'YES' : 'NO') . "\n\n";

// Get store user
$user = User::find(2);
echo "👤 User: {$user->name} ({$user->email})\n";
echo "📍 assigned_branch_id: " . ($user->assigned_branch_id ?? 'NULL') . "\n";
echo "📍 current_branch_id: " . ($user->current_branch_id ?? 'NULL') . "\n\n";

// Get first branch
$branch = Branch::first();
if ($branch) {
    echo "🏢 Available branch: {$branch->name} (ID: {$branch->id})\n\n";
    
    // Assign if not assigned
    if (!$user->assigned_branch_id) {
        $user->assigned_branch_id = $branch->id;
        $user->current_branch_id = $branch->id;
        $user->save();
        echo "✅ Store user assigned to branch '{$branch->name}'\n";
    } else {
        echo "ℹ️ User already has assigned branch\n";
    }
} else {
    echo "❌ No branches found!\n";
}
