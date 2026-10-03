<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiRabScannerService
{
    protected string $apiKey;
    // Model fallback list: jika model utama 503 (high demand), otomatis switch ke model cadangan
    protected array $models = [
        'gemini-3.5-flash',
        'gemini-3.6-flash',
        'gemini-3.1-flash-lite',
        'gemini-flash-latest'
    ];
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $preferredModel = env('GEMINI_MODEL', 'gemini-3.5-flash');
        
        // Posisikan preferred model di urutan paling depan
        if (!empty($preferredModel) && !in_array($preferredModel, $this->models)) {
            array_unshift($this->models, $preferredModel);
        } elseif (!empty($preferredModel)) {
            $this->models = array_diff($this->models, [$preferredModel]);
            array_unshift($this->models, $preferredModel);
        }
    }

    /**
     * Menganalisis gambar dokumen RAB dan mengekstrak daftar material beserta volumenya.
     *
     * @param string $imagePath Path absolut file gambar lokal
     * @return array Array berisi [['nama_material' => '...', 'volume' => 12.5], ...]
     */
    public function scanRabImage(string $imagePath): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception("GEMINI_API_KEY belum disetel di file .env!");
        }

        if (!file_exists($imagePath)) {
            throw new \Exception("File gambar tidak ditemukan di: " . $imagePath);
        }

        $imageData = base64_encode(file_get_contents($imagePath));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $imagePath) ?: 'image/jpeg';
        finfo_close($finfo);

        $prompt = <<<EOT
Kamu adalah AI spesialis analisis dokumen Rencana Anggaran Biaya (RAB) dan logistik konstruksi.
FOKUS UTAMA: Ekstrak HANYA daftar material/barang yang ada pada seksi 'B. BAHAN' atau daftar bahan material fisik konstruksi.

ATURAN KETAT:
1. JANGAN mengambil seksi 'A. PEKERJAAN DOKUMENTASI' (cetak foto digital, dsb).
2. JANGAN mengambil seksi 'C. ANGKUTAN' (bahan bakar minyak, pertamina dex, dsb).
3. JANGAN mengambil seksi 'II. GAJI / UPAH' (pekerja, mandor, tukang batu, tukang kayu, dsb).
4. JANGAN mengambil biaya PPN, subtotal, maupun total rupiah.
5. Ambil HANYA item material fisik konstruksi dari seksi BAHAN (contoh: Semen, Pasir Pasang, Pasir Beton, Batu Pecah, Besi Beton, Kawat Beton, Kayu, Paku, Triplek, dll).
6. Kolom volume harus berupa angka (jika kosong/tanda strip, isi 0). Jika ada koma, ubah ke desimal titik (contoh 12,5 jadi 12.5).

WAJIB kembalikan HANYA format JSON valid murni (array of objects) tanpa tanda markdown (tanpa ```json):
[
  {
    "nama_material": "Semen Portland",
    "volume": 46.0
  },
  {
    "nama_material": "Pasir Pasang",
    "volume": 3.0
  }
]
EOT;

        $lastError = null;

        // Coba setiap model secara berurutan jika terjadi error 503 / high demand
        foreach ($this->models as $model) {
            try {
                $url = "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";

                $response = Http::timeout(45)->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $imageData
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'temperature' => 0.1
                    ]
                ]);

                if ($response->successful()) {
                    $text = $response->json('candidates.0.content.parts.0.text');
                    if (empty($text)) {
                        continue;
                    }

                    // Bersihkan jika ada format markdown
                    $cleaned = trim($text);
                    if (str_starts_with($cleaned, '```json')) {
                        $cleaned = substr($cleaned, 7);
                    }
                    if (str_starts_with($cleaned, '```')) {
                        $cleaned = substr($cleaned, 3);
                    }
                    if (str_ends_with($cleaned, '```')) {
                        $cleaned = substr($cleaned, 0, -3);
                    }

                    $items = json_decode(trim($cleaned), true);
                    if (is_array($items)) {
                        Log::info("Gemini OCR Success menggunakan model [{$model}]", [
                            'item_count' => count($items)
                        ]);
                        return $items;
                    }
                }

                // Jika status 503 (High Demand) atau 429, catat dan coba model fallback berikutnya
                $lastError = "Model {$model} HTTP {$response->status()}: " . $response->body();
                Log::warning("Gemini OCR fallback retry: {$lastError}");

            } catch (\Exception $e) {
                $lastError = "Model {$model} error: " . $e->getMessage();
                Log::warning("Gemini OCR exception retry: {$lastError}");
            }
        }

        throw new \Exception("Seluruh model Gemini sedang sibuk/unavailable: " . ($lastError ?? 'Unknown error'));
    }
}
