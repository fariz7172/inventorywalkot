<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$file = __DIR__ . '/assets/DATA MATERIAL GUDANG KETEL UAP ANCOL 2026 (240426).xlsx';
$reader = IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($file);

echo "FINAL ANALYSIS FOR: BATU PECAH (JANUARI 2026)\n\n";

// 1. SALDO Sheet
$sheetSaldo = $spreadsheet->getSheetByName('SALDO');
if ($sheetSaldo) {
    echo "--- SHEET SALDO ---\n";
    echo "BATU PECAH Saldo Akhir 2025 (C7): " . $sheetSaldo->getCell('C7')->getValue() . "\n";
    echo "BATU PECAH Saldo Awal 2026 (D7): " . $sheetSaldo->getCell('D7')->getValue() . " (Formula)\n";
}

// 2. DATABASE TAHUN 2026 (Checking January)
$sheetDb = $spreadsheet->getSheetByName('DATABASE TAHUN 2026');
if ($sheetDb) {
    echo "\n--- SHEET DATABASE TAHUN 2026 (Columns: B=Item, C=In, D=Out) ---\n";
    $totalInDb = 0;
    $totalOutDb = 0;
    $highestRow = $sheetDb->getHighestRow();
    for ($r = 5; $r <= $highestRow; $r++) {
        $item = $sheetDb->getCell('B' . $r)->getValue();
        if (trim($item) === 'BATU PECAH') {
            $dateVal = $sheetDb->getCell('A' . $r)->getValue();
            $isJan = false;
            if (is_numeric($dateVal)) {
                $d = Date::excelToDateTimeObject($dateVal);
                if ($d->format('m') === '01') $isJan = true;
            } elseif (is_string($dateVal) && stripos($dateVal, 'Januari') !== false) {
                $isJan = true;
            }
            
            if ($isJan) {
                $in = (float)$sheetDb->getCell('C' . $r)->getValue();
                $out = (float)$sheetDb->getCell('D' . $r)->getValue();
                $totalInDb += $in;
                $totalOutDb += $out;
                echo "Row $r: Date=$dateVal, In=$in, Out=$out\n";
            }
        }
    }
    echo "TOTAL JANUARI (DATABASE 2026): MASUK=$totalInDb, KELUAR=$totalOutDb\n";
}

// 3. LAPORAN PER MINGGU (Source for Monthly Sum)
$sheetWeekly = $spreadsheet->getSheetByName('LAPORAN PER MINGGU');
if ($sheetWeekly) {
    echo "\n--- SHEET LAPORAN PER MINGGU (Columns: A=Item, K=In, T=Out) ---\n";
    $totalInWeekly = 0;
    $totalOutWeekly = 0;
    // Look in Januari section (usually rows 10 to 1201 based on user formula)
    for ($r = 10; $r <= 1201; $r++) {
        $item = $sheetWeekly->getCell('A' . $r)->getValue();
        if (trim($item) === 'BATU PECAH') {
            $in = (float)$sheetWeekly->getCell('K' . $r)->getValue();
            $out = (float)$sheetWeekly->getCell('T' . $r)->getValue();
            $totalInWeekly += $in;
            $totalOutWeekly += $out;
            echo "Row $r: In=$in, Out=$out\n";
        }
    }
    echo "TOTAL JANUARI (LAPORAN PER MINGGU): MASUK=$totalInWeekly, KELUAR=$totalOutWeekly\n";
}

// 4. LAPORAN PER BULAN
$sheetMonthly = $spreadsheet->getSheetByName('LAPORAN PER BULAN');
if ($sheetMonthly) {
    echo "\n--- SHEET LAPORAN PER BULAN ---";
    for ($r = 10; $r <= 30; $r++) {
        $item = $sheetMonthly->getCell('B' . $r)->getValue();
        if (trim($item) === 'BATU PECAH') {
            echo "\nBATU PECAH at row $r\n";
            echo "SALDO AWAL (D$r): " . $sheetMonthly->getCell('D' . $r)->getValue() . "\n";
            echo "PENERIMAAN (F$r): " . $sheetMonthly->getCell('F' . $r)->getValue() . "\n";
            echo "PENGELUARAN (H$r): " . $sheetMonthly->getCell('H' . $r)->getValue() . "\n";
            echo "SALDO AKHIR (J$r): " . $sheetMonthly->getCell('J' . $r)->getValue() . "\n";
        }
    }
}
