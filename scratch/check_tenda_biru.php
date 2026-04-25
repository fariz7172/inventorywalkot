<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;
use App\Models\Material;

$m = Material::where('name', 'TENDA BIRU')->first();
if ($m) {
    $txs = InventoryTransaction::where('material_id', $m->id)->get();
    foreach ($txs as $tx) {
        echo "ID: {$tx->id} | Type: {$tx->type} | In: '{$tx->volume_masuk}' | Out: '{$tx->volume_keluar}' | Balance: '{$tx->balance_after}' | Created: {$tx->created_at}\n";
    }
} else {
    echo "Material not found.\n";
}
