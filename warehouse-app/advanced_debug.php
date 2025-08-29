<?php
require_once __DIR__ . '/vendor/autoload.php';

echo "🔧 Advanced Laravel Debug Tool\n";
echo str_repeat("=", 50) . "\n";

try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    echo "✅ Laravel bootstrap: SUCCESS\n";
    
    // Test .env loading
    $appKey = $_ENV['APP_KEY'] ?? env('APP_KEY');
    echo "✅ APP_KEY: " . ($appKey ? 'SET' : 'MISSING') . "\n";
    
    // Test database
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=warehouse_app', 'root', '');
    echo "✅ Database: CONNECTED\n";
    
    // Test Laravel database connection
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "✅ Laravel DB: CONNECTED\n";
    
    // Test config
    $config = $app['config'];
    echo "✅ Config loaded: " . (count($config->all()) > 0 ? 'YES' : 'NO') . "\n";
    
    // Test routes
    $router = $app['router'];
    $routes = $router->getRoutes();
    echo "✅ Routes loaded: " . $routes->count() . " routes\n";
    
    // Test specific route
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    
    try {
        $response = $kernel->handle($request);
        echo "✅ Route /test: STATUS " . $response->getStatusCode() . "\n";
    } catch (Exception $e) {
        echo "❌ Route /test: ERROR - " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo str_repeat("=", 50) . "\n";
