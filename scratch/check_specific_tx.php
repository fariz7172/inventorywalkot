<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;

$tx = InventoryTransaction::where('reference_number', '44/GKU/I/2026')->first();
if ($tx) {
    echo "ID: {$tx->id}\n";
    echo "Ref: {$tx->reference_number}\n";
    echo "Date: {$tx->created_at}\n";
} else {
    echo "Transaction 44/GKU/I/2026 not found.\n";
}
