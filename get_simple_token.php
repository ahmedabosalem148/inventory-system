<?php

$ch = curl_init('http://127.0.0.1:8000/api/v1/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'username' => 'admin',
    'password' => 'admin123',
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $token = $data['token'] ?? null;
    
    if ($token) {
        echo "$token";
    } else {
        echo "Error: No token in response";
        exit(1);
    }
} else {
    echo "Error: Login failed with code $httpCode";
    exit(1);
}
