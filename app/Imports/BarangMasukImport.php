<?php

namespace App\Imports;

use App\Models\Material;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class BarangMasukImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Skip header row
        $dataRows = $rows->slice(1);

        foreach ($dataRows as $row) {
            // Indices:
            // 0: tanggal
            // 1: nama_barang
            // 2: volume_masuk
            // 3: no_referensi
            // 4: supplier
            // 5: keterangan

            if (empty($row[1]) || empty($row[2])) {
                continue;
            }

            $material = Material::where('name', trim($row[1]))->first();

            if (!$material) {
                throw new Exception("Material '{$row[1]}' tidak ditemukan di sistem. Pastikan nama barang sama persis.");
            }

            $date = $this->parseIndonesianDate($row[0] ?? null);

            InventoryTransaction::create([
                'material_id'      => $material->id,
                'type'             => 'in',
                'volume_masuk'     => (float) $row[2],
                'reference_number' => $row[3] ?? null,
                'supplier'         => $row[4] ?? null,
                'note'             => $row[5] ?? 'Import Barang Masuk',
                'user_id'          => Auth::id(),
                'created_at'       => $date,
            ]);
        }
    }

    private function parseIndonesianDate($dateString)
    {
        if (empty($dateString)) {
            return now();
        }

        if (is_numeric($dateString)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateString);
        }

        $months = [
            'januari' => 'January',
            'februari' => 'February',
            'maret' => 'March',
            'april' => 'April',
            'mei' => 'May',
            'juni' => 'June',
            'juli' => 'July',
            'agustus' => 'August',
            'september' => 'September',
            'oktober' => 'October',
            'november' => 'November',
            'desember' => 'December',
        ];

        $dateString = str_replace(array_keys($months), array_values($months), strtolower($dateString));

        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return now();
        }
    }
}
