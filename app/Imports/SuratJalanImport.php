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

        // New Column Order (Updated):
        // 0: tanggal
        // 1: nama_barang
        // 2: volume_keluar
        // 3: no_polisi
        // 4: lokasi
        // 5: pemohon
        // 6: petugas
        // 7: penerima
        // 8: no_surat_jalan
        // 9: kecamatan_pelaksana
        // 10: keterangan

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
            // Skip rows where both material and date are empty
            if (empty($row[0]) && empty($row[1])) {
                continue;
            }

            // Handle No Surat Jalan (index 8)
            if (!empty($row[8])) {
                // New explicit SJ number found
                $lastSjNo = trim($row[8]);
                $lastDate = $row[0];
                $lastPolisi = !empty($row[3]) ? trim($row[3]) : 'Data Belum Di Input';
                $lastLokasi = !empty($row[4]) && trim($row[4]) !== '-' ? trim($row[4]) : 'Data Belum Di Input';
                $lastPemohon = !empty($row[5]) && trim($row[5]) !== '-' ? trim($row[5]) : 'Data Belum Di Input';
                $lastPetugas = !empty($row[6]) ? trim($row[6]) : 'Data Belum Di Input';
                $lastPenerima = !empty($row[7]) && trim($row[7]) !== '-' ? trim($row[7]) : 'Data Belum Di Input';
                $lastKecamatan = !empty($row[9]) && trim($row[9]) !== '-' ? trim($row[9]) : 'Data Belum Di Input';
                $lastKeterangan = !empty($row[10]) ? trim($row[10]) : 'Data Belum Di Input';
            } elseif (empty($lastSjNo) && !empty($row[1])) {
                // If No SJ is empty AND we don't have a previous SJ (first row of a block is missing SJ)
                // Generate automatic SJ number based on date
                $tempDate = $this->parseIndonesianDate($row[0]);
                $lastSjNo = "SJ-TANPA-NOMOR-" . $tempDate->format('Ymd') . "-" . strtoupper(substr(uniqid(), -4));
                $lastDate = $row[0];
                
                $this->log("Generating auto SJ number: $lastSjNo for material " . $row[1]);

                $lastPolisi = 'Data Belum Di Input';
                $lastLokasi = 'Data Belum Di Input';
                $lastPemohon = 'Data Belum Di Input';
                $lastPetugas = 'Data Belum Di Input';
                $lastPenerima = 'Data Belum Di Input';
                $lastKecamatan = 'Data Belum Di Input';
                $lastKeterangan = 'Data Belum Di Input';
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
                'penerima' => $lastPenerima,
                'kecamatan_pelaksana' => $lastKecamatan,
                'keterangan' => $lastKeterangan,
            ]);
        }

        $groups = $processedRows->groupBy('no_surat_jalan');
        $this->log("Grouped into " . $groups->count() . " unique Surat Jalans.");

        // Pre-fetch materials to avoid N+1 queries
        $allMaterialNames = $processedRows->pluck('nama_barang')->unique()->filter()->map(fn($n) => trim($n))->toArray();
        $materialsMap = Material::whereIn('name', $allMaterialNames)->get()->keyBy('name');

        foreach ($groups as $sjNo => $items) {
            if (empty($sjNo)) continue;

            try {
                DB::transaction(function () use ($sjNo, $items, $materialsMap) {
                    $first = $items->first();
                    $date = $this->parseIndonesianDate($first['tanggal']);

                    $deliveryOrder = DeliveryOrder::where('surat_jalan_no', $sjNo)->first();
                    
                    $doData = [
                        'surat_jalan_no' => $sjNo,
                        'tanggal' => $date,
                        'lokasi' => !empty($first['lokasi']) && $first['lokasi'] !== '-' ? $first['lokasi'] : 'Data Belum Di Input',
                        'pemohon' => !empty($first['pemohon']) && $first['pemohon'] !== '-' ? $first['pemohon'] : 'Data Belum Di Input',
                        'petugas' => !empty($first['petugas']) ? $first['petugas'] : 'Data Belum Di Input',
                        'no_polisi' => !empty($first['no_polisi']) ? $first['no_polisi'] : 'Data Belum Di Input',
                        'pelaksana_kecamatan' => !empty($first['kecamatan_pelaksana']) && $first['kecamatan_pelaksana'] !== '-' ? $first['kecamatan_pelaksana'] : 'Data Belum Di Input',
                        'keterangan' => !empty($first['keterangan']) ? $first['keterangan'] : 'Data Belum Di Input',
                        'penerima' => !empty($first['penerima']) && $first['penerima'] !== '-' ? $first['penerima'] : 'Data Belum Di Input',
                        'status' => 'shipped',
                        'user_id' => Auth::id() ?? 1,
                    ];

                    if ($deliveryOrder) {
                        $this->log("UPDATING existing Delivery Order: $sjNo");
                        $deliveryOrder->update($doData);
                    } else {
                        $this->log("Creating NEW Delivery Order: $sjNo");
                        $deliveryOrder = DeliveryOrder::create($doData);
                    }

                    foreach ($items as $item) {
                        if (empty($item['nama_barang']) || empty($item['volume_keluar'])) {
                            continue;
                        }

                        $materialName = trim($item['nama_barang']);
                        $material = $materialsMap->get($materialName);
                        
                        if (!$material) {
                            $this->log("ERROR: Material '$materialName' not found for SJ $sjNo. Skipping item.");
                            continue;
                        }

                        $volume = (float) $item['volume_keluar'];

                        // Validasi sisa stok
                        $currentStock = (float) $material->fresh()->current_volume;
                        if ($volume > $currentStock) {
                            throw new \Exception("Stok {$material->name} tidak cukup (Sisa: {$currentStock}, Diminta: {$volume}) pada baris No Surat Jalan: {$sjNo}. Silakan kurangi atau hapus baris tersebut lalu ulangi import.");
                        }

                        // 1. Update/Link to Delivery Order (Pivot)
                        $deliveryOrder->materials()->syncWithoutDetaching([
                            $material->id => ['requested_volume' => $volume]
                        ]);

                        // 2. Update/Create Inventory Transaction
                        InventoryTransaction::updateOrCreate(
                            [
                                'delivery_order_id' => $deliveryOrder->id,
                                'material_id' => $material->id,
                                'type' => 'out',
                            ],
                            [
                                'volume_keluar' => $volume,
                                'reference_number' => $deliveryOrder->surat_jalan_no,
                                'user_id' => Auth::id() ?? 1,
                                'note' => 'Import Excel (Update): ' . $deliveryOrder->surat_jalan_no,
                                'created_at' => $deliveryOrder->tanggal,
                            ]
                        );
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
