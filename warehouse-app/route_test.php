<?php

echo "🔍 Route Testing\n";
echo str_repeat("=", 40) . "\n";

$baseUrl = 'http://127.0.0.1:8000';
$routes = [
    '/test',
    '/test-db', 
    '/',
    '/admin/dashboard',
    '/api/warehouses/4/inventory'
];

foreach ($routes as $route) {
    $url = $baseUrl . $route;
    echo "Testing: $route ... ";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ FAILED\n";
        if (isset($http_response_header)) {
            echo "   Headers: " . implode(', ', $http_response_header) . "\n";
        }
    } else {
        // Extract status code
        $statusCode = 'unknown';
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $statusCode = $matches[1] ?? 'unknown';
        }
        
        if ($statusCode === '200') {
            echo "✅ SUCCESS ($statusCode)\n";
        } else {
            echo "❌ ERROR ($statusCode)\n";
        }
        
        // Show first 100 chars if error
        if ($statusCode !== '200') {
            echo "   Response: " . substr($response, 0, 100) . "...\n";
        }
    }
}

echo str_repeat("=", 40) . "\n";
