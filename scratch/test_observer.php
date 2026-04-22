<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;
use App\Models\Material;

$material = Material::first();
echo "Current Volume Before: " . $material->current_volume . "\n";

$transaction = InventoryTransaction::create([
    'material_id' => $material->id,
    'type' => 'in',
    'volume_masuk' => 10,
    'reference_number' => 'TEST-01',
    'user_id' => 1,
    'note' => 'Test Transaction'
]);

echo "Current Volume After: " . $material->fresh()->current_volume . "\n";
echo "Transaction Balance After: " . $transaction->balance_after . "\n";
