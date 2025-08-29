<?php
// Extract and validate JavaScript from Blade template
$content = file_get_contents('resources/views/warehouses/show.blade.php');

// Find the script section
preg_match('/(<script>.*?<\/script>)/s', $content, $matches);

if (isset($matches[1])) {
    $script = $matches[1];
    
    // Remove Blade directives for basic syntax check
    $js = preg_replace('/@if\([^)]+\)/', 'if (true) {', $script);
    $js = preg_replace('/@endif/', '}', $js);
    $js = preg_replace('/\{\{[^}]+\}\}/', '""', $js);
    
    // Remove script tags
    $js = preg_replace('/<\/?script[^>]*>/', '', $js);
    
    // Write to temp file for checking
    file_put_contents('temp_check.js', $js);
    
    echo "JavaScript extracted and cleaned. Checking syntax...\n";
    echo "Lines around possible error:\n";
    
    $lines = explode("\n", $js);
    for ($i = 425; $i < 435; $i++) {
        if (isset($lines[$i])) {
            echo sprintf("%3d: %s\n", $i + 1, $lines[$i]);
        }
    }
} else {
    echo "No script section found!\n";
}
?>
