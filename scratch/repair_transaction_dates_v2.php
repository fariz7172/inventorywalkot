<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

echo "--- Repairing Transaction Dates (V2) ---\n";

$txs = InventoryTransaction::whereNotNull('delivery_order_id')->with('deliveryOrder')->get();
$count = 0;
foreach ($txs as $tx) {
    if ($tx->deliveryOrder) {
        $targetDate = $tx->deliveryOrder->tanggal->format('Y-m-d') . ' ' . $tx->created_at->format('H:i:s');
        echo "Updating TX ID: {$tx->id} ({$tx->reference_number}) to {$targetDate}\n";
        
        DB::table('inventory_transactions')
            ->where('id', $tx->id)
            ->update(['created_at' => $targetDate]);
            
        $count++;
    }
}

echo "\nDone. Updated {$count} transactions.\n";
