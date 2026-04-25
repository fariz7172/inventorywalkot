<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;
use App\Models\DeliveryOrder;

echo "--- Recent Inventory Transactions ---\n";
$txs = InventoryTransaction::with('material')->latest()->limit(10)->get();
foreach ($txs as $tx) {
    echo "ID: {$tx->id} | Material: {$tx->material->name} | Type: {$tx->type} | In: {$tx->volume_masuk} | Out: {$tx->volume_keluar} | Date: {$tx->created_at}\n";
}

echo "\n--- Recent Delivery Orders ---\n";
$dos = DeliveryOrder::latest()->limit(5)->get();
foreach ($dos as $do) {
    echo "SJ: {$do->surat_jalan_no} | Status: {$do->status} | Date: {$do->tanggal}\n";
}
