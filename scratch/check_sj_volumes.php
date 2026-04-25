<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;

$targets = [
    '228/GKU/II/2026',
    '232/GKU/II/2026',
    '239/GKU/II/2026',
    '650/GKU/IV/2026',
    '888/GKU/IV/2026',
    '552/GKU/III/2026'
];

echo "Mengecek data di Database:\n";
foreach ($targets as $sj) {
    $trx = InventoryTransaction::where('material_id', 9)->where('reference_number', $sj)->first();
    if ($trx) {
        echo "- SJ: {$trx->reference_number} | Volume: {$trx->volume_keluar}\n";
    } else {
        echo "- SJ: $sj | TIDAK DITEMUKAN\n";
    }
}
