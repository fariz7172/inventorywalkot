<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
\App\Models\InventoryTransaction::whereNotNull('delivery_order_id')->with('deliveryOrder')->get()->each(function($tx) {
    if ($tx->deliveryOrder) { $tx->update(['created_at' => $tx->deliveryOrder->tanggal]); }
});
echo "Repair complete\n";
