<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get first user and create token
$user = App\Models\User::first();
if (!$user) {
    echo "No users found!\n";
    exit(1);
}

$token = $user->createToken('test-api')->plainTextToken;

echo "=== Testing Notifications API ===\n\n";
echo "User: {$user->name}\n";
echo "Token: {$token}\n\n";

// Test 1: Get unread count
echo "TEST 1: Get unread count\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/v1/notifications/unread-count');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP {$httpCode}: " . $response . "\n\n";

// Test 2: Get recent notifications
echo "TEST 2: Get recent notifications\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/v1/notifications/recent');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = json_decode($response, true);
if (isset($data['data'])) {
    echo "HTTP {$httpCode}: Found " . count($data['data']) . " notifications:\n";
    foreach ($data['data'] as $notif) {
        $readStatus = $notif['is_read'] ? '✓' : '○';
        echo "  {$readStatus} [{$notif['type']}] {$notif['title']}\n";
    }
} else {
    echo "HTTP {$httpCode}: " . $response . "\n";
}
echo "\n";

// Test 3: Get all notifications with pagination
echo "TEST 3: Get all notifications (paginated)\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/api/v1/notifications?per_page=5');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = json_decode($response, true);
if (isset($data['data'])) {
    echo "HTTP {$httpCode}: Total: {$data['meta']['total']}, Page: {$data['meta']['current_page']}/{$data['meta']['last_page']}\n";
    echo "Showing {$data['meta']['from']}-{$data['meta']['to']} of {$data['meta']['total']}\n";
} else {
    echo "HTTP {$httpCode}: " . $response . "\n";
}

echo "\n✓ API tests completed!\n";
