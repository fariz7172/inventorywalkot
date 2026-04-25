<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DeliveryOrder;
use App\Models\InventoryTransaction;

$shippedSJs = DeliveryOrder::where('status', 'shipped')->get();
foreach ($shippedSJs as $sj) {
    $txCount = $sj->transactions()->count();
    $materialCount = $sj->materials()->count();
    echo "SJ: {$sj->surat_jalan_no} | Materials: {$materialCount} | Transactions: {$txCount}\n";
    
    if ($txCount < $materialCount) {
        echo "   -> WARNING: Transaction mismatch!\n";
    }
}

$draftSJs = DeliveryOrder::where('status', 'draft')->count();
echo "\nTotal Draft SJs: {$draftSJs}\n";
