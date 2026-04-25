<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DeliveryOrder;

$dos = DeliveryOrder::with('materials')->get();
foreach ($dos as $do) {
    echo "SJ: {$do->surat_jalan_no} | Status: {$do->status}\n";
    foreach ($do->materials as $m) {
        echo "   - Material: {$m->name} | Volume: {$m->pivot->requested_volume}\n";
    }
}
