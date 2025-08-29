<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Create Laravel app instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<!DOCTYPE html><html><head><title>سجل الأخطاء والتشخيص</title>";
echo "<style>body{font-family:Arial;direction:rtl;} .log{background:#f8f9fa;padding:15px;margin:10px 0;border-left:4px solid #007bff;} .error{border-left-color:#dc3545;} .warning{border-left-color:#ffc107;} .info{border-left-color:#17a2b8;}</style>";
echo "</head><body>";
echo "<h2>سجل الأخطاء والتشخيص</h2>";

// Get Laravel log file
$logPath = storage_path('logs/laravel.log');

if (file_exists($logPath)) {
    $logContent = file_get_contents($logPath);
    
    // Get last 50 lines
    $lines = explode("\n", $logContent);
    $recentLines = array_slice($lines, -50);
    
    echo "<h3>آخر 50 سطر من سجل Laravel:</h3>";
    echo "<div style='background:#000;color:#0f0;padding:20px;font-family:monospace;height:400px;overflow-y:scroll;'>";
    foreach ($recentLines as $line) {
        if (trim($line)) {
            $class = 'info';
            if (strpos($line, 'ERROR') !== false) $class = 'error';
            if (strpos($line, 'WARNING') !== false) $class = 'warning';
            
            echo "<div class='{$class}'>" . htmlspecialchars($line) . "</div>";
        }
    }
    echo "</div>";
    
    // Search for specific patterns
    echo "<h3>البحث عن أخطاء محددة:</h3>";
    
    $patterns = [
        'STORE PRODUCT' => 'عمليات إضافة المنتج',
        'Authentication' => 'مشاكل التصديق',
        'CSRF' => 'مشاكل CSRF',
        'ERROR' => 'الأخطاء',
        '419' => 'أخطاء 419'
    ];
    
    foreach ($patterns as $pattern => $description) {
        $matches = [];
        preg_match_all("/.*{$pattern}.*/i", $logContent, $matches);
        
        if (!empty($matches[0])) {
            echo "<h4>{$description} ({$pattern}):</h4>";
            echo "<div class='log'>";
            foreach (array_slice($matches[0], -5) as $match) {
                echo "<div>" . htmlspecialchars($match) . "</div>";
            }
            echo "</div>";
        }
    }
    
} else {
    echo "<p style='color:red;'>ملف السجل غير موجود: {$logPath}</p>";
    echo "<p>تأكد من أن Laravel يعمل وأن المجلد storage/logs قابل للكتابة</p>";
}

echo "<h3>🔧 أدوات التشخيص:</h3>";
echo "<ul>";
echo "<li><a href='/debug_warehouse_auth.php'>تشخيص المخازن والجلسات</a></li>";
echo "<li><a href='/fix_419.php'>إصلاح مشكلة 419</a></li>";
echo "<li><a href='/test_csrf.php'>اختبار CSRF</a></li>";
echo "<li><a href='/warehouses'>الذهاب للمخازن</a></li>";
echo "</ul>";

echo "<p><button onclick='location.reload()'>تحديث السجل</button></p>";

echo "</body></html>";
?>
