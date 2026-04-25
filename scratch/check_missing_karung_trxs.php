<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DeliveryOrder;
use App\Models\InventoryTransaction;

echo "Mengecek SJ yang ada di DeliveryOrder tapi tidak ada di Transaksi (untuk Karung Plastik):\n";

$allSJs = DeliveryOrder::where('status', 'shipped')->get();
$missingCount = 0;

foreach ($allSJs as $sj) {
    // Cek apakah SJ ini punya material Karung Plastik (ID 9) di pivot table
    $hasKarung = $sj->materials()->where('material_id', 9)->exists();
    
    if ($hasKarung) {
        // Cek apakah sudah ada transaksi untuk Karung Plastik di SJ ini
        $hasTrx = InventoryTransaction::where('delivery_order_id', $sj->id)
            ->where('material_id', 9)
            ->exists();
            
        if (!$hasTrx) {
            echo "- SJ: {$sj->surat_jalan_no} | ID: {$sj->id} | Karung Plastik ADA di detail tapi transaksi TIDAK ADA\n";
            $missingCount++;
        }
    }
}

if ($missingCount == 0) {
    echo "Semua SJ yang punya Karung Plastik sudah memiliki transaksi.\n";
} else {
    echo "Total SJ bermasalah: $missingCount\n";
}
