<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;

$trxs = InventoryTransaction::where('material_id', 25)->get();
foreach ($trxs as $t) {
    if (fmod($t->volume_masuk, 1) != 0 || fmod($t->volume_keluar, 1) != 0) {
        echo "ID: {$t->id} | Ref: {$t->reference_number} | In: {$t->volume_masuk} | Out: {$t->volume_keluar} | Date: {$t->created_at}\n";
    }
}
