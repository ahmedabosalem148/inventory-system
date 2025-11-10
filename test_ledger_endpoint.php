<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get a valid token
$token = DB::table('personal_access_tokens')
    ->where('name', 'auth-token')
    ->latest()
    ->first();

if (!$token) {
    echo "❌ No token found. Please login first.\n";
    exit(1);
}

$tokenString = explode('|', $token->token)[1] ?? '';

echo "🔑 Using token: " . substr($tokenString, 0, 20) . "...\n\n";

// Test the new /customers/{id}/ledger endpoint
$url = 'http://127.0.0.1:8000/api/v1/customers/6/ledger?per_page=5';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $tokenString,
    'Accept: application/json',
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📡 GET /api/v1/customers/6/ledger\n";
echo "Status: $httpCode\n\n";

if ($response) {
    $data = json_decode($response, true);
    
    if (isset($data['data']) && is_array($data['data'])) {
        echo "✅ Got " . count($data['data']) . " ledger entries\n\n";
        
        if (count($data['data']) > 0) {
            $firstEntry = $data['data'][0];
            echo "First entry:\n";
            echo "  ID: " . ($firstEntry['id'] ?? 'N/A') . "\n";
            echo "  Transaction Date: " . ($firstEntry['transaction_date'] ?? 'N/A') . "\n";
            echo "  Transaction Type: " . ($firstEntry['transaction_type'] ?? 'N/A') . "\n";
            echo "  Reference Number: " . ($firstEntry['reference_number'] ?? 'N/A') . "\n";
            echo "  Debit: " . ($firstEntry['debit'] ?? 0) . "\n";
            echo "  Credit: " . ($firstEntry['credit'] ?? 0) . "\n";
            echo "  Balance: " . ($firstEntry['balance'] ?? 0) . "\n";
        }
        
        if (isset($data['meta'])) {
            echo "\nPagination:\n";
            echo "  Total: " . ($data['meta']['total'] ?? 'N/A') . "\n";
            echo "  Per Page: " . ($data['meta']['per_page'] ?? 'N/A') . "\n";
            echo "  Current Page: " . ($data['meta']['current_page'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ Unexpected response format\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "❌ No response from server\n";
}
