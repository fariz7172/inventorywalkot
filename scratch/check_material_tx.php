<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;
use App\Models\Material;

$materials = Material::all();
foreach ($materials as $m) {
    $count = InventoryTransaction::where('material_id', $m->id)->count();
    $outs = InventoryTransaction::where('material_id', $m->id)->where('type', 'out')->count();
    echo "Material: {$m->name} | Total Tx: {$count} | Out Tx: {$outs}\n";
}
