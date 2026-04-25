<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::select('DESCRIBE materials');
foreach ($columns as $column) {
    echo $column->Field . ": " . $column->Type . "\n";
}

echo "\n--- delivery_order_materials ---\n";
$columns = DB::select('DESCRIBE delivery_order_materials');
foreach ($columns as $column) {
    echo $column->Field . ": " . $column->Type . "\n";
}
