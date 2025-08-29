<?php

// Test if .env is being loaded correctly
echo "🔍 Environment Debug\n";
echo str_repeat("=", 40) . "\n";

// Check if .env file exists
if (file_exists(__DIR__ . '/.env')) {
    echo "✅ .env file exists\n";
    
    $envContent = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envContent);
    
    foreach ($lines as $line) {
        if (strpos($line, 'APP_KEY=') === 0) {
            echo "✅ APP_KEY found in .env: " . substr($line, 0, 20) . "...\n";
            break;
        }
    }
} else {
    echo "❌ .env file missing\n";
}

// Load Laravel and check environment
require_once __DIR__ . '/vendor/autoload.php';

try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "\n📋 Laravel Environment:\n";
    echo "APP_KEY from config: " . (config('app.key') ? 'SET' : 'MISSING') . "\n";
    echo "APP_KEY from env(): " . (env('APP_KEY') ? 'SET' : 'MISSING') . "\n";
    echo "APP_DEBUG: " . (config('app.debug') ? 'true' : 'false') . "\n";
    echo "APP_ENV: " . config('app.env') . "\n";
    
    // Test encryption
    try {
        $encrypted = encrypt('test');
        echo "✅ Encryption working\n";
    } catch (Exception $e) {
        echo "❌ Encryption failed: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Laravel error: " . $e->getMessage() . "\n";
}

echo str_repeat("=", 40) . "\n";
