<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppResesApiService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.app_reses.url', 'https://app-reses.farizahmad.com/api'), '/');
        $this->token = config('services.app_reses.token', '');
    }

    /**
     * Daftar Kecamatan di Jakarta Utara (Sesuai App-Reses)
     */
    public static function getKecamatans(): array
    {
        return [
            1 => 'Cilincing',
            2 => 'Kelapa Gading',
            3 => 'Koja',
            4 => 'Pademangan',
            5 => 'Penjaringan',
            6 => 'Tanjung Priok',
        ];
    }

    /**
     * Daftar Kelurahan per Kecamatan di Jakarta Utara (Sesuai App-Reses)
     */
    public static function getKelurahans(?int $kecamatanId = null): array
    {
        $kelurahans = [
            1 => [ // Cilincing
                1  => 'Cilincing',
                2  => 'Semper Barat',
                7  => 'Kali Baru',
                8  => 'Semper Timur',
                9  => 'Sukapura',
                10 => 'Rorotan',
                11 => 'Marunda',
            ],
            2 => [ // Kelapa Gading
                3  => 'Kelapa Gading Barat',
                4  => 'Pegangsaan Dua',
                12 => 'Kelapa Gading Timur',
            ],
            3 => [ // Koja
                5  => 'Koja',
                6  => 'Lagoa',
                13 => 'Tugu Utara',
                14 => 'Tugu Selatan',
                15 => 'Rawa Badak Utara',
                16 => 'Rawa Badak Selatan',
            ],
            4 => [ // Pademangan
                17 => 'Pademangan Timur',
                18 => 'Pademangan Barat',
                19 => 'Ancol',
            ],
            5 => [ // Penjaringan
                20 => 'Penjaringan',
                21 => 'Pluit',
                22 => 'Pejagalan',
                23 => 'Kapuk Muara',
                24 => 'Kamal Muara',
            ],
            6 => [ // Tanjung Priok
                25 => 'Tanjung Priok',
                26 => 'Kebon Bawang',
                27 => 'Sungai Bambu',
                28 => 'Papanggo',
                29 => 'Warakas',
                30 => 'Sunter Agung',
                31 => 'Sunter Jaya',
            ],
        ];

        if ($kecamatanId !== null) {
            return $kelurahans[$kecamatanId] ?? [];
        }

        // Return flattened list if no kecamatan specified
        $all = [];
        foreach ($kelurahans as $sub) {
            foreach ($sub as $id => $name) {
                $all[$id] = $name;
            }
        }
        return $all;
    }

    /**
     * Daftar Anggota Dewan di App-Reses
     */
    public static function getDewans(): array
    {
        return [
            21 => 'Tidak Diketahui (Default)',
            1  => 'H. Ahmad (Komisi A)',
            6  => 'Suhud Alynudin S.IP., M.Sc',
            7  => 'H. Tri Waluyo, SH',
            8  => 'Bun Joi Phiau',
            9  => 'Hj. Ida Mahmudah',
            10 => 'Hj. Neneng Hasanah, SE',
            11 => 'Hengky Wijaya',
            12 => 'K.H.M. Subki, Lc',
            13 => 'Agustina Hermanto',
            14 => 'H. Ramly HI Muhamad, S.Sos, M.Si',
            15 => 'Anggi Arando Siregar',
            16 => 'H. Imamuddin',
            17 => 'Jhonny Simanjuntak, SH',
            18 => 'Gani Suwondo Lie, S.H., M.H.',
            19 => 'Oman Rohman Rakinda, S.IP., M.Si',
            20 => 'H. Muhammad Idris, SE',
            22 => '*Ida Mahmuda',
        ];
    }

    /**
     * Daftar Pelaksana di App-Reses
     */
    public static function getPelaksanas(): array
    {
        return [
            2 => 'SUDIN JAKARTA UTARA (Default)',
            1 => 'Slamet Riyanto (Satpel SDA Koja)',
        ];
    }

    /**
     * Kategori Pekerjaan SDA
     */
    public static function getKategoriPekerjaans(): array
    {
        return [
            'Pemasangan Saluran Precast U-Ditch' => 'Pemasangan Saluran Precast U-Ditch',
            'Perbaikan Turap Saluran'            => 'Perbaikan Turap Saluran',
            'Perbaikan Turap Kali / Waduk'       => 'Perbaikan Turap Kali / Waduk',
            'Pengurasan Saluran'                 => 'Pengurasan Saluran',
            'Pengerukan kali/Waduk'              => 'Pengerukan kali/Waduk',
            'Pemasangan Tutup Saluran'           => 'Pemasangan Tutup Saluran',
            'Pembuatan Saluran Beton/ Batu Kali' => 'Pembuatan Saluran Beton/ Batu Kali',
            'MCK Komunal'                        => 'MCK Komunal',
            'Sodetan'                            => 'Sodetan',
            'NCICD'                              => 'NCICD',
        ];
    }

    /**
     * Lingkup Kewenangan SDA
     */
    public static function getLingkupKewenangans(): array
    {
        return [
            'Seksi Pemeliharaan' => 'Seksi Pemeliharaan',
            'Seksi Pembangunan'  => 'Seksi Pembangunan',
            'Satuan Pelaksana'   => 'Satuan Pelaksana',
            'Seksi Pompa'        => 'Seksi Pompa',
            'Dinas SDA'          => 'Dinas SDA',
        ];
    }

    /**
     * Metode Pekerjaan SDA
     */
    public static function getMetodePekerjaans(): array
    {
        return [
            'Swakelola'            => 'Swakelola',
            'Katalog/Pihak Ketiga' => 'Katalog/Pihak Ketiga',
        ];
    }

    /**
     * Auto detect Kecamatan & Kelurahan dari string Lokasi / Alamat
     */
    public static function autoDetectLocation(string $text, ?int $defaultKecamatanId = null): array
    {
        $upper = strtoupper($text);
        $detectedKecamatan = $defaultKecamatanId;
        $detectedKelurahan = null;

        // Check specific kelurahans first (highest precision)
        $allKelurahans = [
            // Cilincing
            10 => ['ROROTAN', 1],
            11 => ['MARUNDA', 1],
            9  => ['SUKAPURA', 1],
            7  => ['KALI BARU', 1],
            71 => ['KALIBARU', 1],
            2  => ['SEMPER BARAT', 1],
            8  => ['SEMPER TIMUR', 1],
            1  => ['CILINCING', 1],
            // Kelapa Gading
            3  => ['KELAPA GADING BARAT', 2],
            12 => ['KELAPA GADING TIMUR', 2],
            4  => ['PEGANGSAAN DUA', 2],
            41 => ['PEGANGSAAN', 2],
            // Koja
            15 => ['RAWA BADAK UTARA', 3],
            16 => ['RAWA BADAK SELATAN', 3],
            13 => ['TUGU UTARA', 3],
            14 => ['TUGU SELATAN', 3],
            6  => ['LAGOA', 3],
            5  => ['KOJA', 3],
            // Pademangan
            17 => ['PADEMANGAN TIMUR', 4],
            18 => ['PADEMANGAN BARAT', 4],
            19 => ['ANCOL', 4],
            // Penjaringan
            21 => ['PLUIT', 5],
            22 => ['PEJAGALAN', 5],
            23 => ['KAPUK MUARA', 5],
            24 => ['KAMAL MUARA', 5],
            20 => ['PENJARINGAN', 5],
            // Tanjung Priok
            30 => ['SUNTER AGUNG', 6],
            31 => ['SUNTER JAYA', 6],
            29 => ['WARAKAS', 6],
            28 => ['PAPANGGO', 6],
            27 => ['SUNGAI BAMBU', 6],
            26 => ['KEBON BAWANG', 6],
            25 => ['TANJUNG PRIOK', 6],
        ];

        foreach ($allKelurahans as $kelId => [$keyword, $kecId]) {
            if (str_contains($upper, $keyword)) {
                $actualKelId = $kelId === 71 ? 7 : ($kelId === 41 ? 4 : $kelId);
                $detectedKelurahan = $actualKelId;
                $detectedKecamatan = $kecId;
                break;
            }
        }

        // If kelurahan didn't match, check kecamatan keywords
        if (!$detectedKecamatan) {
            if (str_contains($upper, 'KELAPA GADING')) {
                $detectedKecamatan = 2;
            } elseif (str_contains($upper, 'CILINCING')) {
                $detectedKecamatan = 1;
            } elseif (str_contains($upper, 'KOJA')) {
                $detectedKecamatan = 3;
            } elseif (str_contains($upper, 'PADEMANGAN')) {
                $detectedKecamatan = 4;
            } elseif (str_contains($upper, 'PENJARINGAN')) {
                $detectedKecamatan = 5;
            } elseif (str_contains($upper, 'PRIOK') || str_contains($upper, 'SUNTER')) {
                $detectedKecamatan = 6;
            }
        }

        // If kecamatan is valid (1-6) but no kelurahan detected, default to first kelurahan
        if ($detectedKecamatan && $detectedKecamatan <= 6 && !$detectedKelurahan) {
            $kelList = self::getKelurahans($detectedKecamatan);
            if (!empty($kelList)) {
                $detectedKelurahan = array_key_first($kelList);
            }
        }

        // If default kecamatan was passed and is within 1-6
        if (!$detectedKecamatan && $defaultKecamatanId && $defaultKecamatanId <= 6) {
            $detectedKecamatan = $defaultKecamatanId;
            $kelList = self::getKelurahans($detectedKecamatan);
            if (!empty($kelList)) {
                $detectedKelurahan = array_key_first($kelList);
            }
        }

        return [
            'id_kecamatan' => $detectedKecamatan ?: 1,
            'id_kelurahan' => $detectedKelurahan ?: 1,
        ];
    }

    /**
     * Kirim data ke POST /api/survei-reses
     */
    /**
     * Helper untuk melampirkan file foto ke HTTP request secara aman (mendukung string path dan object UploadedFile/TemporaryUploadedFile).
     */
    private function attachPhotosToRequest(&$req, array $photos, string $primaryKey, string $secondaryKey): void
    {
        foreach ($photos as $photo) {
            $contents = null;
            $filename = 'photo_' . uniqid() . '.jpg';

            if (is_string($photo) && file_exists($photo)) {
                $contents = file_get_contents($photo);
                $filename = basename($photo);
            } elseif (is_object($photo)) {
                // Mendukung Livewire TemporaryUploadedFile ($photo->get()) dan Standard UploadedFile
                if (method_exists($photo, 'get')) {
                    $contents = $photo->get();
                } elseif (method_exists($photo, 'getRealPath') && file_exists($photo->getRealPath())) {
                    $contents = file_get_contents($photo->getRealPath());
                }

                if (method_exists($photo, 'getClientOriginalName')) {
                    $filename = $photo->getClientOriginalName();
                } elseif (method_exists($photo, 'getFilename')) {
                    $filename = $photo->getFilename();
                }
            }

            if ($contents !== null) {
                // Lampirkan di bawah primary key dan secondary key agar kompatibel dengan controller mana pun
                $req->attach($primaryKey, $contents, $filename);
                if ($primaryKey !== $secondaryKey) {
                    $req->attach($secondaryKey, $contents, $filename);
                }
            }
        }
    }

    /**
     * Kirim data ke POST /api/survei-reses
     */
    public function sendSurveiReses(array $data, array $photos = []): array
    {
        try {
            $url = "{$this->baseUrl}/survei-reses";
            $req = Http::withToken($this->token)->acceptJson();

            // Attach photos if any (mendukung foto[] dan photo[])
            if (!empty($photos)) {
                $this->attachPhotosToRequest($req, $photos, 'foto[]', 'photo[]');
                $response = $req->post($url, $data);
            } else {
                $response = $req->asForm()->post($url, $data);
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'data' => $response->json(),
                    'message' => 'Data berhasil dikirim ke Survei Reses.',
                ];
            }

            $errorMessage = $response->json('message') ?? 'Gagal mengirim data ke API Survei Reses (Status: ' . $response->status() . ')';
            $errors = $response->json('errors') ?? [];

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => $errorMessage,
                'errors' => $errors,
                'raw' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('AppResesApiService::sendSurveiReses error: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Koneksi ke App-Reses API gagal: ' . $e->getMessage(),
                'errors' => [],
            ];
        }
    }

    /**
     * Kirim data langsung ke POST /api/pekerjaan-sda
     */
    public function sendPekerjaanSda(array $data, array $photos = []): array
    {
        try {
            $url = "{$this->baseUrl}/pekerjaan-sda";
            $req = Http::withToken($this->token)->acceptJson();

            if (!empty($photos)) {
                $this->attachPhotosToRequest($req, $photos, 'photo[]', 'foto[]');
                $response = $req->post($url, $data);
            } else {
                $response = $req->asForm()->post($url, $data);
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'data' => $response->json(),
                    'message' => 'Data berhasil dikirim ke Pekerjaan SDA.',
                ];
            }

            $errorMessage = $response->json('message') ?? 'Gagal mengirim data ke API Pekerjaan SDA (Status: ' . $response->status() . ')';
            $errors = $response->json('errors') ?? [];

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => $errorMessage,
                'errors' => $errors,
                'raw' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('AppResesApiService::sendPekerjaanSda error: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Koneksi ke App-Reses API gagal: ' . $e->getMessage(),
                'errors' => [],
            ];
        }
    }

    /**
     * Update data Survei Reses (PUT|POST /api/survei-reses/{id})
     */
    public function updateSurveiReses(int $id, array $data, array $photos = []): array
    {
        try {
            $url = "{$this->baseUrl}/survei-reses/{$id}";
            $req = Http::withToken($this->token)->acceptJson();
            $payload = array_merge($data, ['_method' => 'PUT']);

            if (!empty($photos)) {
                $this->attachPhotosToRequest($req, $photos, 'foto[]', 'photo[]');
                $response = $req->post($url, $payload);
            } else {
                $response = $req->asForm()->post($url, $payload);
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'data' => $response->json(),
                    'message' => "Data Survei Reses #{$id} berhasil diperbarui.",
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => $response->json('message') ?? "Gagal memperbarui Survei Reses #{$id}.",
                'errors' => $response->json('errors') ?? [],
                'raw' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error("AppResesApiService::updateSurveiReses error: " . $e->getMessage());
            return [
                'success' => false,
                'status' => 500,
                'message' => "Koneksi ke App-Reses API gagal saat update Survei Reses #{$id}: " . $e->getMessage(),
                'errors' => [],
            ];
        }
    }

    /**
     * Update data Pekerjaan SDA (PUT|POST /api/pekerjaan-sda/{id})
     */
    public function updatePekerjaanSda(int $id, array $data, array $photos = []): array
    {
        try {
            $url = "{$this->baseUrl}/pekerjaan-sda/{$id}";
            $req = Http::withToken($this->token)->acceptJson();
            $payload = array_merge($data, ['_method' => 'PUT']);

            if (!empty($photos)) {
                $this->attachPhotosToRequest($req, $photos, 'photo[]', 'foto[]');
                $response = $req->post($url, $payload);
            } else {
                $response = $req->asForm()->post($url, $payload);
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'data' => $response->json(),
                    'message' => "Data Pekerjaan SDA #{$id} berhasil diperbarui.",
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => $response->json('message') ?? "Gagal memperbarui Pekerjaan SDA #{$id}.",
                'errors' => $response->json('errors') ?? [],
                'raw' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error("AppResesApiService::updatePekerjaanSda error: " . $e->getMessage());
            return [
                'success' => false,
                'status' => 500,
                'message' => "Koneksi ke App-Reses API gagal saat update Pekerjaan SDA #{$id}: " . $e->getMessage(),
                'errors' => [],
            ];
        }
    }

    /**
     * Update data di KEDUA endpoint sekaligus untuk data yang sama
     */
    public function updateBoth(int $surveiId, array $surveiData, int $pekerjaanId, array $pekerjaanData, array $photosAwal = [], array $photosAkhir = []): array
    {
        $surveiResult = $this->updateSurveiReses($surveiId, $surveiData, $photosAwal);
        $pekerjaanData['id_survei_reses'] = $surveiId;
        $pekerjaanResult = $this->updatePekerjaanSda($pekerjaanId, $pekerjaanData, $photosAkhir);

        $surveiOk = $surveiResult['success'] ?? false;
        $pekerjaanOk = $pekerjaanResult['success'] ?? false;

        if ($surveiOk && $pekerjaanOk) {
            return [
                'success' => true,
                'status' => 200,
                'message' => "Data berhasil diperbarui (UPDATE) di KEDUA modul: Survei Reses #{$surveiId} & Pekerjaan SDA #{$pekerjaanId}!",
                'survei_id' => $surveiId,
                'pekerjaan_id' => $pekerjaanId,
                'survei' => $surveiResult,
                'pekerjaan' => $pekerjaanResult,
            ];
        }

        if ($surveiOk || $pekerjaanOk) {
            $msg = 'Pembaruan parsial: ';
            $msg .= $surveiOk ? "Survei Reses #{$surveiId} Berhasil diupdate, " : "Survei Reses Gagal: " . ($surveiResult['message'] ?? '') . ", ";
            $msg .= $pekerjaanOk ? "Pekerjaan SDA #{$pekerjaanId} Berhasil diupdate." : "Pekerjaan SDA Gagal: " . ($pekerjaanResult['message'] ?? '');
            return [
                'success' => true,
                'partial' => true,
                'message' => $msg,
                'survei_id' => $surveiId,
                'pekerjaan_id' => $pekerjaanId,
                'survei' => $surveiResult,
                'pekerjaan' => $pekerjaanResult,
            ];
        }

        return [
            'success' => false,
            'status' => 500,
            'message' => 'Gagal memperbarui kedua modul. Survei: ' . ($surveiResult['message'] ?? '') . ' | Pekerjaan SDA: ' . ($pekerjaanResult['message'] ?? ''),
            'survei' => $surveiResult,
            'pekerjaan' => $pekerjaanResult,
        ];
    }

    /**
     * Ambil detail data Survei Reses (GET /api/survei-reses/{id})
     */
    public function getSurveiReses(int $id): ?array
    {
        try {
            $url = "{$this->baseUrl}/survei-reses/{$id}";
            $response = Http::withToken($this->token)->acceptJson()->get($url);
            if ($response->successful()) {
                $body = $response->json();
                return $body['data'] ?? $body;
            }
        } catch (\Throwable $e) {
            Log::error("AppResesApiService::getSurveiReses error: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Ambil detail data Pekerjaan SDA (GET /api/pekerjaan-sda/{id})
     */
    public function getPekerjaanSda(int $id): ?array
    {
        try {
            $url = "{$this->baseUrl}/pekerjaan-sda/{$id}";
            $response = Http::withToken($this->token)->acceptJson()->get($url);
            if ($response->successful()) {
                $body = $response->json();
                return $body['data'] ?? $body;
            }
        } catch (\Throwable $e) {
            Log::error("AppResesApiService::getPekerjaanSda error: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Parse string/JSON photo paths menjadi array URL lengkap
     */
    public static function parsePhotoUrls($raw, ?string $baseUrl = null): array
    {
        if (empty($raw)) return [];
        if ($baseUrl === null) {
            $apiUrl = config('services.app_reses.url', 'https://app-reses.farizahmad.com/api');
            $parsed = parse_url($apiUrl);
            $scheme = $parsed['scheme'] ?? 'https';
            $host = $parsed['host'] ?? 'app-reses.farizahmad.com';
            $port = !empty($parsed['port']) ? ':' . $parsed['port'] : '';
            $baseUrl = "{$scheme}://{$host}{$port}";
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $raw = $decoded;
            } else {
                $raw = [$raw];
            }
        }
        $urls = [];
        foreach ((array)$raw as $item) {
            if (!is_string($item) || trim($item) === '') continue;
            $clean = trim($item);
            if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://')) {
                $urls[] = $clean;
            } else {
                $urls[] = rtrim($baseUrl, '/') . '/storage/' . ltrim($clean, '/');
            }
        }
        return $urls;
    }

    /**
     * Update progress pada Pekerjaan SDA (PATCH /api/pekerjaan-sda/{id}/progress)
     */
    public function updatePekerjaanSdaProgress(int $pekerjaanId, array $data): array
    {
        try {
            $url = "{$this->baseUrl}/pekerjaan-sda/{$pekerjaanId}/progress";
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->patch($url, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'data' => $response->json(),
                    'message' => 'Progress Pekerjaan SDA berhasil diupdate.',
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'message' => $response->json('message') ?? 'Gagal update progress Pekerjaan SDA.',
                'errors' => $response->json('errors') ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('AppResesApiService::updatePekerjaanSdaProgress error: ' . $e->getMessage());
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Gagal menghubungi API Pekerjaan SDA: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Mengirimkan alur terintegrasi:
     * 1. Buat Survei Reses (yang otomatis membuat Pekerjaan SDA & Surat Permohonan)
     * 2. Jika ada data progress tambahan, update ke Pekerjaan SDA yang baru terbentuk
     */
    public function sendIntegrated(array $surveiData, array $progressData = [], array $photos = []): array
    {
        $surveiResult = $this->sendSurveiReses($surveiData, $photos);
        if (!$surveiResult['success']) {
            return $surveiResult;
        }

        $pekerjaanId = $surveiResult['data']['data']['pekerjaan_sda']['id'] 
            ?? $surveiResult['data']['pekerjaan_sda']['id'] 
            ?? null;

        // If pekerjaan_sda ID was created and there is progress data to update
        if ($pekerjaanId && !empty($progressData)) {
            $progressResult = $this->updatePekerjaanSdaProgress((int)$pekerjaanId, $progressData);
            return [
                'success' => true,
                'message' => 'Data Survei Reses & Pekerjaan SDA berhasil disimpan dan diperbarui!',
                'survei' => $surveiResult,
                'progress' => $progressResult,
                'pekerjaan_id' => $pekerjaanId,
            ];
        }

        return [
            'success' => true,
            'message' => 'Data Survei Reses & Pekerjaan SDA berhasil disimpan ke App-Reses!',
            'survei' => $surveiResult,
            'pekerjaan_id' => $pekerjaanId,
        ];
    }

    /**
     * Kirim data ke KEDUA endpoint sekaligus:
     * 1. POST /api/survei-reses (dengan $photosAwal)
     * 2. POST /api/pekerjaan-sda (dengan $photosAkhir)
     */
    public function sendBoth(array $surveiData, array $pekerjaanData, array $photosAwal = [], array $photosAkhir = []): array
    {
        $surveiResult = $this->sendSurveiReses($surveiData, $photosAwal);
        $surveiId = $surveiResult['data']['data']['survei_reses']['id'] ?? null;

        // Hubungkan Pekerjaan SDA dengan ID Survei Reses yang baru dibuat
        if ($surveiId) {
            $pekerjaanData['id_survei_reses'] = $surveiId;
        }

        $pekerjaanResult = $this->sendPekerjaanSda($pekerjaanData, $photosAkhir);

        $surveiOk = $surveiResult['success'] ?? false;
        $pekerjaanOk = $pekerjaanResult['success'] ?? false;

        $pekerjaanId = $pekerjaanResult['data']['data']['id'] ?? null;

        if ($surveiOk && $pekerjaanOk) {
            return [
                'success' => true,
                'status' => 201,
                'message' => 'Data berhasil disimpan ke KEDUA endpoint (Survei Reses & Pekerjaan SDA)!',
                'survei_id' => $surveiId,
                'pekerjaan_id' => $pekerjaanId,
                'survei' => $surveiResult,
                'pekerjaan' => $pekerjaanResult,
            ];
        }

        if ($surveiOk || $pekerjaanOk) {
            $msg = 'Pengiriman parsial: ';
            $msg .= $surveiOk ? 'Survei Reses Berhasil (#' . $surveiId . '), ' : 'Survei Reses Gagal: ' . ($surveiResult['message'] ?? '') . ', ';
            $msg .= $pekerjaanOk ? 'Pekerjaan SDA Berhasil (#' . $pekerjaanId . ').' : 'Pekerjaan SDA Gagal: ' . ($pekerjaanResult['message'] ?? '');
            return [
                'success' => true,
                'partial' => true,
                'message' => $msg,
                'survei_id' => $surveiId,
                'pekerjaan_id' => $pekerjaanId,
                'survei' => $surveiResult,
                'pekerjaan' => $pekerjaanResult,
            ];
        }

        return [
            'success' => false,
            'status' => 500,
            'message' => 'Gagal mengirim ke kedua endpoint. Survei: ' . ($surveiResult['message'] ?? '') . ' | Pekerjaan SDA: ' . ($pekerjaanResult['message'] ?? ''),
            'survei' => $surveiResult,
            'pekerjaan' => $pekerjaanResult,
        ];
    }

    /**
     * Geocoding alamat menggunakan MapTiler API
     * Mengembalikan ['latitude' => ..., 'longitude' => ..., 'place_name' => ...] atau null
     */
    public static function geocodeLocation(string $query): ?array
    {
        $key = config('services.maptiler.key', 'ZDiWx4r1ToT9jGdlNPco');
        if (empty($key) || empty(trim($query))) {
            return null;
        }

        try {
            // Proximity Jakarta Utara: 106.8841,-6.1214
            $url = 'https://api.maptiler.com/geocoding/' . urlencode(trim($query)) . '.json?key=' . $key . '&country=id&proximity=106.8841,-6.1214';
            $response = Http::timeout(8)->get($url);

            if ($response->successful()) {
                $features = $response->json('features') ?? [];
                if (!empty($features[0]['geometry']['coordinates'])) {
                    $coords = $features[0]['geometry']['coordinates']; // [lng, lat]
                    return [
                        'longitude' => (float)$coords[0],
                        'latitude' => (float)$coords[1],
                        'place_name' => $features[0]['place_name'] ?? '',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('AppResesApiService::geocodeLocation error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Ekstrak koordinat Latitude & Longitude dari link atau teks Google Maps
     * Mendukung format URL lengkap, shortlink (maps.app.goo.gl), desimal, atau DMS
     */
    public static function parseGoogleMapsCoordinates(string $input): ?array
    {
        $input = trim($input);
        if (empty($input)) return null;

        // Resolve shortlink jika menggunakan maps.app.goo.gl atau goo.gl/maps
        if (preg_match('/(?:maps\.app\.goo\.gl|goo\.gl\/maps)\/[A-Za-z0-9_\-]+/i', $input)) {
            try {
                $response = Http::timeout(6)->withUserAgent('Mozilla/5.0')->get($input);
                $effective = $response->effectiveUri();
                if ($effective) {
                    $input = (string)$effective;
                }
            } catch (\Throwable $e) {
                Log::warning('AppResesApiService::parseGoogleMapsCoordinates shortlink resolve error: ' . $e->getMessage());
            }
        }

        $decoded = urldecode($input);

        // 1. Format: !3d-6.106374!4d106.919958
        if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $decoded, $m)) {
            return ['lat' => (float)$m[1], 'lng' => (float)$m[2]];
        }

        // 2. Format: @-6.106374,106.9150871
        if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $decoded, $m)) {
            return ['lat' => (float)$m[1], 'lng' => (float)$m[2]];
        }

        // 3. Format: q=-6.106374,106.919958 atau ll=-6.106374,106.919958
        if (preg_match('/(?:q|ll|place)\/?:?(-?\d+\.\d+)[,\s]+(-?\d+\.\d+)/i', $decoded, $m)) {
            return ['lat' => (float)$m[1], 'lng' => (float)$m[2]];
        }

        // 4. Raw coordinate string: -6.106374, 106.919958
        if (preg_match('/^(-?\d+\.\d+)[,\s]+(-?\d+\.\d+)$/', $decoded, $m)) {
            return ['lat' => (float)$m[1], 'lng' => (float)$m[2]];
        }

        // 5. DMS Format: 6°06'23.0"S 106°55'11.9"E
        if (preg_match('/(\d+)[°\s]+(\d+)[\'\s]+([\d\.]+)["\s]*([NSns])[,\s]+(\d+)[°\s]+(\d+)[\'\s]+([\d\.]+)["\s]*([EWew])/', $decoded, $m)) {
            $lat = (float)$m[1] + ((float)$m[2] / 60) + ((float)$m[3] / 3600);
            if (strtoupper($m[4]) === 'S') $lat = -$lat;
            $lng = (float)$m[5] + ((float)$m[6] / 60) + ((float)$m[7] / 3600);
            if (strtoupper($m[8]) === 'W') $lng = -$lng;
            return ['lat' => round($lat, 6), 'lng' => round($lng, 6)];
        }

        return null;
    }
}


