<?php

require __DIR__.'/../vendor/autoload.php';

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DebugImport implements ToCollection, WithHeadingRow {
    public function collection(Collection $rows) {
        echo "Row Count: " . $rows->count() . "\n";
        if ($rows->count() > 0) {
            echo "Headers found: " . implode(', ', array_keys($rows->first()->toArray())) . "\n";
            echo "First Row Data:\n";
            print_r($rows->first()->toArray());
        } else {
            echo "No rows found (check if heading row is correct).\n";
        }
    }
}

// Find the latest file in temporary directory
$tmpDir = __DIR__.'/../storage/app/livewire-tmp';
$files = glob("$tmpDir/*.xlsx");
if (empty($files)) {
    die("No files found in $tmpDir\n");
}

usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$latestFile = $files[0];
echo "Checking file: $latestFile\n";

// We need a dummy app instance to run Excel::import if it depends on it, 
// but since I'm just debugging, I'll use PhpSpreadsheet directly or a simple wrapper.

$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($latestFile);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();

echo "Raw Rows Count: " . count($rows) . "\n";
echo "Sheet Names: " . implode(', ', $spreadsheet->getSheetNames()) . "\n";
echo "First 5 rows of ACTIVE SHEET:\n";
print_r(array_slice($rows, 0, 5));
