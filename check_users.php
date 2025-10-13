<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Users in Database ===\n";
$users = App\Models\User::select('id', 'name', 'email')->get();

if ($users->count() > 0) {
    foreach ($users as $user) {
        echo "ID: {$user->id} | Name: {$user->name} | Email: {$user->email}\n";
    }
} else {
    echo "No users found!\n";
}

echo "\n=== Total Users: " . $users->count() . " ===\n";