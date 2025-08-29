<?php
// اختبار API مباشرة

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// محاكاة HTTP request
$request = \Illuminate\Http\Request::create('/api/warehouses/4/inventory', 'GET');
$response = $kernel->handle($request);

echo "HTTP Status: " . $response->getStatusCode() . "\n";
echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
echo "Response:\n";
echo $response->getContent() . "\n";
?>
