<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;
use App\Models\DeliveryOrder;

echo "--- Repairing Transaction Dates ---\n";

$txs = InventoryTransaction::whereNotNull('delivery_order_id')->with('deliveryOrder')->get();
$count = 0;
foreach ($txs as $tx) {
    if ($tx->deliveryOrder && $tx->created_at->format('Y-m-d') != $tx->deliveryOrder->tanggal->format('Y-m-d')) {
        echo "Updating TX ID: {$tx->id} from {$tx->created_at->format('Y-m-d')} to {$tx->deliveryOrder->tanggal->format('Y-m-d')}\n";
        $tx->update(['created_at' => $tx->deliveryOrder->tanggal]);
        $count++;
    }
}

echo "\nDone. Updated {$count} transactions.\n";
