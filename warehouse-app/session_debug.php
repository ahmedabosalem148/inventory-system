<?php
session_start();

echo "🔐 Session Debug للمخزن 4\n";
echo "==========================\n\n";

echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);

echo "\nWarehouse 4 Auth: " . (isset($_SESSION['warehouse_4_auth']) ? 'YES' : 'NO') . "\n";

if (isset($_SESSION['warehouse_4_auth'])) {
    echo "Auth Value: " . $_SESSION['warehouse_4_auth'] . "\n";
}

echo "\nTo set auth manually, visit:\n";
echo "http://localhost:8000/warehouses/4/login\n";
echo "Password: 1234\n";
?>
