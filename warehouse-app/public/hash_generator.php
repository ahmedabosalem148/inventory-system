<!DOCTYPE html>
<html>
<head>
    <title>Generate PIN Hash</title>
</head>
<body>
    <h1>PIN Hash Generator</h1>
    <?php
    $pin = '5678';
    $hash = password_hash($pin, PASSWORD_BCRYPT);
    echo "<p>PIN: $pin</p>";
    echo "<p>Hash: $hash</p>";
    
    // Test verification
    $verify = password_verify($pin, $hash);
    echo "<p>Verification: " . ($verify ? 'SUCCESS' : 'FAILED') . "</p>";
    ?>
</body>
</html>
