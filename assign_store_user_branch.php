<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Branch;

// Get store user (ID 2: store1@inventory.local - أمين مخزن المصنع)
$user = User::find(2);

if (!$user) {
    echo "❌ Store user not found!\n";
    exit(1);
}

echo "👤 Found user: {$user->name} ({$user->email})\n";
echo "📍 Current active_branch_id: " . ($user->active_branch_id ?? 'NULL') . "\n\n";

// Get first branch
$branch = Branch::first();

if (!$branch) {
    echo "❌ No branches found!\n";
    exit(1);
}

// Assign branch
$user->active_branch_id = $branch->id;
$user->save();

echo "✅ Store user '{$user->name}' assigned to branch '{$branch->name}' (ID: {$branch->id})\n";
echo "📍 User email: {$user->email}\n";
echo "🏢 Active Branch: {$user->active_branch_id}\n";
