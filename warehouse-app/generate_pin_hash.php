<?php
// Generate bcrypt hash for PIN "5678"
echo "PIN: 5678\n";
echo "Hash: " . password_hash('5678', PASSWORD_BCRYPT) . "\n";
echo "Verifying: " . (password_verify('5678', password_hash('5678', PASSWORD_BCRYPT)) ? 'YES' : 'NO') . "\n";
?>
