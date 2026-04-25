<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Material;
use App\Models\InventoryTransaction;
use Carbon\Carbon;

$year = 2026;
$month = 4;
$date = Carbon::create($year, $month, 1);
$start = $date->copy()->startOfMonth();
$end = $date->copy()->endOfMonth();

echo "Total Materials: " . Material::count() . "\n\n";

$materials = Material::whereIn('id', [9, 54, 45])->get();
foreach($materials as $m) {
    echo "Processing: {$m->name} (Current Volume: {$m->current_volume})\n";
    $mutAfter = InventoryTransaction::where('material_id', $m->id)
        ->where('created_at', '>', $end)
        ->selectRaw('SUM(volume_masuk) - SUM(volume_keluar) as net')
        ->first()->net ?? 0;
    
    $final = (float)$m->current_volume - (float)$mutAfter;
    
    $mutDuring = InventoryTransaction::where('material_id', $m->id)
        ->whereBetween('created_at', [$start, $end])
        ->selectRaw('SUM(volume_masuk) as total_in, SUM(volume_keluar) as total_out')
        ->first();
    
    $in = (float)($mutDuring->total_in ?? 0);
    $out = (float)($mutDuring->total_out ?? 0);
    $opening = $final - ($in - $out);

    echo "Material: {$m->name}\n";
    echo "  Opening: {$opening}\n";
    echo "  In: {$in}\n";
    echo "  Out: {$out}\n";
    echo "  Final: {$final}\n\n";
}
