<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryTransaction;

$data = InventoryTransaction::whereHas('material', fn($q) => $q->where('name', 'like', '%TENDA BIRU%'))
    ->get(['id', 'image'])
    ->toArray();

print_r($data);
