<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function generateTemplate($filename, $headers, $sampleData) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Headers
    foreach ($headers as $col => $header) {
        $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
    }

    // Sample Data
    foreach ($sampleData as $rowIdx => $rowData) {
        foreach ($rowData as $colIdx => $value) {
            $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowIdx + 2, $value);
        }
    }

    // Auto-size columns
    foreach (range('A', $sheet->getHighestColumn()) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
    echo "Generated: $filename\n";
}

// 1. Template Surat Jalan (NEW ORDER)
// 0: tanggal, 1: nama_barang, 2: volume_keluar, 3: no_polisi, 4: lokasi, 5: pemohon, 6: petugas, 7: no_surat_jalan, 8: kecamatan_pelaksana, 9: keterangan
$sjHeaders = ['tanggal', 'nama_barang', 'volume_keluar', 'no_polisi', 'lokasi', 'pemohon', 'petugas', 'no_surat_jalan', 'kecamatan_pelaksana', 'keterangan'];
$sjSample = [
    ['15 Januari 2026', 'TRIPLEK/MULTIPLEK', 2, 'B 1234 ABC', 'POMPA SINDANG', 'SHOLAHUDDIN', 'Sanjaya', '90/GKU/I/2026', 'PEMELIHARAAN', 'Inventaris'],
    ['15 Januari 2026', 'PAKU 2"-5"', 2, 'B 1234 ABC', 'POMPA SINDANG', 'SHOLAHUDDIN', 'Sanjaya', '90/GKU/I/2026', 'PEMELIHARAAN', 'Inventaris'],
];
generateTemplate(__DIR__.'/../public/templates/template_surat_jalan.xlsx', $sjHeaders, $sjSample);

// 2. Template Barang Masuk
$bmHeaders = ['tanggal', 'nama_barang', 'volume_masuk', 'no_referensi', 'supplier', 'keterangan'];
$bmSample = [
    ['10 Januari 2026', 'TRIPLEK/MULTIPLEK', 100, 'INV-001', 'Toko Bangunan Jaya', 'Restock Bulanan'],
];
generateTemplate(__DIR__.'/../public/templates/template_barang_masuk.xlsx', $bmHeaders, $bmSample);
