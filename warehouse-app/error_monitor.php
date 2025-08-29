<?php

// Real-time error monitoring
echo "🔍 Laravel Error Monitor - Real Time\n";
echo str_repeat("=", 50) . "\n";
echo "Monitoring for 500 errors...\n\n";

// Check if log file exists
$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "❌ Log file not found: $logFile\n";
    exit;
}

// Get current log size
$currentSize = filesize($logFile);
echo "📊 Current log size: " . number_format($currentSize) . " bytes\n";

// Function to get recent errors
function getRecentErrors($logFile, $lines = 50) {
    $content = file($logFile);
    $totalLines = count($content);
    $start = max(0, $totalLines - $lines);
    
    $recentLines = array_slice($content, $start);
    
    $errors = [];
    $currentError = null;
    
    foreach ($recentLines as $line) {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?(ERROR|CRITICAL|ALERT|EMERGENCY):(.*)/', $line, $matches)) {
            if ($currentError) {
                $errors[] = $currentError;
            }
            $currentError = [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => trim($matches[3]),
                'details' => []
            ];
        } elseif ($currentError && !empty(trim($line))) {
            $currentError['details'][] = trim($line);
        }
    }
    
    if ($currentError) {
        $errors[] = $currentError;
    }
    
    return $errors;
}

// Get recent errors
$errors = getRecentErrors($logFile);

if (empty($errors)) {
    echo "✅ No recent errors found in logs\n";
} else {
    echo "🚨 Found " . count($errors) . " recent errors:\n\n";
    
    foreach (array_reverse(array_slice($errors, -5)) as $i => $error) {
        echo "Error #" . ($i + 1) . ":\n";
        echo "  📅 Time: {$error['timestamp']}\n";
        echo "  🔥 Level: {$error['level']}\n";
        echo "  💬 Message: {$error['message']}\n";
        
        if (!empty($error['details'])) {
            echo "  📋 Details:\n";
            foreach (array_slice($error['details'], 0, 3) as $detail) {
                echo "    " . substr($detail, 0, 100) . "\n";
            }
        }
        echo "\n";
    }
}

// Monitor for new entries
echo "🔄 Monitoring for new errors (Press Ctrl+C to stop)...\n";
echo "Try accessing the problematic URL now.\n\n";

$lastSize = $currentSize;
$checkCount = 0;

while ($checkCount < 30) { // Monitor for 30 iterations
    sleep(2);
    $checkCount++;
    
    $newSize = filesize($logFile);
    
    if ($newSize > $lastSize) {
        echo "🚨 NEW LOG ENTRY DETECTED!\n";
        
        // Read new content
        $handle = fopen($logFile, 'r');
        fseek($handle, $lastSize);
        $newContent = fread($handle, $newSize - $lastSize);
        fclose($handle);
        
        echo "📝 New content:\n";
        echo str_repeat("-", 40) . "\n";
        echo $newContent;
        echo str_repeat("-", 40) . "\n\n";
        
        $lastSize = $newSize;
    } else {
        echo "⏳ Check #$checkCount - No new errors\n";
    }
}

echo "\n🏁 Monitoring stopped.\n";
