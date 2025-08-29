<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance for Bcrypt
$app = require_once __DIR__ . '/../bootstrap/app.php';
$hasher = $app->make('hash');

$pin = '5678';
$hash = $hasher->make($pin);

echo "<!DOCTYPE html><html><head><title>PIN Hash</title></head><body>";
echo "<h2>PIN Hash Generator</h2>";
echo "<p><strong>PIN:</strong> $pin</p>";
echo "<p><strong>Hash:</strong> $hash</p>";

// Test verification
$verify = $hasher->check($pin, $hash);
echo "<p><strong>Verification Test:</strong> " . ($verify ? 'SUCCESS ✓' : 'FAILED ✗') . "</p>";

echo "<h3>Instructions:</h3>";
echo "<p>Copy the hash above and update your .env file:</p>";
echo "<code>WAREHOUSE_MANAGER_PIN_HASH=\"$hash\"</code>";
echo "</body></html>";
?>
