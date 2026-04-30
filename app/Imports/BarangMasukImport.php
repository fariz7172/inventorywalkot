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
        $errors = [];

        foreach ($dataRows as $row) {
            // Indices:
            // 0: tanggal
            // 1: nama_barang
            // 2: volume_masuk
            // 3: no_referensi
            // 4: supplier
            // 5: keterangan

            if (empty($row[1]) || !isset($row[2])) {
                continue;
            }

            $name = trim($row[1]);
            $material = Material::where('name', $name)->first();

            if (!$material) {
                // Try case-insensitive search if exact match fails
                $material = Material::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            }

            if (!$material) {
                $errors[] = "Material '$name' tidak ditemukan.";
                continue;
            }

            $date = $this->parseIndonesianDate($row[0] ?? null);

            // Clean volume string from commas (thousand separators)
            $volume = $row[2];
            if (is_string($volume)) {
                $volume = str_replace(',', '', $volume);
            }

            InventoryTransaction::create([
                'material_id'      => $material->id,
                'type'             => 'in',
                'volume_masuk'     => (float) $volume,
                'reference_number' => $row[3] ?? null,
                'supplier'         => $row[4] ?? null,
                'note'             => $row[5] ?? 'Import Barang Masuk',
                'user_id'          => Auth::id(),
                'created_at'       => $date,
            ]);
        }

        if (!empty($errors)) {
            $uniqueErrors = array_unique($errors);
            $message = "Beberapa barang dilewati karena tidak terdaftar: " . implode(', ', array_slice($uniqueErrors, 0, 5));
            if (count($uniqueErrors) > 5) $message .= "... dan " . (count($uniqueErrors) - 5) . " lainnya.";
            
            session()->flash('warning', $message);
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
