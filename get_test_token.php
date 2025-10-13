<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::where('email', 'test@example.com')->first();

if (!$user) {
    echo "❌ User not found!\n";
    exit(1);
}

// Delete old tokens
$user->tokens()->delete();

// Create new token
$token = $user->createToken('test-token-' . time());

echo "✅ Token generated successfully!\n";
echo "Email: " . $user->email . "\n";
echo "Token: " . $token->plainTextToken . "\n\n";
echo "Copy this token and use it in your frontend:\n";
echo "localStorage.setItem('token', '" . $token->plainTextToken . "');\n";
