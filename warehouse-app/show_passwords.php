<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$warehouses = \App\Models\Warehouse::all();
foreach($warehouses as $w) {
    echo "المخزن: {$w->name} - كلمة المرور: {$w->password}\n";
}
?>
