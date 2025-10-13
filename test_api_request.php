<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

// Create a fake HTTP request
$request = Illuminate\Http\Request::create(
    '/api/v1/customers',
    'GET',
    ['page' => 1, 'per_page' => 10]
);

// Get test user and authenticate
$user = App\Models\User::where('email', 'test@example.com')->first();
$request->setUserResolver(function () use ($user) {
    return $user;
});

echo "🌐 Simulating API Request to /api/v1/customers...\n\n";
echo "User: {$user->name}\n";
echo "Request: GET /api/v1/customers?page=1&per_page=10\n\n";

try {
    // Process the request through Laravel
    $response = $kernel->handle($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content:\n";
    
    $content = $response->getContent();
    $json = json_decode($content, true);
    
    if ($json) {
        echo "Total: " . ($json['meta']['total'] ?? 'N/A') . "\n";
        echo "Data Count: " . count($json['data'] ?? []) . "\n";
        
        if (empty($json['data'])) {
            echo "\n❌ WARNING: Data is empty!\n";
            echo "Full response:\n";
            echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            echo "\n✅ SUCCESS: Got " . count($json['data']) . " customers\n";
            echo "First customer: " . ($json['data'][0]['name'] ?? 'N/A') . "\n";
        }
    } else {
        echo $content . "\n";
    }
    
    $kernel->terminate($request, $response);
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
