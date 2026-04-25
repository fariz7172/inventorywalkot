<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;

$targets = [
    '370/GKU/II/2026' => 19320,
    '430/GKU/III/2026' => 15120,
    '500/GKU/III/2026' => 21980,
    '563/GKU/III/2026' => 22000,
    '590/GKU/III/2026' => 28800,
    '628/GKU/III/2026' => 20076,
    '708/GKU/IV/2026' => 31000,
    '733/GKU/IV/2026' => 11840,
    '782/GKU/IV/2026' => 21324,
    '824/GKU/IV/2026' => 52000
];

echo "Spot Check 10 Data dari Gambar Excel:\n";
foreach ($targets as $sj => $expected) {
    $trx = InventoryTransaction::where('material_id', 9)->where('reference_number', $sj)->first();
    if ($trx) {
        $status = ((int)$trx->volume_keluar === $expected) ? "COCOK" : "BERBEDA (DB: {$trx->volume_keluar})";
        echo "- SJ: $sj | Excel: $expected | Status: $status\n";
    } else {
        echo "- SJ: $sj | TIDAK DITEMUKAN DI DB\n";
    }
}
