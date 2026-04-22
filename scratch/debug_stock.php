<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Material;
use App\Models\InventoryTransaction;

$material = Material::where('name', 'like', 'Semen Padang%')->first();
if (!$material) {
    echo "Material not found\n";
    exit;
}

echo "Material: " . $material->name . " (ID: " . $material->id . ")\n";
echo "Current Volume (in Material table): " . $material->current_volume . "\n";

echo "\nRecent Transactions:\n";
$transactions = InventoryTransaction::where('material_id', $material->id)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($transactions as $t) {
    echo "ID: " . $t->id . " | Date: " . $t->created_at . " | Type: " . $t->type . " | In: " . $t->volume_masuk . " | Out: " . $t->volume_keluar . " | Balance After: " . $t->balance_after . " | Ref: " . $t->reference_number . " | Note: " . $t->note . "\n";
}
