<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get the token from command line argument or use default
$tokenString = $argv[1] ?? 'ST7eaHCrVxTBtkVZqk6e';

echo "🔍 Checking token: " . substr($tokenString, 0, 20) . "...\n\n";

// Find token in database
$token = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $tokenString))
    ->first();

if (!$token) {
    echo "❌ Token not found in database!\n";
    echo "This means the token is invalid or expired.\n";
    exit(1);
}

$user = $token->tokenable;

echo "✅ Token found!\n";
echo "Token ID: " . $token->id . "\n";
echo "Token Name: " . $token->name . "\n";
echo "Created: " . $token->created_at . "\n";
echo "Last Used: " . ($token->last_used_at ?? 'Never') . "\n\n";

echo "👤 User Info:\n";
echo "ID: " . $user->id . "\n";
echo "Name: " . $user->name . "\n";
echo "Email: " . $user->email . "\n";
echo "Role: " . $user->role . "\n\n";

// Check if user has branch access
$branches = $user->authorizedBranches()->get();
echo "🏢 Authorized Branches: " . $branches->count() . "\n";
if ($branches->count() > 0) {
    foreach ($branches as $branch) {
        echo "  - " . $branch->name . " (ID: " . $branch->id . ") - Level: " . $branch->pivot->permission_level . "\n";
    }
} else {
    echo "  ⚠️ User has NO branch access!\n";
}

// Check current branch
$currentBranch = $user->currentBranch;
echo "Current Branch: " . ($currentBranch ? $currentBranch->name . " (ID: " . $currentBranch->id . ")" : "None") . "\n";
echo "Assigned Branch: " . ($user->assignedBranch ? $user->assignedBranch->name . " (ID: " . $user->assignedBranch->id . ")" : "None") . "\n";

// Check if there's a branch filter in CustomerController
echo "\n📊 Testing Customer Query:\n";
$query = App\Models\Customer::query();
echo "Total customers in DB: " . App\Models\Customer::count() . "\n";

// Check if user has branch restriction
if (!$user->hasRole('super-admin') && $branches->count() > 0) {
    $branchIds = $branches->pluck('id');
    echo "User's branch IDs: " . $branchIds->implode(', ') . "\n";
    
    // Check if customers have branch_id
    $customersWithBranch = App\Models\Customer::whereIn('branch_id', $branchIds)->count();
    echo "Customers in user's branches: " . $customersWithBranch . "\n";
    
    // Test direct branch access check
    $canAccessBranch1 = $user->canAccessBranch(1);
    echo "Can access Branch 1: " . ($canAccessBranch1 ? "YES" : "NO") . "\n";
}
