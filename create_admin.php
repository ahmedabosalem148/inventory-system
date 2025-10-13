<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Creating Admin User ===\n";

// Check if admin user exists
$existingUser = App\Models\User::where('email', 'admin@inventory.test')->first();

if ($existingUser) {
    echo "Admin user already exists!\n";
    echo "Email: {$existingUser->email}\n";
    echo "Name: {$existingUser->name}\n";
} else {
    // Create admin user
    $adminUser = App\Models\User::create([
        'name' => 'Admin User',
        'email' => 'admin@inventory.test',
        'email_verified_at' => now(),
        'password' => Hash::make('password'),
    ]);
    
    echo "✅ Admin user created successfully!\n";
    echo "Email: {$adminUser->email}\n";
    echo "Name: {$adminUser->name}\n";
    echo "ID: {$adminUser->id}\n";
}

echo "\n=== All Users ===\n";
$users = App\Models\User::select('id', 'name', 'email')->get();
foreach ($users as $user) {
    echo "ID: {$user->id} | Name: {$user->name} | Email: {$user->email}\n";
}

echo "\n=== Ready to login with ===\n";
echo "Email: admin@inventory.test\n";
echo "Password: password\n";