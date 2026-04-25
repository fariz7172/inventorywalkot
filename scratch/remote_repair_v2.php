<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

$txs = InventoryTransaction::whereNotNull('delivery_order_id')->with('deliveryOrder')->get();
foreach ($txs as $tx) {
    if ($tx->deliveryOrder) {
        $targetDate = $tx->deliveryOrder->tanggal->format('Y-m-d') . ' ' . $tx->created_at->format('H:i:s');
        DB::table('inventory_transactions')
            ->where('id', $tx->id)
            ->update(['created_at' => $targetDate]);
    }
}
echo "Remote Repair V2 complete\n";
