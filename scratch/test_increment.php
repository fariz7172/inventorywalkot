<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Material;

$material = Material::first();
echo "Before: " . $material->current_volume . "\n";
$material->increment('current_volume', 5);
echo "After increment: " . $material->current_volume . "\n";
