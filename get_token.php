<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Get first user and create token
$user = User::first();
if ($user) {
    $token = $user->createToken('api-access');
    echo $token->plainTextToken . PHP_EOL;
} else {
    echo "No users found" . PHP_EOL;
}