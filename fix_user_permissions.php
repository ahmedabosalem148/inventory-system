<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Adding permissions to test user...\n\n";

// Get test user
$user = App\Models\User::where('email', 'test@example.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

echo "👤 User: {$user->name} ({$user->email})\n";
echo "Role: {$user->role}\n\n";

// Check current permissions
$permissions = $user->getAllPermissions()->pluck('name');
echo "📋 Current permissions: " . ($permissions->count() > 0 ? $permissions->implode(', ') : 'NONE') . "\n\n";

// List of required permissions
$requiredPermissions = [
    'view-customers',
    'create-customers',
    'edit-customers',
    'delete-customers',
    'view-customer-ledger',
    'print-customer-statement',
    'view-products',
    'view-issue-vouchers',
    'create-issue-vouchers',
    'view-return-vouchers',
    'create-return-vouchers',
    'view-payments',
    'create-payments',
    'view-dashboard',
];

echo "➕ Adding permissions...\n";
foreach ($requiredPermissions as $permissionName) {
    // Check if permission exists
    $permission = Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permissionName]);
    
    // Give permission to user
    if (!$user->hasPermissionTo($permissionName)) {
        $user->givePermissionTo($permissionName);
        echo "  ✅ Added: {$permissionName}\n";
    } else {
        echo "  ⏭️  Already has: {$permissionName}\n";
    }
}

echo "\n✅ Done! User now has " . $user->getAllPermissions()->count() . " permissions.\n";
echo "\n🎯 Next step: Logout and login again in the frontend.\n";
