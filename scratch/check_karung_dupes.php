<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

echo "Mencari transaksi duplikat untuk Karung Plastik...\n";

$dupes = InventoryTransaction::where('material_id', 9)
    ->where('volume_keluar', '>', 0)
    ->select('reference_number', 'volume_keluar', DB::raw('COUNT(*) as count'))
    ->groupBy('reference_number', 'volume_keluar')
    ->having('count', '>', 1)
    ->get();

if ($dupes->isEmpty()) {
    echo "Tidak ditemukan transaksi duplikat.\n";
} else {
    echo "Ditemukan " . $dupes->count() . " grup duplikat:\n";
    foreach ($dupes as $d) {
        echo "- Ref: {$d->reference_number} | Vol: {$d->volume_keluar} | Muncul: {$d->count} kali\n";
    }
}

echo "\nMengecek total saldo awal...\n";
$saldoAwal = InventoryTransaction::where('material_id', 9)
    ->where('reference_number', 'SUKU DINAS SUMBER DAYA AIR')
    ->get();
foreach ($saldoAwal as $s) {
    echo "Saldo Awal ID {$s->id}: {$s->volume_masuk} (Ref: {$s->reference_number})\n";
}
