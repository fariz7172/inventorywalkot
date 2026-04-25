<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\Material;
use App\Models\InventoryTransaction;
use Carbon\Carbon;

// Use the exact same file that failed
$filename = 'D:/program file/Project Kantor/Inventory/storage/framework/cache/laravel-excel/laravel-excel-KPQMEDaWFBP4aGb8xpnkk0MTGpH8IRSj.html';

if (!file_exists($filename)) {
    die("File not found: $filename");
}

$content = file_get_contents($filename);

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$loaded = $dom->loadHTML($content);

if (!$loaded) {
    echo "Failed to load HTML!\n";
    foreach (libxml_get_errors() as $error) {
        echo "Error: " . $error->message . "\n";
    }
} else {
    echo "HTML loaded successfully in pure DOMDocument!\n";
}
