<?php

namespace App\Imports;

use App\Models\Material;
use App\Models\DeliveryOrder;
use App\Models\InventoryTransaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class SuratJalanImport implements ToCollection
{
    private $logFile;

    public function __construct()
    {
        $this->logFile = base_path('storage/logs/import_debug.log');
        $this->log("--- Import Started at " . now()->toDateTimeString() . " ---");
    }

    private function log($message)
    {
        file_put_contents($this->logFile, "[" . now()->toDateTimeString() . "] " . $message . PHP_EOL, FILE_APPEND);
        Log::info($message);
    }

    public function collection(Collection $rows)
    {
        $this->log("Total rows received from Excel: " . $rows->count());

        if ($rows->count() <= 1) {
            $this->log("Error: File appears to have no data rows (only header or empty).");
            return;
        }

        $dataRows = $rows->slice(1);
        $this->log("Processing " . $dataRows->count() . " data rows with NEW COLUMN ORDER.");

        // New Column Order:
        // 0: tanggal
        // 1: nama_barang
        // 2: volume_keluar
        // 3: no_polisi
        // 4: lokasi
        // 5: pemohon
        // 6: petugas
        // 7: no_surat_jalan
        // 8: kecamatan_pelaksana
        // 9: keterangan
        // 10: penerima

        $lastSjNo = null;
        $lastDate = null;
        $lastLokasi = null;
        $lastPemohon = null;
        $lastPetugas = null;
        $lastPolisi = null;
        $lastKecamatan = null;
        $lastKeterangan = null;
        $lastPenerima = null;

        $processedRows = new Collection();

        foreach ($dataRows as $index => $row) {
            // Skip rows where both material and SJ no are empty
            if (empty($row[7]) && empty($row[1])) {
                continue;
            }

            // If No Surat Jalan (index 7) is filled, we consider it a new document or start of a document's items
            if (!empty($row[7])) {
                $lastSjNo = trim($row[7]);
                $lastDate = $row[0];
                $lastPolisi = $row[3] ?? null;
                $lastLokasi = $row[4] ?? '-';
                $lastPemohon = $row[5] ?? '-';
                $lastPetugas = $row[6] ?? 'SANJAYA';
                $lastKecamatan = $row[8] ?? '-';
                $lastKeterangan = $row[9] ?? null;
                $lastPenerima = $row[10] ?? '-';
            }

            $processedRows->push([
                'no_surat_jalan' => $lastSjNo,
                'tanggal' => $lastDate,
                'nama_barang' => $row[1] ?? null,
                'volume_keluar' => $row[2] ?? 0,
                'no_polisi' => $lastPolisi,
                'lokasi' => $lastLokasi,
                'pemohon' => $lastPemohon,
                'petugas' => $lastPetugas,
                'kecamatan_pelaksana' => $lastKecamatan,
                'keterangan' => $lastKeterangan,
                'penerima' => $lastPenerima,
            ]);
        }

        $groups = $processedRows->groupBy('no_surat_jalan');
        $this->log("Grouped into " . $groups->count() . " unique Surat Jalans.");

        foreach ($groups as $sjNo => $items) {
            if (empty($sjNo)) continue;

            try {
                DB::transaction(function () use ($sjNo, $items) {
                    $first = $items->first();
                    $date = $this->parseIndonesianDate($first['tanggal']);

                    $deliveryOrder = DeliveryOrder::where('surat_jalan_no', $sjNo)->first();
                    $isNewOrder = false;
                    
                    if (!$deliveryOrder) {
                        $isNewOrder = true;
                        $this->log("Creating NEW Delivery Order: $sjNo");
                        $deliveryOrder = DeliveryOrder::create([
                            'surat_jalan_no' => $sjNo,
                            'tanggal' => $date,
                            'lokasi' => $first['lokasi'],
                            'pemohon' => $first['pemohon'],
                            'petugas' => $first['petugas'],
                            'no_polisi' => $first['no_polisi'],
                            'pelaksana_kecamatan' => $first['kecamatan_pelaksana'],
                            'keterangan' => $first['keterangan'],
                            'penerima' => $first['penerima'],
                            'status' => 'shipped', // Set as shipped automatically
                            'user_id' => Auth::id() ?? 1,
                        ]);
                    }

                    foreach ($items as $item) {
                        if (empty($item['nama_barang']) || empty($item['volume_keluar'])) {
                            continue;
                        }

                        $materialName = trim($item['nama_barang']);
                        $material = Material::where('name', $materialName)->first();
                        
                        if (!$material) {
                            $this->log("ERROR: Material '$materialName' not found for SJ $sjNo. Skipping item.");
                            continue;
                        }

                        $volume = (float) $item['volume_keluar'];

                        // Link to Delivery Order (Pivot)
                        $deliveryOrder->materials()->syncWithoutDetaching([
                            $material->id => ['requested_volume' => $volume]
                        ]);

                        // Only create transaction if this is a newly imported order
                        // This prevents duplicating transactions if imported twice
                        if ($isNewOrder) {
                            InventoryTransaction::create([
                                'delivery_order_id' => $deliveryOrder->id,
                                'material_id' => $material->id,
                                'type' => 'out',
                                'volume_keluar' => $volume,
                                'reference_number' => $deliveryOrder->surat_jalan_no,
                                'user_id' => Auth::id() ?? 1,
                                'note' => 'Import Excel: ' . $deliveryOrder->surat_jalan_no,
                                'created_at' => $deliveryOrder->tanggal,
                            ]);
                        }
                    }
                });
            } catch (\Exception $e) {
                $this->log("CRITICAL ERROR in SJ $sjNo: " . $e->getMessage());
                throw $e;
            }
        }
        $this->log("--- Import Finished ---");
    }

    private function parseIndonesianDate($dateString)
    {
        if (empty($dateString)) return now();
        if (is_numeric($dateString)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateString);
        }
        $months = [
            'januari' => 'January', 'februari' => 'February', 'maret' => 'March',
            'april' => 'April', 'mei' => 'May', 'juni' => 'June',
            'juli' => 'July', 'agustus' => 'August', 'september' => 'September',
            'oktober' => 'October', 'november' => 'November', 'desember' => 'December',
        ];
        $dateString = str_replace(array_keys($months), array_values($months), strtolower($dateString));
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return now();
        }
    }
}
