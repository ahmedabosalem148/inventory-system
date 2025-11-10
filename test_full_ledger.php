<?php

// Login and get token
$ch = curl_init('http://127.0.0.1:8000/api/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'manager@inventory.local',
    'password' => 'password',
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login Status: $httpCode\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $token = $data['token'] ?? null;
    
    if (!$token) {
        echo "❌ No token in response\n";
        exit(1);
    }
    
    echo "✅ Got token\n\n";
    
    // Test ledger endpoint
    $ch = curl_init('http://127.0.0.1:8000/api/v1/customers/6/ledger?per_page=2');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Ledger Endpoint Status: $httpCode\n\n";
    
    if ($response) {
        $data = json_decode($response, true);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "❌ No response\n";
    }
} else {
    echo "❌ Login failed\n";
    echo $response . "\n";
}
