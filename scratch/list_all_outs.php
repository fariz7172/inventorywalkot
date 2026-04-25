<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;

$count = InventoryTransaction::where('type', 'out')->count();
echo "Total OUT transactions: " . $count . "\n";

$txs = InventoryTransaction::where('type', 'out')->with('material', 'deliveryOrder')->get();
foreach ($txs as $tx) {
    echo "Material: " . $tx->material->name . " | Vol: " . $tx->volume_keluar . " | SJ: " . ($tx->deliveryOrder->surat_jalan_no ?? 'N/A') . " | Date: " . $tx->created_at . "\n";
}
