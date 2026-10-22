<?php

use App\Models\Rab;
use App\Models\DeliveryOrder;
use App\Models\Kecamatan;
use App\Services\AppResesApiService;
use Livewire\WithFileUploads;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use function Livewire\Volt\{state, computed, layout, with, uses, mount};

layout('layouts.admin');
uses([WithFileUploads::class]);

state([
    'selectedKecamatanId' => '',
    'selectedRabId' => '',
    'selectedMonth' => date('n'),
    'selectedYear' => date('Y'),
    'showBastModal' => false,
    'bastPhotos' => [],
    'existingBastPhotos' => [],
    'bastTanggalMulai' => '',
    'bastTanggalSelesai' => '',

    // State untuk Modal & Pengiriman API App-Reses
    'showAppResesModal' => false,
    'appResesTargetEndpoint' => 'both', // 'both' (Gabungkan), 'survei-reses', atau 'pekerjaan-sda'
    'appResesRabId' => null,
    'appResesSurveiId' => null,
    'appResesPekerjaanId' => null,
    'appResesIsUpdate' => false,
    'appResesLokasi' => '',
    'appResesKecamatanId' => 1,
    'appResesKelurahanId' => 1,
    'appResesRt' => '',
    'appResesRw' => '',
    'appResesLatitude' => '',
    'appResesLongitude' => '',
    'appResesMapPlaceName' => '',
    'appResesGoogleMapsInput' => '',
    'appResesTanggal' => date('Y-m-d'),
    'appResesNoSkpd' => '',
    'appResesKodeTracking' => '',
    'appResesUraian' => '',
    'appResesKeterangan' => '',
    'appResesSumberData' => 'Masyarakat',
    'appResesStatusSurvei' => 'Selesai',
    'appResesStatusTindakLanjut' => 'Sedang Dikerjakan',
    'appResesProgress' => 85,
    'appResesKategoriPekerjaan' => 'Pemasangan Saluran Precast U-Ditch',
    'appResesLingkupKewenangan' => 'Seksi Pemeliharaan',
    'appResesMetodePekerjaan' => 'Swakelola',
    'appResesTahunDikerjakan' => date('Y'),
    'appResesIdPelaksana' => 2,
    'appResesPanjang' => '',
    'appResesLebar' => '',
    'appResesTinggi' => '',
    'appResesTglMulai' => '',
    'appResesTglSelesai' => '',
    'appResesPhotosAwal' => [],
    'appResesPhotosAkhir' => [],
    'appResesExistingPhotosAwal' => [],
    'appResesExistingPhotosAkhir' => [],
    'appResesSuccessMessage' => '',
    'appResesErrorMessage' => '',
    'appResesLastResponse' => null,
]);

mount(function() {
    $user = auth()->user();
    if ($user) {
        $isKecamatanUser = $user->hasRole('kecamatan_admin') || 
            ($user->kecamatan_id && !$user->hasAnyRole(['superadmin', 'sudin', 'pemel', 'seksi_pompa', 'pompa']));
        
        if ($isKecamatanUser && $user->kecamatan_id) {
            $this->selectedKecamatanId = (string) $user->kecamatan_id;
        } elseif ($user->hasRole('pemel')) {
            $pemelKec = Kecamatan::where('nama_kecamatan', 'PEMEL')->first();
            if ($pemelKec) {
                $this->selectedKecamatanId = (string) $pemelKec->id;
            }
        }
    }
});

$kecamatans = computed(function() {
    $user = auth()->user();
    if ($user && $user->hasRole('pemel')) {
        return Kecamatan::where('nama_kecamatan', 'PEMEL')->get();
    }
    return Kecamatan::orderBy('nama_kecamatan', 'asc')->get();
});

$rabs = computed(function() {
    $q = Rab::with('kecamatan')->orderBy('lokasi', 'asc');
    
    $user = auth()->user();
    $isPemelUser = $user && $user->hasRole('pemel');
    $isKecamatanUser = $user && (
        $user->hasRole('kecamatan_admin') || 
        ($user->kecamatan_id && !$user->hasAnyRole(['superadmin', 'sudin', 'pemel', 'seksi_pompa', 'pompa']))
    );

    $pemelKec = Kecamatan::where('nama_kecamatan', 'PEMEL')->first();
    $isFilteringPemel = $pemelKec && (string)$this->selectedKecamatanId === (string)$pemelKec->id;

    if ($isPemelUser) {
        // Ketika akun yang login adalah PEMEL, data yang ditampilkan HANYA data yang berstatus PEMEL saja (termasuk saat memilih '-- Semua Kecamatan --')
        $q->where(function($query) use ($pemelKec) {
            if ($pemelKec) {
                $query->where('kecamatan_id', $pemelKec->id);
            }
            $query->orWhere('lokasi', 'like', '%(PML)%')
                  ->orWhere('lokasi', 'like', '%(PEMEL)%')
                  ->orWhere('lokasi', 'like', '%PML%')
                  ->orWhere('lokasi', 'like', '%PEMEL%')
                  ->orWhere('lokasi', 'like', '%PEMELIHARAAN%');
        });
    } elseif ($isKecamatanUser) {
        if ($user->kecamatan_id) {
            $q->where('kecamatan_id', $user->kecamatan_id);
        } else {
            $q->where('id', '<', 0);
        }
    } else {
        if ($this->selectedKecamatanId !== '') {
            if ($this->selectedKecamatanId === 'null') {
                $q->whereNull('kecamatan_id');
            } elseif ($isFilteringPemel) {
                $q->where(function($query) use ($pemelKec) {
                    if ($pemelKec) {
                        $query->where('kecamatan_id', $pemelKec->id);
                    }
                    $query->orWhere('lokasi', 'like', '%(PML)%')
                          ->orWhere('lokasi', 'like', '%(PEMEL)%')
                          ->orWhere('lokasi', 'like', '%PML%')
                          ->orWhere('lokasi', 'like', '%PEMEL%')
                          ->orWhere('lokasi', 'like', '%PEMELIHARAAN%');
                });
            } else {
                $q->where('kecamatan_id', $this->selectedKecamatanId);
            }
        }
    }
    
    $allRabs = $q->get();

    // Index uploaded BAST photos from storage
    $allBastFiles = Storage::disk('public')->files('bast_photos');
    $rabPhotoCounts = [];
    foreach ($allBastFiles as $file) {
        $filename = basename($file);
        if (preg_match('/^bast_(\d+)_/', $filename, $matches)) {
            $rId = (int)$matches[1];
            $rabPhotoCounts[$rId] = ($rabPhotoCounts[$rId] ?? 0) + 1;
        }
    }

    foreach ($allRabs as $rab) {
        $photoCount = $rabPhotoCounts[$rab->id] ?? 0;
        $rab->bast_count = $photoCount;
        $rab->has_upload = !empty($rab->document_photo) || $photoCount > 0;
    }

    return $allRabs;
});

$updatedSelectedKecamatanId = function () {
    $this->selectedRabId = '';
};

with(fn() => [
    'months' => [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ]
]);

$years = computed(function() {
    $currentYear = date('Y');
    return range($currentYear - 2, $currentYear + 1);
});

$reportData = computed(function() {
    if (!$this->selectedRabId) return null;
    
    $rab = Rab::with('materials')->find($this->selectedRabId);
    if (!$rab) return null;
    
    // Check BAST photos count for selected RAB
    $allBastFiles = Storage::disk('public')->files('bast_photos');
    $bastCount = 0;
    foreach ($allBastFiles as $file) {
        if (str_starts_with(basename($file), 'bast_' . $rab->id . '_')) {
            $bastCount++;
        }
    }
    $rab->bast_count = $bastCount;
    $rab->has_upload = !empty($rab->document_photo) || $bastCount > 0;
    
    // Get delivery orders for this lokasi, month, and year. We exclude cancelled if you have such status, usually draft and shipped are what exists.
    $dos = DeliveryOrder::where('lokasi', $rab->lokasi)
        ->whereMonth('tanggal', $this->selectedMonth)
        ->whereYear('tanggal', $this->selectedYear)
        ->with('materials')
        ->get();

    $currentDate = \Carbon\Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1);
    $prevDos = DeliveryOrder::where('lokasi', $rab->lokasi)
        ->whereDate('tanggal', '<', $currentDate->format('Y-m-d'))
        ->with('materials')
        ->get();
        
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $this->selectedMonth, $this->selectedYear);
    
    $data = [];
    foreach ($rab->materials as $material) {
        $data[$material->id] = [
            'material' => $material->name,
            'unit' => $material->unit,
            'target' => (float)$material->pivot->target_volume,
            'lalu' => 0,
            'daily' => array_fill(1, $daysInMonth, 0),
            'total' => 0,
            'sisa' => 0
        ];
    }

    foreach ($prevDos as $do) {
        foreach ($do->materials as $m) {
            if (!isset($data[$m->id])) {
                $data[$m->id] = [
                    'material' => $m->name,
                    'unit' => $m->unit,
                    'target' => 0,
                    'lalu' => 0,
                    'daily' => array_fill(1, $daysInMonth, 0),
                    'total' => 0,
                    'sisa' => 0
                ];
            }
            $data[$m->id]['lalu'] += (float)$m->pivot->requested_volume;
        }
    }
    
    foreach ($dos as $do) {
        $day = (int)date('j', strtotime($do->tanggal));
        foreach ($do->materials as $m) {
            if (!isset($data[$m->id])) {
                $data[$m->id] = [
                    'material' => $m->name,
                    'unit' => $m->unit,
                    'target' => 0,
                    'lalu' => 0,
                    'daily' => array_fill(1, $daysInMonth, 0),
                    'total' => 0,
                    'sisa' => 0
                ];
            }
            $vol = (float)$m->pivot->requested_volume;
            $data[$m->id]['daily'][$day] += $vol;
            $data[$m->id]['total'] += $vol;
        }
    }
    
    foreach ($data as $id => &$row) {
        $row['target'] = $row['target'] - $row['lalu'];
        $row['sisa'] = $row['target'] - $row['total'];
    }
    
    // Ambil semua Surat Jalan untuk riwayat pengeluaran
    $allDosForLocation = DeliveryOrder::where('lokasi', $rab->lokasi)
        ->with('materials')
        ->orderBy('tanggal', 'desc')
        ->get();
    
    return [
        'rab' => $rab,
        'rows' => $data,
        'daysInMonth' => $daysInMonth,
        'history_dos' => $allDosForLocation
    ];
});

$exportExcel = function() {
    $report = $this->reportData;
    if (!$report) {
        session()->flash('error', 'Tidak ada data untuk diekspor.');
        return;
    }

    $fileName = 'Laporan-RAB-' . str_replace(' ', '-', $report['rab']->lokasi) . '-' . $this->selectedMonth . '-' . $this->selectedYear . '.xlsx';

    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\RabExport($report['rab'], $report['rows'], $this->selectedMonth, $this->selectedYear, $report['daysInMonth']),
        $fileName
    );
};

$openBastModal = function() {
    $this->bastPhotos = [];
    $this->resetErrorBag();

    $rabId = $this->selectedRabId;
    if ($rabId) {
        $allFiles = Storage::disk('public')->files('bast_photos');
        $this->existingBastPhotos = array_values(array_filter($allFiles, function($file) use ($rabId) {
            return str_starts_with(basename($file), 'bast_' . $rabId . '_');
        }));

        $rab = Rab::find($rabId);
        if ($rab) {
            $tglMulaiDate = $rab->created_at ? Carbon::parse($rab->created_at)->format('Y-m-d') : date('Y-m-d');
            $latestMaterial = \Illuminate\Support\Facades\DB::table('material_rab')
                ->where('rab_id', $rab->id)
                ->orderBy('created_at', 'desc')
                ->first();
            $tglSelesaiDate = ($latestMaterial && $latestMaterial->created_at)
                ? Carbon::parse($latestMaterial->created_at)->format('Y-m-d')
                : $tglMulaiDate;

            $this->bastTanggalMulai = $tglMulaiDate;
            $this->bastTanggalSelesai = $tglSelesaiDate;
        }
    } else {
        $this->existingBastPhotos = [];
        $this->bastTanggalMulai = date('Y-m-d');
        $this->bastTanggalSelesai = date('Y-m-d');
    }

    $this->showBastModal = true;
};

$openBastModalFor = function($targetRabId) {
    $this->selectedRabId = (string)$targetRabId;
    $this->openBastModal();
};

$closeBastModal = function() {
    $this->showBastModal = false;
    $this->bastPhotos = [];
    $this->existingBastPhotos = [];
    $this->resetErrorBag();
};

$removeBastPhoto = function($index) {
    if (isset($this->bastPhotos[$index])) {
        unset($this->bastPhotos[$index]);
        $this->bastPhotos = array_values($this->bastPhotos);
    }
};

$deleteExistingBastPhoto = function($path) {
    if (Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
    }
    $rabId = $this->selectedRabId;
    if ($rabId) {
        $allFiles = Storage::disk('public')->files('bast_photos');
        $this->existingBastPhotos = array_values(array_filter($allFiles, function($file) use ($rabId) {
            return str_starts_with(basename($file), 'bast_' . $rabId . '_');
        }));
    } else {
        $this->existingBastPhotos = [];
    }
};

$downloadBast = function() {
    $existingCount = count($this->existingBastPhotos);
    $totalCount = $existingCount + count($this->bastPhotos);

    if ($totalCount < 3) {
        $this->validate([
            'bastPhotos' => 'required|array|min:' . (3 - $existingCount),
            'bastPhotos.*' => 'image|max:10240',
        ], [
            'bastPhotos.required' => 'Wajib memiliki minimal 3 foto dokumentasi. Saat ini baru ada ' . $existingCount . ' foto.',
            'bastPhotos.min' => 'Wajib memiliki total minimal 3 foto dokumentasi pekerjaan (saat ini baru ' . $totalCount . ' foto).',
            'bastPhotos.*.image' => 'Semua file harus berupa format gambar (JPG, PNG, JPEG, WEBP).',
            'bastPhotos.*.max' => 'Ukuran setiap gambar maksimal 10MB.',
        ]);
    } else {
        if (!empty($this->bastPhotos)) {
            $this->validate([
                'bastPhotos.*' => 'image|max:10240',
            ], [
                'bastPhotos.*.image' => 'Semua file harus berupa format gambar (JPG, PNG, JPEG, WEBP).',
                'bastPhotos.*.max' => 'Ukuran setiap gambar maksimal 10MB.',
            ]);
        }
    }

    $rab = Rab::find($this->selectedRabId);
    if (!$rab) {
        session()->flash('error', 'Data RAB tidak ditemukan.');
        return;
    }

    if (!empty($this->bastPhotos)) {
        // Simpan foto bukti BAST ke storage public (dikonversi ke format webp kualitas 80%)
        $manager = new ImageManager(new Driver());
        foreach ($this->bastPhotos as $photo) {
            $image = $manager->read($photo->getRealPath());
            $webp = $image->toWebp(80);
            $fileName = 'bast_' . $rab->id . '_' . uniqid() . '.webp';
            Storage::disk('public')->put('bast_photos/' . $fileName, (string) $webp);
        }
    }

    $controller = new \App\Http\Controllers\RabController();
    $response = $controller->downloadBast($rab, $this->bastTanggalMulai, $this->bastTanggalSelesai);

    $this->showBastModal = false;
    $this->bastPhotos = [];
    $this->existingBastPhotos = [];

    return $response;
};

$appResesKecamatans = computed(fn() => AppResesApiService::getKecamatans());
$appResesKelurahans = computed(fn() => AppResesApiService::getKelurahans((int)$this->appResesKecamatanId));
$appResesPelaksanas = computed(fn() => AppResesApiService::getPelaksanas());
$appResesKategoriList = computed(fn() => AppResesApiService::getKategoriPekerjaans());
$appResesLingkupList = computed(fn() => AppResesApiService::getLingkupKewenangans());
$appResesMetodeList = computed(fn() => AppResesApiService::getMetodePekerjaans());

$updatedAppResesKecamatanId = function ($val) {
    $kelList = AppResesApiService::getKelurahans((int)$val);
    $this->appResesKelurahanId = !empty($kelList) ? array_key_first($kelList) : 1;
};

$fetchExistingAppResesData = function ($surveiId = null, $pekerjaanId = null) {
    $surveiId = $surveiId !== null ? (int)$surveiId : (int)$this->appResesSurveiId;
    $pekerjaanId = $pekerjaanId !== null ? (int)$pekerjaanId : (int)$this->appResesPekerjaanId;

    if ($surveiId <= 0 && $pekerjaanId <= 0) return;

    $apiService = new AppResesApiService();

    // 1. Ambil data dari Survei Reses (GET /api/survei-reses/{id})
    if ($surveiId > 0) {
        $survei = $apiService->getSurveiReses($surveiId);
        if ($survei) {
            $s = $survei['survei_reses'] ?? $survei;
            if (isset($s['panjang']) && $s['panjang'] !== null && $s['panjang'] !== '') {
                $this->appResesPanjang = (string)(float)$s['panjang'];
            }
            if (isset($s['lebar']) && $s['lebar'] !== null && $s['lebar'] !== '') {
                $this->appResesLebar = (string)(float)$s['lebar'];
            }
            if (isset($s['tinggi']) && $s['tinggi'] !== null && $s['tinggi'] !== '') {
                $this->appResesTinggi = (string)(float)$s['tinggi'];
            }
            if (!empty($s['status'])) {
                $this->appResesStatusSurvei = $s['status'];
            }
            if (!empty($s['alamat'])) {
                $this->appResesLokasi = $s['alamat'];
            }
            if (!empty($s['permintaan'])) {
                $this->appResesUraian = $s['permintaan'];
            }
            if (!empty($s['keterangan'])) {
                $this->appResesKeterangan = $s['keterangan'];
            }
            if (!empty($s['id_kecamatan'])) {
                $this->appResesKecamatanId = (int)$s['id_kecamatan'];
            }
            if (!empty($s['id_kelurahan'])) {
                $this->appResesKelurahanId = (int)$s['id_kelurahan'];
            }
            if (!empty($s['tanggal_reses'])) {
                $this->appResesTanggal = date('Y-m-d', strtotime($s['tanggal_reses']));
            }

            // Foto Awal dari Survei Reses yang sudah tersimpan di server
            $this->appResesExistingPhotosAwal = AppResesApiService::parsePhotoUrls($s['foto'] ?? []);

            // Jika survei punya relasi pekerjaan_sda dan pekerjaanId belum diset
            if ($pekerjaanId <= 0 && !empty($s['pekerjaan_sda']['id'])) {
                $pekerjaanId = (int)$s['pekerjaan_sda']['id'];
                $this->appResesPekerjaanId = $pekerjaanId;
            }
        }
    }

    // 2. Ambil data dari Pekerjaan SDA (GET /api/pekerjaan-sda/{id})
    if ($pekerjaanId > 0) {
        $pekerjaan = $apiService->getPekerjaanSda($pekerjaanId);
        if ($pekerjaan) {
            $p = $pekerjaan['pekerjaan_sda'] ?? $pekerjaan;
            if (isset($p['progress'])) {
                $this->appResesProgress = (int)$p['progress'];
            }
            if (!empty($p['status_tindak_lanjut'])) {
                $this->appResesStatusTindakLanjut = $p['status_tindak_lanjut'];
            }
            if (!empty($p['tgl_mulai'])) {
                $this->appResesTglMulai = date('Y-m-d', strtotime($p['tgl_mulai']));
            }
            if (!empty($p['tgl_selesai'])) {
                $this->appResesTglSelesai = date('Y-m-d', strtotime($p['tgl_selesai']));
            }
            if (!empty($p['kategori_pekerjaan'])) {
                $this->appResesKategoriPekerjaan = $p['kategori_pekerjaan'];
            }
            if (!empty($p['lingkup_kewenangan'])) {
                $this->appResesLingkupKewenangan = $p['lingkup_kewenangan'];
            }
            if (!empty($p['metode_pekerjaan'])) {
                $this->appResesMetodePekerjaan = $p['metode_pekerjaan'];
            }
            if (!empty($p['tahun_dikerjakan'])) {
                $this->appResesTahunDikerjakan = (int)$p['tahun_dikerjakan'];
            }
            if (!empty($p['id_pelaksana'])) {
                $this->appResesIdPelaksana = (int)$p['id_pelaksana'];
            }
            if (!empty($p['no_skpd'])) {
                $this->appResesNoSkpd = $p['no_skpd'];
            }
            if (!empty($p['kode_tracking'])) {
                $this->appResesKodeTracking = $p['kode_tracking'];
            }
            if (!empty($p['alamat']) && empty($this->appResesLokasi)) {
                $this->appResesLokasi = $p['alamat'];
            }
            if (!empty($p['deskripsi']) && empty($this->appResesUraian)) {
                $this->appResesUraian = $p['deskripsi'];
            }
            if (isset($p['rt'])) {
                $this->appResesRt = (string)($p['rt'] ?? '');
            }
            if (isset($p['rw'])) {
                $this->appResesRw = (string)($p['rw'] ?? '');
            }
            if (!empty($p['latitude'])) {
                $this->appResesLatitude = (string)$p['latitude'];
            }
            if (!empty($p['longitude'])) {
                $this->appResesLongitude = (string)$p['longitude'];
            }

            // Foto Akhir dari Pekerjaan SDA yang sudah tersimpan di server
            $this->appResesExistingPhotosAkhir = AppResesApiService::parsePhotoUrls($p['photo'] ?? []);
        }
    }
};

$autoGeocodeLocation = function () {
    $kels = AppResesApiService::getKelurahans((int)$this->appResesKecamatanId);
    $kecs = AppResesApiService::getKecamatans();
    $kelurahanName = $kels[(int)$this->appResesKelurahanId] ?? '';
    $kecamatanName = $kecs[(int)$this->appResesKecamatanId] ?? 'Cilincing';
    
    // 1. Bersihkan teks lokasi dari kata RT/RW agar MapTiler tidak terkecoh ke polygon RT kelurahan lain
    $cleanLokasi = $this->appResesLokasi;
    $cleanLokasi = preg_replace('/\brt\s*[:\.\/]?\s*\d+\b/i', '', $cleanLokasi);
    $cleanLokasi = preg_replace('/\brw\s*[:\.\/]?\s*\d+\b/i', '', $cleanLokasi);
    $cleanLokasi = trim(preg_replace('/\s+/', ' ', $cleanLokasi), ", \t\n\r\0\x0B");

    // Urutan pencarian berjenjang (dari paling spesifik sampai ke kelurahan/kecamatan):
    // 1. Alamat Bersih + Kelurahan + Kecamatan + Jakarta Utara
    $queryClean = trim($cleanLokasi . ', ' . ($kelurahanName ? $kelurahanName . ', ' : '') . $kecamatanName . ', Jakarta Utara', ', ');
    $geo = AppResesApiService::geocodeLocation($queryClean);

    // 2. Jika belum ketemu, coba teks lokasi lengkap + RT/RW + Kelurahan + Kecamatan
    if (!$geo && !empty($this->appResesLokasi)) {
        $parts = array_filter([
            $this->appResesLokasi,
            $this->appResesRt ? 'RT ' . $this->appResesRt : null,
            $this->appResesRw ? 'RW ' . $this->appResesRw : null,
            $kelurahanName,
            $kecamatanName,
            'Jakarta Utara'
        ]);
        $geo = AppResesApiService::geocodeLocation(implode(', ', $parts));
    }

    // 3. Fallback: Alamat Bersih + Kecamatan + Jakarta Utara
    if (!$geo && !empty($cleanLokasi)) {
        $geo = AppResesApiService::geocodeLocation($cleanLokasi . ', ' . $kecamatanName . ', Jakarta Utara');
    }

    // 4. Fallback: Kelurahan + Kecamatan + Jakarta Utara
    if (!$geo && !empty($kelurahanName)) {
        $geo = AppResesApiService::geocodeLocation($kelurahanName . ', ' . $kecamatanName . ', Jakarta Utara');
    }

    // 5. Fallback: Kecamatan + Jakarta Utara
    if (!$geo) {
        $geo = AppResesApiService::geocodeLocation($kecamatanName . ', Jakarta Utara');
    }

    if ($geo) {
        $this->appResesLatitude = (string)round($geo['latitude'], 6);
        $this->appResesLongitude = (string)round($geo['longitude'], 6);
        $this->appResesMapPlaceName = $geo['place_name'] ?? '';
        
        $this->dispatch('update-maptiler-coords', [
            'lat' => (float)$this->appResesLatitude,
            'lng' => (float)$this->appResesLongitude,
            'label' => $this->appResesLokasi ?: $this->appResesMapPlaceName,
        ]);
    }
};

$parseGoogleMapsInput = function () {
    if (empty(trim($this->appResesGoogleMapsInput))) return;

    $coords = AppResesApiService::parseGoogleMapsCoordinates($this->appResesGoogleMapsInput);
    if ($coords) {
        $this->appResesLatitude = (string)round($coords['lat'], 6);
        $this->appResesLongitude = (string)round($coords['lng'], 6);
        $this->appResesMapPlaceName = 'Google Maps: ' . $this->appResesLatitude . ', ' . $this->appResesLongitude;

        $this->dispatch('update-maptiler-coords', [
            'lat' => (float)$this->appResesLatitude,
            'lng' => (float)$this->appResesLongitude,
            'label' => $this->appResesLokasi ?: 'Titik Google Maps',
        ]);

        $this->appResesGoogleMapsInput = '';
        $this->resetErrorBag('appResesGoogleMapsInput');
    } else {
        $this->addError('appResesGoogleMapsInput', 'Format link atau koordinat Google Maps tidak valid / tidak ditemukan.');
    }
};

$syncFromApi = function () {
    $this->fetchExistingAppResesData($this->appResesSurveiId, $this->appResesPekerjaanId);
};

$openAppResesModalFor = function ($rabId) {
    $rab = Rab::with('kecamatan')->find($rabId);
    if (!$rab) return;

    $this->appResesRabId = $rab->id;
    $this->appResesSurveiId = $rab->app_reses_survei_id;
    $this->appResesPekerjaanId = $rab->app_reses_pekerjaan_id;
    $this->appResesIsUpdate = !empty($rab->app_reses_survei_id) || !empty($rab->app_reses_pekerjaan_id);
    $this->appResesLokasi = $rab->lokasi;
    
    // Auto-detect kecamatan & kelurahan dari teks lokasi
    $detected = AppResesApiService::autoDetectLocation($rab->lokasi, $rab->kecamatan_id);
    $this->appResesKecamatanId = $detected['id_kecamatan'];
    $this->appResesKelurahanId = $detected['id_kelurahan'];

    // Auto-detect RT & RW dari teks lokasi RAB
    $this->appResesRt = '';
    $this->appResesRw = '';
    if (preg_match('/rt\s*[:\.\/]?\s*(\d{1,3})/i', $rab->lokasi, $mRt)) {
        $this->appResesRt = str_pad($mRt[1], 2, '0', STR_PAD_LEFT);
    }
    if (preg_match('/rw\s*[:\.\/]?\s*(\d{1,3})/i', $rab->lokasi, $mRw)) {
        $this->appResesRw = str_pad($mRw[1], 2, '0', STR_PAD_LEFT);
    }

    $this->appResesTanggal = date('Y-m-d');
    $this->appResesTargetEndpoint = 'both'; // Default langsung gabungkan kedua endpoint!

    // Format otomatis No SKPD & Kode Tracking
    $tahun = date('Y');
    $this->appResesNoSkpd = $rab->app_reses_no_skpd ?: ('600/RAB-' . $rab->id . '/SDA/' . $tahun);
    $this->appResesKodeTracking = $rab->app_reses_kode_tracking ?: ('TRK-' . date('Ymd') . '-RAB' . $rab->id . '-' . strtoupper(substr(uniqid(), -4)));

    $this->appResesUraian = 'Pekerjaan perbaikan/pemeliharaan infrastruktur di ' . $rab->lokasi;
    $this->appResesKeterangan = 'Data diinput otomatis melalui Aplikasi Inventory SDA JU';
    $this->appResesSumberData = 'Inventory';
    $this->appResesStatusSurvei = 'Selesai';
    $this->appResesStatusTindakLanjut = 'Sedang Dikerjakan';
    $this->appResesProgress = 85;
    $this->appResesKategoriPekerjaan = 'Pemasangan Saluran Precast U-Ditch';
    $this->appResesLingkupKewenangan = 'Seksi Pemeliharaan';
    $this->appResesMetodePekerjaan = 'Swakelola';
    $this->appResesTahunDikerjakan = date('Y');
    $this->appResesIdPelaksana = 8;
    $this->appResesPanjang = '';
    $this->appResesLebar = '';
    $this->appResesTinggi = '';
    $this->appResesTglMulai = '';
    $this->appResesTglSelesai = '';
    $this->appResesPhotosAwal = [];
    $this->appResesPhotosAkhir = [];
    $this->appResesExistingPhotosAwal = [];
    $this->appResesExistingPhotosAkhir = [];
    $this->appResesSuccessMessage = '';
    $this->appResesErrorMessage = '';
    $this->appResesLastResponse = null;
    $this->appResesLatitude = '';
    $this->appResesLongitude = '';
    $this->appResesMapPlaceName = '';
    $this->resetErrorBag();

    // Jika data sudah pernah dibuat (memiliki ID), tarik data riil langsung dari API App-Reses!
    if ($this->appResesSurveiId || $this->appResesPekerjaanId) {
        $this->fetchExistingAppResesData($this->appResesSurveiId, $this->appResesPekerjaanId);
    }

    // Jika koordinat belum ada, deteksi otomatis via MapTiler Geocoding
    if (empty($this->appResesLatitude) || empty($this->appResesLongitude)) {
        $this->autoGeocodeLocation();
    } else {
        $this->dispatch('update-maptiler-coords', [
            'lat' => (float)$this->appResesLatitude,
            'lng' => (float)$this->appResesLongitude,
            'label' => $this->appResesLokasi,
        ]);
    }

    $this->showAppResesModal = true;
};

$regenerateTrackingCode = function () {
    $rabId = $this->appResesRabId ?: '0';
    $this->appResesKodeTracking = 'TRK-' . date('Ymd') . '-RAB' . $rabId . '-' . strtoupper(substr(uniqid(), -4));
};

$closeAppResesModal = function () {
    $this->showAppResesModal = false;
    $this->appResesSurveiId = null;
    $this->appResesPekerjaanId = null;
    $this->appResesIsUpdate = false;
    $this->appResesPhotosAwal = [];
    $this->appResesPhotosAkhir = [];
    $this->appResesSuccessMessage = '';
    $this->appResesErrorMessage = '';
    $this->appResesLastResponse = null;
    $this->resetErrorBag();
};

$removeAppResesPhotoAwal = function ($index) {
    if (isset($this->appResesPhotosAwal[$index])) {
        unset($this->appResesPhotosAwal[$index]);
        $this->appResesPhotosAwal = array_values($this->appResesPhotosAwal);
    }
};

$removeAppResesPhotoAkhir = function ($index) {
    if (isset($this->appResesPhotosAkhir[$index])) {
        unset($this->appResesPhotosAkhir[$index]);
        $this->appResesPhotosAkhir = array_values($this->appResesPhotosAkhir);
    }
};

$submitToAppReses = function () {
    $this->validate([
        'appResesLokasi' => 'required|string|min:3',
        'appResesKecamatanId' => 'required|integer',
        'appResesKelurahanId' => 'required|integer',
        'appResesTanggal' => 'required|date',
        'appResesUraian' => 'required|string|min:3',
        'appResesPhotosAwal.*' => 'image|max:10240',
        'appResesPhotosAkhir.*' => 'image|max:10240',
    ], [
        'appResesLokasi.required' => 'Lokasi / alamat pekerjaan wajib diisi.',
        'appResesUraian.required' => 'Deskripsi atau uraian permintaan wajib diisi.',
        'appResesPhotosAwal.*.image' => 'File foto awal harus berupa format gambar (JPG, PNG, JPEG, WEBP).',
        'appResesPhotosAwal.*.max' => 'Ukuran foto awal maksimal 10MB.',
        'appResesPhotosAkhir.*.image' => 'File foto akhir harus berupa format gambar (JPG, PNG, JPEG, WEBP).',
        'appResesPhotosAkhir.*.max' => 'Ukuran foto akhir maksimal 10MB.',
    ]);

    $apiService = new AppResesApiService();

    $cleanNum = function($val, bool $allowNegative = false) {
        if ($val === null || $val === '') return null;
        $pattern = $allowNegative ? '/[^0-9,\.\-]/' : '/[^0-9,\.]/';
        $str = str_replace(',', '.', preg_replace($pattern, '', (string)$val));
        return is_numeric($str) ? (float)$str : null;
    };

    $panjangNum = $cleanNum($this->appResesPanjang, false);
    $lebarNum   = $cleanNum($this->appResesLebar, false);
    $tinggiNum  = $cleanNum($this->appResesTinggi, false);

    $vol = null;
    if ($panjangNum !== null && $lebarNum !== null && $tinggiNum !== null) {
        $vol = round($panjangNum * $lebarNum * $tinggiNum, 3);
    }

    $tahun = date('Y', strtotime($this->appResesTanggal));
    $noSkpd = $this->appResesNoSkpd ?: ('600/RAB-' . $this->appResesRabId . '/SDA/' . $tahun);

    // Pastikan Kecamatan & Kelurahan valid
    $kecId = (int)($this->appResesKecamatanId ?: 1);
    if ($kecId < 1 || $kecId > 6) $kecId = 1;
    $kelList = AppResesApiService::getKelurahans($kecId);
    $kelId = (int)$this->appResesKelurahanId;
    if (!$kelId || !array_key_exists($kelId, $kelList)) {
        $kelId = !empty($kelList) ? array_key_first($kelList) : 1;
        $this->appResesKelurahanId = $kelId;
    }

    $surveiId = (int)$this->appResesSurveiId;
    $pekerjaanId = (int)$this->appResesPekerjaanId;

    // Untuk data baru, selalu buat kode_tracking yang baru dan unik
    if ($surveiId <= 0 && $pekerjaanId <= 0) {
        $kodeTracking = 'TRK-' . date('Ymd') . '-RAB' . $this->appResesRabId . '-' . strtoupper(substr(uniqid(), -4));
        $this->appResesKodeTracking = $kodeTracking;
    } else {
        $kodeTracking = $this->appResesKodeTracking ?: ('TRK-' . date('Ymd') . '-RAB' . $this->appResesRabId . '-' . strtoupper(substr(uniqid(), -4)));
    }

    $latNum = $cleanNum($this->appResesLatitude, true);
    $lngNum = $cleanNum($this->appResesLongitude, true);
    if ($latNum !== null && ($latNum < -90 || $latNum > 90)) $latNum = null;
    if ($lngNum !== null && ($lngNum < -180 || $lngNum > 180)) $lngNum = null;

    $idPelaksana = (int)($this->appResesIdPelaksana ?: 8);

    // Payload untuk Survei Reses (otomatis membuat Survei Reses & Surat Permohonan)
    $surveiPayload = [
        'sumber_data' => 'Inventory',
        'id_kecamatan' => $kecId,
        'id_kelurahan' => $kelId,
        'tanggal_reses' => $this->appResesTanggal,
        'alamat' => $this->appResesLokasi,
        'permintaan' => $this->appResesUraian,
        'keterangan' => $this->appResesKeterangan ?: 'Data terkirim dari Aplikasi Inventory',
        'status' => $this->appResesStatusSurvei ?: 'Selesai',
        'panjang' => $panjangNum,
        'lebar' => $lebarNum,
        'tinggi' => $tinggiNum,
        'volume' => $vol,
    ];

    // Payload untuk Pekerjaan SDA (dengan no_skpd & kode_tracking unik)
    $pekerjaanPayload = [
        'sumber_data' => 'Inventory',
        'id_kecamatan' => $kecId,
        'id_kelurahan' => $kelId,
        'rt' => $this->appResesRt ?: null,
        'rw' => $this->appResesRw ?: null,
        'alamat' => $this->appResesLokasi,
        'deskripsi' => $this->appResesUraian,
        'no_skpd' => $noSkpd,
        'kode_tracking' => $kodeTracking,
        'kategori_pekerjaan' => $this->appResesKategoriPekerjaan ?: 'Pemasangan Saluran Precast U-Ditch',
        'lingkup_kewenangan' => $this->appResesLingkupKewenangan ?: 'Seksi Pemeliharaan',
        'metode_pekerjaan' => $this->appResesMetodePekerjaan ?: 'Swakelola',
        'tahun_dikerjakan' => (int)($this->appResesTahunDikerjakan ?: date('Y')),
        'id_pelaksana' => $idPelaksana,
        'status_tindak_lanjut' => $this->appResesStatusTindakLanjut ?: 'Survei Lapangan',
        'progress' => (int)($this->appResesProgress ?: 0),
        'tgl_input' => $this->appResesTanggal,
        'tahun_monev' => (int)$tahun,
        'tgl_mulai' => $this->appResesTglMulai ?: null,
        'tgl_selesai' => $this->appResesTglSelesai ?: null,
        'volume_panjang' => $panjangNum ? $panjangNum . ' m' : null,
        'latitude' => $latNum,
        'longitude' => $lngNum,
    ];

    $surveiId = (int)$this->appResesSurveiId;
    $pekerjaanId = (int)$this->appResesPekerjaanId;

    if ($this->appResesTargetEndpoint === 'both') {
        if ($surveiId > 0 && $pekerjaanId > 0) {
            // Mode UPDATE: Perbarui data yang sama (tidak membuat data baru)
            $result = $apiService->updateBoth($surveiId, $surveiPayload, $pekerjaanId, $pekerjaanPayload, $this->appResesPhotosAwal, $this->appResesPhotosAkhir);
        } elseif ($surveiId > 0) {
            $result = $apiService->updateSurveiReses($surveiId, $surveiPayload, $this->appResesPhotosAwal);
        } elseif ($pekerjaanId > 0) {
            $result = $apiService->updatePekerjaanSda($pekerjaanId, $pekerjaanPayload, $this->appResesPhotosAkhir);
        } else {
            // Mode BARU: Buat data baru
            $result = $apiService->sendBoth($surveiPayload, $pekerjaanPayload, $this->appResesPhotosAwal, $this->appResesPhotosAkhir);
        }
    } elseif ($this->appResesTargetEndpoint === 'pekerjaan-sda') {
        if ($pekerjaanId > 0) {
            $result = $apiService->updatePekerjaanSda($pekerjaanId, $pekerjaanPayload, $this->appResesPhotosAkhir);
        } else {
            $result = $apiService->sendPekerjaanSda($pekerjaanPayload, $this->appResesPhotosAkhir);
        }
    } else {
        if ($surveiId > 0) {
            $result = $apiService->updateSurveiReses($surveiId, $surveiPayload, $this->appResesPhotosAwal);
        } else {
            $progressPayload = [];
            if ($this->appResesProgress > 0 || $this->appResesStatusTindakLanjut || $this->appResesTglMulai) {
                $progressPayload = [
                    'progress' => (int)($this->appResesProgress ?: 0),
                    'status_tindak_lanjut' => $this->appResesStatusTindakLanjut ?: 'Survei Lapangan',
                    'tgl_mulai' => $this->appResesTglMulai ?: null,
                    'tgl_selesai' => $this->appResesTglSelesai ?: null,
                    'kode_tracking' => $kodeTracking,
                ];
            }
            $result = $apiService->sendIntegrated($surveiPayload, $progressPayload, $this->appResesPhotosAwal);
        }
    }

    if ($result['success']) {
        $this->appResesSuccessMessage = $result['message'];
        $this->appResesErrorMessage = '';
        $this->appResesLastResponse = $result;
        $this->appResesPhotosAwal = [];
        $this->appResesPhotosAkhir = [];

        // Simpan / sinkronkan ID App-Reses ke tabel rabs agar selanjutnya otomatis UPDATE
        $rab = Rab::find($this->appResesRabId);
        if ($rab) {
            $newSurveiId = $result['survei_id'] ?? $surveiId;
            $newPekerjaanId = $result['pekerjaan_id'] ?? $pekerjaanId;

            $rab->update([
                'app_reses_survei_id' => $newSurveiId ?: $rab->app_reses_survei_id,
                'app_reses_pekerjaan_id' => $newPekerjaanId ?: $rab->app_reses_pekerjaan_id,
                'app_reses_no_skpd' => $noSkpd,
                'app_reses_kode_tracking' => $kodeTracking,
            ]);

            $this->appResesSurveiId = $rab->app_reses_survei_id;
            $this->appResesPekerjaanId = $rab->app_reses_pekerjaan_id;
            $this->appResesIsUpdate = true;
        }
    } else {
        $msg = $result['message'] ?? 'Terjadi kesalahan saat mengirim data ke API App-Reses.';
        if (!empty($result['errors'])) {
            $errList = [];
            foreach ($result['errors'] as $field => $errs) {
                $errList[] = (is_array($errs) ? implode(', ', $errs) : $errs);
            }
            $msg .= ' Detail: ' . implode(' | ', $errList);
        }
        $this->appResesErrorMessage = $msg;
        $this->appResesSuccessMessage = '';
    }
};

?>

<div class="max-w-[100vw] overflow-hidden flex flex-col h-full">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan RAB</h1>
            <p class="text-sm text-gray-500">Rekap pengambilan material harian berbanding Stok RAB.</p>
        </div>
        
        <div class="flex flex-col md:flex-row md:flex-wrap items-center gap-3 bg-white p-2 rounded-2xl shadow-sm border border-gray-100">
            @php
                $user = auth()->user();
                $isKecamatanUser = $user && (
                    $user->hasRole('kecamatan_admin') || 
                    ($user->kecamatan_id && !$user->hasAnyRole(['superadmin', 'sudin', 'pemel', 'seksi_pompa', 'pompa']))
                );
            @endphp

            @if(!$isKecamatanUser)
            <select wire:model.live="selectedKecamatanId" class="bg-gray-50 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 outline-none border-none focus:ring-2 focus:ring-accent/20">
                @if(!auth()->user()?->hasRole('pemel'))
                    <option value="">-- Semua Kecamatan --</option>
                    <option value="null">-- Nota Dinas --</option>
                @endif
                @foreach($this->kecamatans as $kec)
                    <option value="{{ $kec->id }}">{{ $kec->nama_kecamatan }}</option>
                @endforeach
            </select>
            @else
            <div class="bg-blue-50 text-blue-700 font-bold px-3 py-2 rounded-xl text-xs flex items-center gap-1.5 border border-blue-200">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>{{ $user->kecamatan->nama_kecamatan ?? 'Kecamatan Anda' }}</span>
            </div>
            @endif

            @php
                $rabOptions = [];
                foreach($this->rabs as $rab) {
                    $rabOptions[] = [
                        'id' => $rab->id, 
                        'label' => $rab->lokasi,
                        'bast_count' => (int)($rab->bast_count ?? 0),
                        'has_upload' => (bool)($rab->has_upload ?? false)
                    ];
                }
            @endphp
            <div wire:key="dropdown-rab-{{ $selectedKecamatanId }}" x-data="{
                    open: false,
                    search: '',
                    filterStatus: 'all',
                    selectedId: @entangle('selectedRabId').live,
                    options: {{ json_encode($rabOptions) }},
                    get filteredOptions() {
                        return this.options.filter(opt => {
                            const matchSearch = this.search === '' || opt.label.toLowerCase().includes(this.search.toLowerCase());
                            if (!matchSearch) return false;

                            if (this.filterStatus === 'uploaded') return opt.has_upload;
                            if (this.filterStatus === 'pending') return !opt.has_upload;
                            return true;
                        });
                    },
                    get selectedLabel() {
                        const selectedOpt = this.options.find(opt => opt.id == this.selectedId);
                        return selectedOpt ? selectedOpt.label : '-- Pilih Lokasi RAB --';
                    }
                }"
                class="relative w-full md:w-72"
                @click.away="open = false"
            >
                <div @click="open = !open"
                     class="w-full bg-gray-50 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 outline-none border-none focus:ring-2 focus:ring-accent/20 cursor-pointer flex justify-between items-center gap-2">
                    <span x-text="selectedLabel" class="truncate flex-1 text-left block"></span>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </div>

                <div x-show="open" 
                     x-transition.opacity
                     style="display: none;"
                     class="absolute z-50 w-full md:w-[440px] mt-1 bg-white border border-warm/60 rounded-xl shadow-xl max-h-72 overflow-y-auto left-0 md:left-auto">
                     
                     <div class="p-2 sticky top-0 bg-white border-b border-warm/30 shadow-sm z-10 space-y-2">
                         <input type="text" x-model="search" placeholder="Cari lokasi RAB..." 
                                class="w-full bg-gray-50 rounded-md px-3 py-1.5 text-xs border border-warm/30 focus:outline-none focus:ring-1 focus:ring-accent"
                                @click.stop>
                         <div class="flex items-center gap-1 text-[10px] font-bold">
                             <button type="button" @click.stop="filterStatus = 'all'" :class="filterStatus === 'all' ? 'bg-accent text-white font-black' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'" class="px-2 py-0.5 rounded-lg transition-colors flex-1 text-center">Semua</button>
                             <button type="button" @click.stop="filterStatus = 'uploaded'" :class="filterStatus === 'uploaded' ? 'bg-emerald-600 text-white font-black' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'" class="px-2 py-0.5 rounded-lg transition-colors flex-1 text-center">✓ Sudah Upload</button>
                             <button type="button" @click.stop="filterStatus = 'pending'" :class="filterStatus === 'pending' ? 'bg-gray-600 text-white font-black' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'" class="px-2 py-0.5 rounded-lg transition-colors flex-1 text-center">Belum Upload</button>
                         </div>
                     </div>

                     <ul class="py-1">
                         <template x-for="option in filteredOptions" :key="option.id">
                             <li @click="selectedId = option.id; open = false; search = ''"
                                 class="px-4 py-2.5 text-sm text-gray-700 hover:bg-accent/10 cursor-pointer transition-colors border-b border-gray-50 last:border-0 flex items-center justify-between gap-2">
                                 <span x-text="option.label" class="font-bold text-gray-800 truncate flex-1"></span>
                                 <template x-if="option.bast_count >= 3">
                                     <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-300 flex-shrink-0">✓ BAST (3)</span>
                                 </template>
                                 <template x-if="option.bast_count > 0 && option.bast_count < 3">
                                     <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 border border-amber-300 flex-shrink-0" x-text="'BAST (' + option.bast_count + '/3)'"></span>
                                 </template>
                                 <template x-if="option.bast_count === 0 && option.has_upload">
                                     <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 border border-blue-300 flex-shrink-0">✓ Ada File</span>
                                 </template>
                                 <template x-if="!option.has_upload">
                                     <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-400 border border-gray-200 flex-shrink-0">Belum Upload</span>
                                 </template>
                             </li>
                         </template>
                         <li x-show="filteredOptions.length === 0" class="px-4 py-2 text-sm text-gray-400 italic text-center">
                             Lokasi tidak ditemukan...
                         </li>
                     </ul>
                </div>
            </div>
            
            <select wire:model.live="selectedMonth" class="bg-gray-50 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 outline-none border-none focus:ring-2 focus:ring-accent/20">
                @foreach($months as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>
            
            <select wire:model.live="selectedYear" class="bg-gray-50 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 outline-none border-none focus:ring-2 focus:ring-accent/20">
                @foreach($this->years as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if(!$this->selectedRabId)
    <div class="space-y-6">
        @php
            $rabsList = $this->rabs;
            $totalRabsCount = count($rabsList);
            $bastLengkapCount = $rabsList->filter(fn($r) => ($r->bast_count ?? 0) >= 3)->count();
            $hasUploadCount = $rabsList->filter(fn($r) => $r->has_upload)->count();
            $belumUploadCount = $rabsList->filter(fn($r) => !$r->has_upload)->count();
        @endphp

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total RAB</p>
                    <p class="text-2xl font-black text-gray-800 mt-1">{{ $totalRabsCount }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 shadow-sm border border-emerald-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">BAST Lengkap (3)</p>
                    <p class="text-2xl font-black text-emerald-700 mt-1">{{ $bastLengkapCount }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 shadow-sm border border-blue-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Sudah Upload</p>
                    <p class="text-2xl font-black text-blue-700 mt-1">{{ $hasUploadCount }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Belum Upload</p>
                    <p class="text-2xl font-black text-gray-500 mt-1">{{ $belumUploadCount }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        <!-- Tabel Rekap Status Upload Semua RAB -->
        <div class="bg-white rounded-3xl shadow-card ring-1 ring-accent/5 overflow-hidden border border-gray-100">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between flex-wrap gap-4 bg-gray-50/50">
                <div>
                    <h3 class="text-lg font-black text-gray-800">Rekapitulasi Status Upload Lokasi RAB</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Daftar semua RAB beserta status dokumen dan foto BAST yang diunggah.</p>
                </div>
            </div>

            @if($rabsList->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-400">
                            <th class="px-6 py-3.5 w-12 text-center">NO</th>
                            <th class="px-6 py-3.5">LOKASI PEKERJAAN RAB</th>
                            <th class="px-6 py-3.5">KECAMATAN</th>
                            <th class="px-6 py-3.5 text-center">FOTO BAST</th>
                            <th class="px-6 py-3.5 text-center">STATUS UPLOAD</th>
                            <th class="px-6 py-3.5 text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-xs">
                        @foreach($rabsList as $idx => $rabItem)
                        <tr class="hover:bg-blue-50/30 transition-colors">
                            <td class="px-6 py-4 text-center font-bold text-gray-400">{{ $idx + 1 }}</td>
                            <td class="px-6 py-4 font-bold text-gray-900">{{ $rabItem->lokasi }}</td>
                            <td class="px-6 py-4 text-gray-600 font-semibold">
                                @if(preg_match('/\b(PML|PEMEL|PEMELIHARAAN)\b/i', $rabItem->lokasi) || ($rabItem->kecamatan && $rabItem->kecamatan->nama_kecamatan === 'PEMEL'))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-purple-100 text-purple-700">PEMEL</span>
                                @else
                                    {{ $rabItem->kecamatan->nama_kecamatan ?? ($rabItem->kecamatan_id ? 'Kecamatan ID: '.$rabItem->kecamatan_id : 'Nota Dinas') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-gray-700">
                                @if(($rabItem->bast_count ?? 0) > 0)
                                    <button type="button" wire:click="openBastModalFor('{{ $rabItem->id }}')" class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 px-2.5 py-1 rounded-full text-xs transition-colors cursor-pointer" title="Klik untuk lihat foto BAST">
                                        📸 {{ $rabItem->bast_count }} Foto
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-400 px-2.5 py-1 rounded-full text-xs">
                                        📸 0 Foto
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if(($rabItem->bast_count ?? 0) >= 3)
                                    <button type="button" wire:click="openBastModalFor('{{ $rabItem->id }}')" class="inline-flex items-center gap-1 font-black px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 text-[11px] hover:bg-emerald-200 transition-colors cursor-pointer" title="Klik untuk lihat foto BAST">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        ✓ BAST Lengkap (3)
                                    </button>
                                @elseif(($rabItem->bast_count ?? 0) > 0)
                                    <button type="button" wire:click="openBastModalFor('{{ $rabItem->id }}')" class="inline-flex items-center gap-1 font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-300 text-[11px] hover:bg-amber-200 transition-colors cursor-pointer" title="Klik untuk lihat foto BAST">
                                        BAST {{ $rabItem->bast_count }}/3 Foto
                                    </button>
                                @elseif($rabItem->has_upload)
                                    <span class="inline-flex items-center gap-1 font-bold px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 border border-blue-300 text-[11px]">
                                        ✓ Ada File
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 font-bold px-2.5 py-1 rounded-full bg-gray-100 text-gray-400 border border-gray-200 text-[11px]">
                                        Belum Upload
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2">
                                    @if($rabItem->app_reses_survei_id || $rabItem->app_reses_pekerjaan_id)
                                        <button type="button" wire:click="openAppResesModalFor('{{ $rabItem->id }}')" class="bg-amber-50 text-amber-800 hover:bg-amber-600 hover:text-white border border-amber-300 font-bold px-3 py-1.5 rounded-xl transition-all text-xs inline-flex items-center gap-1.5 shadow-sm group" title="Update data di API App-Reses (#{{ $rabItem->app_reses_survei_id ?: $rabItem->app_reses_pekerjaan_id }})">
                                            <svg class="w-3.5 h-3.5 text-amber-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span>Update App-Reses</span>
                                        </button>
                                    @else
                                        <button type="button" wire:click="openAppResesModalFor('{{ $rabItem->id }}')" class="bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white border border-indigo-200 font-bold px-3 py-1.5 rounded-xl transition-all text-xs inline-flex items-center gap-1.5 shadow-sm group" title="Kirim data RAB ini ke API App-Reses">
                                            <svg class="w-3.5 h-3.5 text-indigo-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                            </svg>
                                            <span>Kirim ke App-Reses</span>
                                        </button>
                                    @endif
                                    <button wire:click="$set('selectedRabId', '{{ $rabItem->id }}')" class="bg-blue-50 text-blue-600 font-bold px-3 py-1.5 rounded-xl hover:bg-blue-600 hover:text-white transition-colors text-xs inline-flex items-center gap-1">
                                        <span>Pilih Lokasi</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-12 text-center text-gray-400 italic">
                Tidak ada data RAB untuk kecamatan terpilih.
            </div>
            @endif
        </div>
    </div>
    @elseif($this->reportData && empty($this->reportData['rows']))
    <div class="bg-white rounded-3xl shadow-card ring-1 ring-accent/5 p-12 text-center flex flex-col items-center justify-center min-h-[400px]">
        <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Material Belum Diatur</h3>
        <p class="text-sm text-gray-500 mt-2 max-w-sm">Anda belum menambahkan pengaturan <strong>Stok Material RAB</strong> untuk lokasi ini. Silakan kelola di menu Data RAB terlebih dahulu.</p>
        <a href="/dashboard/rab" wire:navigate class="mt-4 text-xs font-bold bg-accent text-white px-4 py-2 rounded-xl hover:bg-accent-dark transition-colors">Kelola Stok RAB</a>
    </div>
    @else
    <div class="bg-white rounded-3xl shadow-card ring-1 ring-accent/5 flex-1 overflow-hidden flex flex-col">
        <div class="p-6 border-b border-warm/60 flex items-center justify-between bg-gray-50/50 flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h2 class="font-black text-gray-800 text-lg uppercase tracking-wider">LOKASI: {{ $this->reportData['rab']->lokasi }}</h2>
                    @if($this->reportData['rab']->app_reses_survei_id || $this->reportData['rab']->app_reses_pekerjaan_id)
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-black px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm" title="Data sudah tersinkron dengan API App-Reses">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            App-Reses: 
                            @if($this->reportData['rab']->app_reses_survei_id) Survei #{{ $this->reportData['rab']->app_reses_survei_id }} @endif
                            @if($this->reportData['rab']->app_reses_pekerjaan_id) | Pekerjaan #{{ $this->reportData['rab']->app_reses_pekerjaan_id }} @endif
                        </span>
                    @endif
                    @if(($this->reportData['rab']->bast_count ?? 0) >= 3)
                        <button type="button" wire:click="openBastModal" class="inline-flex items-center gap-1 text-[11px] font-black px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm hover:bg-emerald-200 transition-colors cursor-pointer" title="Klik untuk lihat / kelola foto BAST">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            Foto BAST Lengkap ({{ $this->reportData['rab']->bast_count }} Foto)
                        </button>
                    @elseif(($this->reportData['rab']->bast_count ?? 0) > 0)
                        <button type="button" wire:click="openBastModal" class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 border border-amber-300 shadow-sm hover:bg-amber-200 transition-colors cursor-pointer" title="Klik untuk lihat / kelola foto BAST">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Foto BAST {{ $this->reportData['rab']->bast_count }}/3
                        </button>
                    @else
                        <button type="button" wire:click="openBastModal" class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-gray-100 text-gray-500 border border-gray-200 hover:bg-gray-200 transition-colors cursor-pointer" title="Klik untuk unggah foto BAST">
                            Belum Ada Foto BAST
                        </button>
                    @endif
                </div>
                <p class="text-xs font-bold text-gray-500 mt-1">PERIODE: {{ strtoupper($months[$selectedMonth]) }} {{ $selectedYear }}</p>
            </div>
            <div class="flex gap-2 flex-wrap">
                @if($this->reportData['rab']->app_reses_survei_id || $this->reportData['rab']->app_reses_pekerjaan_id)
                    <button type="button" wire:click="openAppResesModalFor('{{ $this->reportData['rab']->id }}')" class="text-xs font-bold text-white bg-amber-600 border border-amber-700 px-4 py-2 rounded-xl hover:bg-amber-700 transition-colors flex items-center gap-2 shadow-sm" title="Perbarui data yang sudah ada di API App-Reses (Update)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Update App-Reses
                    </button>
                @else
                    <button type="button" wire:click="openAppResesModalFor('{{ $this->reportData['rab']->id }}')" class="text-xs font-bold text-white bg-indigo-600 border border-indigo-700 px-4 py-2 rounded-xl hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-sm" title="Kirim data RAB ini ke API App-Reses">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Kirim ke App-Reses
                    </button>
                @endif
                <button wire:click="openBastModal" class="text-xs font-bold text-white bg-blue-600 border border-blue-700 px-4 py-2 rounded-xl hover:bg-blue-700 transition-colors flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download BAST
                </button>
                <button wire:click="exportExcel" class="text-xs font-bold text-white bg-green-600 border border-green-700 px-4 py-2 rounded-xl hover:bg-green-700 transition-colors flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </button>
                <button onclick="window.print()" class="text-xs font-bold text-gray-600 bg-white border border-gray-200 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak Laporan
                </button>
            </div>
        </div>

        <div class="overflow-x-auto w-full">
            <style>
                @media print {
                    body * { visibility: hidden; }
                    .print-area, .print-area * { visibility: visible; }
                    .print-area { position: absolute; left: 0; top: 0; width: 100%; }
                    @page { size: landscape; margin: 1.5cm; }
                    .print-header { display: flex !important; }
                    table th, table td { color: black !important; border-color: #000 !important; }
                }
                .print-header { display: none; }
            </style>
            <div class="print-area inline-block min-w-full align-middle font-serif">
                
                <div class="print-header items-center border-b-[3px] border-black pb-2 mb-6 text-center">
                    <div class="w-[100px] pr-4">
                        <img src="{{ asset('assets/logo.png') }}" onerror="this.style.display='none'" class="w-full" alt="Logo">
                    </div>
                    <div class="flex-1 text-center text-black">
                        <h1 class="text-[11pt] font-bold leading-tight uppercase text-center">PEMERINTAH PROVINSI DAERAH KHUSUS IBUKOTA JAKARTA</h1>
                        <h2 class="text-[13pt] font-bold leading-tight uppercase text-center">DINAS SUMBER DAYA AIR</h2>
                        <h3 class="text-[11pt] font-bold leading-tight uppercase text-center">SUKU DINAS SUMBER DAYA AIR KOTA ADMINISTRASI JAKARTA UTARA</h3>
                    </div>
                </div>

                <div class="text-center mb-6 print-header flex-col">
                    <h1 class="text-[14pt] font-black underline tracking-widest uppercase text-black">LAPORAN MATRIKS RAB MATERIAL</h1>
                    <p class="font-bold text-black mt-1 text-[11pt]">LOKASI: {{ $this->reportData['rab']->lokasi }}</p>
                    <p class="text-black text-[10pt] mt-0.5">PERIODE: {{ strtoupper($months[$selectedMonth]) }} {{ $selectedYear }}</p>
                </div>

                <table class="min-w-full border-collapse border border-gray-300 text-[10px]">
                    <thead>
                        <tr class="bg-gray-100">
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center w-8">NO</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-left min-w-[150px]">MATERIAL</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center">SATUAN</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center">STOCK MATERIAL RAB</th>
                            <th colspan="{{ $this->reportData['daysInMonth'] }}" class="border border-gray-300 px-1 py-1 text-center bg-gray-200">TANGGAL ({{ strtoupper($months[$selectedMonth]) }})</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center min-w-[100px]">TOTAL PENGAMBILAN</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center min-w-[100px]">SISA PENGAMBILAN</th>
                        </tr>
                        <tr class="bg-gray-50">
                            @for($i = 1; $i <= $this->reportData['daysInMonth']; $i++)
                            <th class="border border-gray-300 px-1 py-1 text-center w-6">{{ $i }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @foreach($this->reportData['rows'] as $row)
                        <tr class="hover:bg-warm/30 transition-colors">
                            <td class="border border-gray-300 px-2 py-1 text-center">{{ $no++ }}</td>
                            <td class="border border-gray-300 px-2 py-1 font-bold">{{ $row['material'] }}</td>
                            <td class="border border-gray-300 px-2 py-1 text-center text-gray-500">{{ $row['unit'] }}</td>
                            <td class="border border-gray-300 px-2 py-1 text-right font-bold text-accent bg-accent/5">{{ number_format($row['target'], 2, ',', '.') }}</td>

                            @for($i = 1; $i <= $this->reportData['daysInMonth']; $i++)
                                <td class="border border-gray-300 px-1 py-1 text-center text-gray-600">{{ $row['daily'][$i] > 0 ? number_format($row['daily'][$i], 2, ',', '.') : '' }}</td>
                            @endfor

                            <td class="border border-gray-300 px-2 py-1 text-right font-bold">{{ number_format($row['total'], 2, ',', '.') }}</td>
                            
                            @if($row['sisa'] < 0)
                                <td class="border border-gray-300 px-2 py-1 text-right font-bold text-red-600 bg-red-50/50">
                                    {{ number_format($row['sisa'], 2, ',', '.') }}
                                </td>
                            @else
                                <td class="border border-gray-300 px-2 py-1 text-right font-bold">
                                    {{ number_format($row['sisa'], 2, ',', '.') }}
                                </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Delivery Order History Table --}}
                <div class="mt-12 mb-6">
                    <h3 class="text-[11pt] font-black text-gray-800 uppercase tracking-widest mb-4 border-b-2 border-warm/60 pb-2">Bukti Pengeluaran (Riwayat Surat Jalan)</h3>
                    @if(count($this->reportData['history_dos']) > 0)
                        <div class="bg-white rounded-[2.5rem] shadow-sm ring-1 ring-accent/5 overflow-hidden border border-gray-100">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-base/50">
                                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Tanggal</th>
                                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Jenis / Ref</th>
                                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Rincian Barang</th>
                                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Petugas</th>
                                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-center">Bukti Nota / Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($this->reportData['history_dos'] as $do)
                                            <tr class="hover:bg-base/30 transition-colors">
                                                <td class="px-8 py-5">
                                                    <span class="text-xs font-bold text-gray-700 block">{{ \Carbon\Carbon::parse($do->tanggal)->format('d/m/Y') }}</span>
                                                    <span class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($do->created_at)->format('H:i') }} WIB</span>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="flex flex-col">
                                                        <span class="text-[10px] font-black uppercase tracking-widest mb-1 text-red-500">
                                                            Keluar
                                                        </span>
                                                        <span class="text-sm font-black text-gray-800">{{ $do->surat_jalan_no }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="flex flex-col">
                                                        <span class="text-sm font-bold text-gray-800 mb-1">{{ count($do->materials) }} Item Barang:</span>
                                                        <ul class="list-disc list-inside text-xs text-gray-600 space-y-0.5">
                                                        @foreach($do->materials as $mat)
                                                            <li><span class="font-bold">{{ $mat->name }}</span> : <span class="text-red-500">{{ (float)$mat->pivot->requested_volume }} {{ $mat->unit }}</span></li>
                                                        @endforeach
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <span class="text-xs font-bold text-gray-700">{{ $do->petugas ?? 'System' }}</span>
                                                </td>
                                                <td class="px-6 py-5 text-center">
                                                    <div class="flex justify-center gap-2">
                                                        @if($do->nota_dinas_photo)
                                                            @php $firstND = explode(',', $do->nota_dinas_photo)[0]; @endphp
                                                            <a href="{{ Storage::url(trim($firstND)) }}" target="_blank" class="block hover:opacity-80 transition-opacity" title="Nota Dinas">
                                                                <img src="{{ Storage::url(trim($firstND)) }}" class="w-10 h-10 rounded-lg object-cover ring-2 ring-white shadow-sm">
                                                            </a>
                                                        @endif
                                                        @if($do->progress_photo)
                                                            @foreach(explode(',', $do->progress_photo) as $pp)
                                                            <a href="{{ Storage::url(trim($pp)) }}" target="_blank" class="block hover:opacity-80 transition-opacity" title="Progress Pekerjaan">
                                                                <img src="{{ Storage::url(trim($pp)) }}" class="w-10 h-10 rounded-lg object-cover ring-2 ring-accent/30 shadow-sm">
                                                            </a>
                                                            @endforeach
                                                        @endif
                                                        @if(!$do->nota_dinas_photo && !$do->progress_photo)
                                                            <span class="text-[10px] font-bold text-gray-300 italic uppercase">No Photo</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-50 rounded-2xl p-8 text-center border border-dashed border-gray-300">
                            <p class="text-sm font-semibold text-gray-400 italic">Belum ada data pengeluaran (Surat Jalan) untuk lokasi ini.</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
    @endif

    {{-- Modal Upload Foto & Download BAST --}}
    @if($showBastModal && $this->reportData)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" wire:click="closeBastModal"></div>
        <div class="relative bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl p-6 md:p-8 animate-fade-in-up max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Download Berita Acara (BAST)</h2>
                        <p class="text-xs text-gray-500">{{ $this->reportData['rab']->lokasi }}</p>
                    </div>
                </div>
                <button type="button" wire:click="closeBastModal" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-xl hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            @php
                $existingCount = count($existingBastPhotos);
                $newCount = count($bastPhotos);
                $totalCount = $existingCount + $newCount;
            @endphp

            @if($existingCount >= 3)
                <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-3.5 mb-4 text-xs text-emerald-800">
                    <div class="flex items-center gap-2 font-bold mb-1">
                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Dokumentasi Foto Sudah Lengkap ({{ $existingCount }} Foto)</span>
                    </div>
                    <p class="text-[11px] text-emerald-700 leading-relaxed ml-6">
                        Foto dokumentasi untuk RAB ini sudah pernah diunggah. Anda dapat <strong>langsung mengunduh berkas BAST</strong> tanpa perlu mengunggah file baru lagi.
                    </p>
                </div>
            @elseif($existingCount > 0)
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3.5 mb-4 text-xs text-amber-800">
                    <div class="flex items-center gap-2 font-bold mb-1">
                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Sudah Ada {{ $existingCount }} Foto Terunggah</span>
                    </div>
                    <p class="text-[11px] text-amber-700 leading-relaxed ml-6">
                        Silakan unggah minimal <strong>{{ 3 - $existingCount }} foto lagi</strong> agar dokumen BAST dapat diunduh.
                    </p>
                </div>
            @else
                <div class="bg-blue-50/70 border border-blue-100 rounded-2xl p-3.5 mb-4 text-xs text-blue-800">
                    <div class="flex items-center gap-2 font-bold mb-1">
                        <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Ketentuan Pengunduhan BAST:</span>
                    </div>
                    <p class="text-[11px] text-blue-700 leading-relaxed ml-6">
                        Wajib mengunggah minimal <strong>3 foto dokumentasi pekerjaan</strong> sebelum berkas Word Berita Acara Serah Terima dapat diunduh.
                    </p>
                </div>
            @endif

            {{-- Display Existing Uploaded Photos --}}
            @if(!empty($existingBastPhotos))
                <div class="mb-4 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-gray-700">Foto Terunggah di Server ({{ $existingCount }}):</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2.5 max-h-40 overflow-y-auto p-2 bg-emerald-50/40 rounded-2xl border border-emerald-100">
                        @foreach($existingBastPhotos as $idx => $filePath)
                        <div class="relative group rounded-xl overflow-hidden border border-emerald-200 aspect-square bg-gray-100">
                            <img src="{{ Storage::url($filePath) }}" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <a href="{{ Storage::url($filePath) }}" target="_blank" class="bg-white text-gray-800 rounded-full p-1.5 hover:bg-gray-100 shadow-lg transition-transform hover:scale-110" title="Lihat Foto Full">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <button type="button" wire:click="deleteExistingBastPhoto('{{ $filePath }}')" wire:confirm="Hapus foto dokumentasi ini dari server?" class="bg-red-500 text-white rounded-full p-1.5 hover:bg-red-600 shadow-lg transition-transform hover:scale-110" title="Hapus Foto dari Server">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <span class="absolute bottom-1 left-1 bg-emerald-700/80 text-white text-[9px] px-1.5 py-0.5 rounded font-bold">
                                File #{{ $idx + 1 }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form wire:submit="downloadBast" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Tanggal Mulai Pekerjaan</label>
                        <input type="date" wire:model="bastTanggalMulai" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Tanggal Selesai Pekerjaan</label>
                        <input type="date" wire:model="bastTanggalSelesai" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        {{ $existingCount >= 3 ? 'Tambah / Ganti Foto (Opsional)' : 'Unggah Foto Dokumentasi Pekerjaan' }} 
                        @if($existingCount < 3)
                            <span class="text-red-500">* (Butuh min. {{ 3 - $existingCount }} foto lagi)</span>
                        @endif
                    </label>
                    
                    <input type="file" wire:model="bastPhotos" multiple accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 border border-dashed border-gray-300 rounded-2xl p-3 transition-all cursor-pointer">
                    
                    <div wire:loading wire:target="bastPhotos" class="text-xs text-blue-600 font-bold mt-2 flex items-center gap-1.5">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Sedang mengunggah dan memproses foto...</span>
                    </div>

                    @error('bastPhotos') <p class="text-xs text-red-500 font-bold mt-1.5">{{ $message }}</p> @enderror
                    @error('bastPhotos.*') <p class="text-xs text-red-500 font-bold mt-1.5">{{ $message }}</p> @enderror
                </div>

                {{-- Thumbnail Previews of NEW uploads --}}
                @if (!empty($bastPhotos))
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-gray-600">Foto Baru Terpilih:</span>
                            <span class="font-bold {{ $totalCount >= 3 ? 'text-emerald-600' : 'text-amber-600' }}">
                                Total: {{ $totalCount }} / 3 Foto {{ $totalCount >= 3 ? '✓ (Lengkap)' : '(Kurang ' . (3 - $totalCount) . ' foto)' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-2.5 max-h-48 overflow-y-auto p-1 bg-gray-50 rounded-2xl border border-gray-100">
                            @foreach($bastPhotos as $index => $photo)
                            <div class="relative group rounded-xl overflow-hidden border border-gray-200 aspect-square bg-gray-200">
                                <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <button type="button" wire:click="removeBastPhoto({{ $index }})" class="bg-red-500 text-white rounded-full p-1.5 hover:bg-red-600 shadow-lg transition-transform hover:scale-110" title="Hapus Foto">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <span class="absolute bottom-1 left-1 bg-black/60 text-white text-[9px] px-1.5 py-0.5 rounded font-bold">
                                    Baru #{{ $index + 1 }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="pt-3 flex gap-3">
                    <button type="button" wire:click="closeBastModal" class="flex-1 bg-gray-100 text-gray-600 py-3 rounded-2xl font-bold text-xs hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            {{ $totalCount < 3 ? 'disabled' : '' }}
                            wire:loading.attr="disabled"
                            class="flex-1 bg-blue-600 text-white py-3 rounded-2xl font-bold text-xs shadow-lg shadow-blue-600/30 hover:bg-blue-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="downloadBast">Download BAST (.docx)</span>
                        <span wire:loading wire:target="downloadBast">Menyiapkan Dokumen...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal Kirim Data ke API App-Reses --}}
    @if($showAppResesModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" wire:click="closeAppResesModal"></div>
        
        <div class="relative bg-white w-full max-w-2xl rounded-3xl sm:rounded-[2.5rem] shadow-2xl p-5 sm:p-7 md:p-8 animate-fade-in-up max-h-[92vh] overflow-y-auto border border-indigo-100 flex flex-col gap-5">
            
            {{-- Header Modal --}}
            <div class="flex items-start justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white flex items-center justify-center shadow-md shadow-indigo-500/20 flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-base sm:text-lg font-black text-gray-900 tracking-tight">Kirim Data ke API App-Reses</h2>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                App-Reses Online
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">Sinkronisasi usulan & pekerjaan SDA langsung ke sistem App-Reses.</p>
                    </div>
                </div>
                <button type="button" wire:click="closeAppResesModal" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl hover:bg-gray-100 transition-colors flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>



            {{-- Success Feedback Banner --}}
            @if($appResesSuccessMessage)
                <div class="bg-emerald-50 border-2 border-emerald-300 rounded-2xl p-4 text-xs text-emerald-900 shadow-sm animate-fade-in-up">
                    <div class="flex items-center gap-2 font-black text-emerald-800 text-sm mb-1.5">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>Berhasil Diproses di App-Reses!</span>
                    </div>
                    <p class="text-xs text-emerald-800 mb-2">{{ $appResesSuccessMessage }}</p>

                    @php
                        $lastDataSurvei = $appResesLastResponse['survei']['data']['data'] ?? $appResesLastResponse['data']['data'] ?? null;
                        $lastDataPekerjaan = $appResesLastResponse['pekerjaan']['data']['data'] ?? $appResesLastResponse['data']['data'] ?? null;
                        $surveiIdShow = $appResesLastResponse['survei_id'] ?? $lastDataSurvei['survei_reses']['id'] ?? $appResesSurveiId;
                        $pekerjaanIdShow = $appResesLastResponse['pekerjaan_id'] ?? $lastDataPekerjaan['id'] ?? $lastDataSurvei['pekerjaan_sda']['id'] ?? $appResesPekerjaanId;
                        $permohonanIdShow = $lastDataSurvei['surat_permohonan']['id'] ?? null;
                    @endphp

                    <div class="bg-white/90 border border-emerald-200 rounded-xl p-3 text-[11px] grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2">
                        @if($surveiIdShow)
                            <div>
                                <span class="text-gray-400 block font-bold uppercase text-[9px]">ID Survei Reses</span>
                                <span class="font-black text-indigo-700 text-sm">#{{ $surveiIdShow }}</span>
                            </div>
                        @endif
                        @if($pekerjaanIdShow)
                            <div>
                                <span class="text-gray-400 block font-bold uppercase text-[9px]">ID Pekerjaan SDA</span>
                                <span class="font-black text-emerald-700 text-sm">#{{ $pekerjaanIdShow }}</span>
                            </div>
                        @endif
                        @if($permohonanIdShow)
                            <div>
                                <span class="text-gray-400 block font-bold uppercase text-[9px]">ID Permohonan</span>
                                <span class="font-black text-purple-700 text-sm">#{{ $permohonanIdShow }}</span>
                            </div>
                        @endif
                        <div>
                            <span class="text-gray-400 block font-bold uppercase text-[9px]">Kode Tracking</span>
                            <span class="font-black text-gray-800 text-xs font-mono truncate block">{{ $appResesKodeTracking }}</span>
                        </div>
                    </div>

                    <div class="mt-3 flex justify-end">
                        <button type="button" wire:click="closeAppResesModal" class="px-3 py-1.5 bg-emerald-600 text-white rounded-xl font-bold text-xs hover:bg-emerald-700 transition-colors">
                            Tutup Jendela
                        </button>
                    </div>
                </div>
            @endif

            {{-- Error Feedback Banner --}}
            @if($appResesErrorMessage)
                <div class="bg-rose-50 border border-rose-200 rounded-2xl p-3.5 text-xs text-rose-900 flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <strong class="font-bold text-rose-800 block mb-0.5">Pengiriman Gagal:</strong>
                        <p class="text-[11px] leading-relaxed">{{ $appResesErrorMessage }}</p>
                    </div>
                </div>
            @endif

            {{-- Form Input --}}
            <form wire:submit="submitToAppReses" class="space-y-4">

                {{-- ID Mapping Control (Mencegah Duplikasi) --}}
                <div class="bg-gray-50/80 p-3 rounded-2xl border border-gray-200">
                    <div class="flex items-center justify-between mb-2 flex-wrap gap-1">
                        <span class="text-[11px] font-black uppercase tracking-wider text-gray-700 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            Koneksi ID Record di App-Reses
                        </span>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="syncFromApi" wire:loading.attr="disabled" class="inline-flex items-center gap-1 text-[10px] bg-indigo-100/80 hover:bg-indigo-200 text-indigo-800 border border-indigo-300 px-2.5 py-0.5 rounded-lg font-extrabold transition-all shadow-sm cursor-pointer" title="Ambil data detail & foto dari API App-Reses">
                                <svg class="w-3 h-3 text-indigo-700 animate-spin" wire:loading wire:target="syncFromApi" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <svg class="w-3 h-3 text-indigo-700" wire:loading.remove wire:target="syncFromApi" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Tarik Data dari API</span>
                            </button>
                            <span class="text-[10px] text-gray-500 font-medium">Terhubung Otomatis</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 mb-1">
                                ID Survei Reses (cth: 550)
                            </label>
                            <input type="number" wire:model="appResesSurveiId" disabled readonly placeholder="Otomatis (Tersimpan)" class="w-full bg-gray-100 border border-gray-200 rounded-xl px-3 py-1.5 text-xs font-mono font-bold text-indigo-700 cursor-not-allowed select-none opacity-80">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 mb-1">
                                ID Pekerjaan SDA (cth: 626)
                            </label>
                            <input type="number" wire:model="appResesPekerjaanId" disabled readonly placeholder="Otomatis (Tersimpan)" class="w-full bg-gray-100 border border-gray-200 rounded-xl px-3 py-1.5 text-xs font-mono font-bold text-emerald-700 cursor-not-allowed select-none opacity-80">
                        </div>
                    </div>
                </div>
                
                {{-- Alamat / Lokasi --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-black text-gray-700">
                            Lokasi / Alamat Pekerjaan <span class="text-red-500">*</span>
                        </label>
                        <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded-md">Otomatis dari RAB</span>
                    </div>
                    <input type="text" wire:model.live.debounce.400ms="appResesLokasi" placeholder="Masukkan lokasi detail..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-800 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                    @error('appResesLokasi') <p class="text-[11px] text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Wilayah (Kecamatan, Kelurahan, RT, RW) --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Kecamatan (Jakut) <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="appResesKecamatanId" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                            @foreach($this->appResesKecamatans as $kId => $kName)
                                <option value="{{ $kId }}">{{ $kName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Kelurahan <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="appResesKelurahanId" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                            @foreach($this->appResesKelurahans as $kelId => $kelName)
                                <option value="{{ $kelId }}">{{ $kelName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-1 sm:col-span-1">
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            RT
                        </label>
                        <input type="text" wire:model.live.debounce.300ms="appResesRt" placeholder="cth: 05" maxlength="10" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                    <div class="col-span-1 sm:col-span-1">
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            RW
                        </label>
                        <input type="text" wire:model.live.debounce.300ms="appResesRw" placeholder="cth: 02" maxlength="10" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                </div>

                {{-- Peta Lokasi MapTiler & Titik Koordinat --}}
                <div class="bg-indigo-50/40 p-3.5 rounded-2xl border border-indigo-100 space-y-2.5" 
                     x-data="{
                        map: null,
                        marker: null,
                        lat: @entangle('appResesLatitude'),
                        lng: @entangle('appResesLongitude'),
                        maptilerKey: '{{ config('services.maptiler.key', 'ZDiWx4r1ToT9jGdlNPco') }}',
                        initMap() {
                            if (this.map) return;
                            const currentLat = parseFloat(this.lat) || -6.1214;
                            const currentLng = parseFloat(this.lng) || 106.8841;
                            const zoom = (this.lat && this.lng) ? 16 : 13;

                            this.map = L.map($refs.mapContainer).setView([currentLat, currentLng], zoom);

                            L.tileLayer('https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=' + this.maptilerKey, {
                                attribution: '&copy; <a href=\'https://www.maptiler.com/\' target=\'_blank\'>MapTiler</a> &copy; <a href=\'https://www.openstreetmap.org/copyright\' target=\'_blank\'>OpenStreetMap</a>',
                                maxZoom: 19
                            }).addTo(this.map);

                            if (this.lat && this.lng) {
                                this.setMarker(currentLat, currentLng);
                            }

                            this.map.on('click', (e) => {
                                this.updateCoords(e.latlng.lat, e.latlng.lng);
                            });

                            window.addEventListener('update-maptiler-coords', (event) => {
                                const data = event.detail ? (event.detail[0] || event.detail) : null;
                                if (data && data.lat && data.lng) {
                                    this.updateCoords(data.lat, data.lng, true);
                                }
                            });

                            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 400);
                        },
                        setMarker(lat, lng) {
                            if (this.marker) {
                                this.marker.setLatLng([lat, lng]);
                            } else {
                                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                                this.marker.on('dragend', (e) => {
                                    const pos = e.target.getLatLng();
                                    this.updateCoords(pos.lat, pos.lng, false);
                                });
                            }
                        },
                        updateCoords(newLat, newLng, pan = false) {
                            const fixedLat = parseFloat(Number(newLat).toFixed(6));
                            const fixedLng = parseFloat(Number(newLng).toFixed(6));
                            this.lat = fixedLat;
                            this.lng = fixedLng;
                            this.setMarker(fixedLat, fixedLng);
                            if (pan && this.map) {
                                this.map.setView([fixedLat, fixedLng], 16);
                            }
                        }
                     }"
                     x-init="$nextTick(() => { setTimeout(() => { initMap(); }, 250); })">

                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-1.5">
                            <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-black text-xs shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </span>
                            <span class="text-xs font-black text-gray-800">Titik Koordinat Lokasi (Peta MapTiler)</span>
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 font-extrabold">Akurat</span>
                        </div>
                        <button type="button" 
                                wire:click="autoGeocodeLocation" 
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 text-[10px] bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 py-1 rounded-lg font-bold shadow-sm transition-all cursor-pointer">
                            <svg class="w-3 h-3 text-white animate-spin" wire:loading wire:target="autoGeocodeLocation" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <svg class="w-3 h-3" wire:loading.remove wire:target="autoGeocodeLocation" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <span>Deteksi Otomatis</span>
                        </button>
                    </div>

                    {{-- Google Maps Link / Coordinate Quick Extractor --}}
                    <div class="bg-white/95 p-2.5 rounded-xl border border-indigo-200/90 shadow-sm space-y-1.5">
                        <div class="flex items-center justify-between text-[11px] font-bold text-gray-700">
                            <span class="flex items-center gap-1.5 text-red-600 font-extrabold">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                <span>Ambil Titik Presisi dari Google Maps</span>
                            </span>
                            @php
                                $gSearchQuery = urlencode(trim(($appResesLokasi ? $appResesLokasi . ' ' : '') . ($appResesRt ? 'RT ' . $appResesRt . ' ' : '') . ($appResesRw ? 'RW ' . $appResesRw . ' ' : '') . ($this->appResesKelurahans[$appResesKelurahanId] ?? '') . ' ' . ($this->appResesKecamatans[$appResesKecamatanId] ?? 'Cilincing') . ' Jakarta Utara'));
                            @endphp
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $gSearchQuery }}" 
                               target="_blank" 
                               class="inline-flex items-center gap-1 text-[10px] text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-2 py-0.5 rounded-lg font-bold transition-all"
                               title="Buka alamat ini di tab Google Maps baru untuk mengambil titik atau linknya">
                                <svg class="w-3 h-3 text-red-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                <span>Buka di Google Maps</span>
                                <svg class="w-2.5 h-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="relative flex-1">
                                <input type="text" 
                                       wire:model="appResesGoogleMapsInput" 
                                       wire:keydown.enter.prevent="parseGoogleMapsInput"
                                       placeholder="Tempel link Google Maps atau koordinat di sini (cth: https://www.google.com/maps/... atau -6.106374, 106.919958)..." 
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl pl-3 pr-20 py-1.5 text-xs text-gray-800 placeholder-gray-400 outline-none focus:ring-1 focus:ring-indigo-500">
                                <button type="button" 
                                        wire:click="parseGoogleMapsInput" 
                                        wire:loading.attr="disabled"
                                        class="absolute right-1 top-1 bottom-1 px-3 bg-red-600 hover:bg-red-700 text-white rounded-lg text-[10px] font-bold shadow-sm transition-all flex items-center gap-1 cursor-pointer">
                                    <svg class="w-3 h-3 text-white animate-spin" wire:loading wire:target="parseGoogleMapsInput" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span wire:loading.remove wire:target="parseGoogleMapsInput">Terapkan</span>
                                </button>
                            </div>
                        </div>
                        @error('appResesGoogleMapsInput') <p class="text-[10px] text-red-500 font-bold">{{ $message }}</p> @enderror
                    </div>

                    {{-- Map Container --}}
                    <div wire:ignore class="relative rounded-xl overflow-hidden border border-indigo-200 shadow-inner">
                        <div x-ref="mapContainer" class="w-full h-48 sm:h-56 z-0 bg-gray-100"></div>
                        <div class="absolute bottom-1.5 left-2 z-[400] bg-white/90 backdrop-blur-sm px-2.5 py-0.5 rounded-lg border border-gray-200 text-[10px] text-gray-700 font-medium pointer-events-none shadow-sm">
                            💡 Klik peta atau geser pin untuk mengatur titik lokasi presisi
                        </div>
                    </div>

                    {{-- Coordinate Inputs --}}
                    <div class="grid grid-cols-2 gap-3 pt-0.5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 mb-0.5">Latitude (Lintang)</label>
                            <input type="text" wire:model="appResesLatitude" placeholder="cth: -6.123456" class="w-full bg-white border border-gray-200 rounded-xl px-3 py-1.5 text-xs font-mono font-bold text-gray-800 outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 mb-0.5">Longitude (Bujur)</label>
                            <input type="text" wire:model="appResesLongitude" placeholder="cth: 106.876543" class="w-full bg-white border border-gray-200 rounded-xl px-3 py-1.5 text-xs font-mono font-bold text-gray-800 outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                {{-- Sumber Data & Tanggal --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Sumber Data <span class="text-red-500">*</span>
                        </label>
                        <div class="w-full bg-gray-100/90 border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-800 flex items-center justify-between">
                            <span>Inventory</span>
                            <span class="inline-flex items-center gap-1 text-[10px] font-black text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-md">
                                <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                Aplikasi Inventory
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Tanggal Input / Pekerjaan <span class="text-red-500">*</span>
                        </label>
                        <input type="date" wire:model="appResesTanggal" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                </div>

                {{-- No SKPD & Kode Tracking Otomatis --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-indigo-50/40 p-3.5 rounded-2xl border border-indigo-100">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-bold text-gray-700">No. SKPD</label>
                            <span class="text-[9px] font-black text-indigo-700 bg-indigo-100 px-1.5 py-0.5 rounded">Otomatis</span>
                        </div>
                        <input type="text" wire:model="appResesNoSkpd" placeholder="600/RAB-.../SDA/2026" class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-xs font-bold text-gray-700">Kode Tracking</label>
                            <span class="text-[9px] font-black text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">Otomatis (Unik)</span>
                        </div>
                        <div class="relative">
                            <input type="text" wire:model="appResesKodeTracking" placeholder="TRK-..." class="w-full bg-white border border-gray-200 rounded-xl pl-3 pr-8 py-2 text-xs font-mono font-bold text-gray-800 outline-none focus:ring-1 focus:ring-indigo-500">
                            <button type="button" wire:click="regenerateTrackingCode" title="Buat Ulang Kode Tracking Unik" class="absolute right-2 top-2 text-gray-400 hover:text-indigo-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Deskripsi / Uraian Permintaan --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">
                        Deskripsi Pekerjaan SDA / Uraian Usulan <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="appResesUraian" rows="2" placeholder="Tulis rincian permohonan atau pekerjaan..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-xs font-medium text-gray-800 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></textarea>
                    @error('appResesUraian') <p class="text-[11px] text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Keterangan / Catatan Tambahan --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan Tambahan (Opsional)</label>
                    <input type="text" wire:model="appResesKeterangan" placeholder="Catatan tambahan lokasi / dokumen..." class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-800 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>

                {{-- Klasifikasi & Pelaksana Pekerjaan SDA --}}
                <div class="bg-emerald-50/40 p-3.5 rounded-2xl border border-emerald-100">
                    <div class="flex items-center justify-between mb-2.5">
                        <p class="text-[11px] font-black uppercase tracking-wider text-emerald-950 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            Data Pekerjaan SDA
                        </p>
                        <span class="text-[9px] font-black text-emerald-700 bg-emerald-100/90 px-2 py-0.5 rounded-full">Pelaksana & Klasifikasi</span>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1">
                                Kategori Pekerjaan
                            </label>
                            <select wire:model="appResesKategoriPekerjaan" class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-gray-800 outline-none focus:ring-1 focus:ring-emerald-500">
                                @foreach($this->appResesKategoriList as $kKey => $kVal)
                                    <option value="{{ $kKey }}">{{ $kVal }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1">
                                Lingkup Kewenangan
                            </label>
                            <select wire:model="appResesLingkupKewenangan" class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-gray-800 outline-none focus:ring-1 focus:ring-emerald-500">
                                @foreach($this->appResesLingkupList as $lKey => $lVal)
                                    <option value="{{ $lKey }}">{{ $lVal }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1">
                                Metode Pekerjaan
                            </label>
                            <select wire:model="appResesMetodePekerjaan" class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-gray-800 outline-none focus:ring-1 focus:ring-emerald-500">
                                @foreach($this->appResesMetodeList as $mKey => $mVal)
                                    <option value="{{ $mKey }}">{{ $mVal }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-700 mb-1">
                                Tahun Dikerjakan
                            </label>
                            <input type="number" min="2000" max="2100" wire:model="appResesTahunDikerjakan" placeholder="{{ date('Y') }}" class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-gray-800 outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[10px] font-bold text-gray-700">Pelaksana</label>
                                <span class="text-[9px] font-black text-emerald-700 bg-emerald-100 px-1 rounded">ID: {{ $this->appResesIdPelaksana ?: 8 }}</span>
                            </div>
                            <select wire:model.live="appResesIdPelaksana" class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-emerald-800 outline-none focus:ring-1 focus:ring-emerald-500">
                                @foreach($this->appResesPelaksanas as $pId => $pName)
                                    <option value="{{ $pId }}">{{ $pName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Bagian Dimensi Ukuran --}}
                <div class="bg-gray-50/70 p-3.5 rounded-2xl border border-gray-100">
                    <p class="text-[11px] font-black uppercase tracking-wider text-gray-500 mb-2.5 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7M4 7l8 5 8-5M4 7h16"/></svg>
                        Dimensi Ukuran Saluran / Fisik (Opsional)
                    </p>
                    <div class="grid grid-cols-3 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 mb-1">Panjang (m)</label>
                            <input type="number" step="0.01" wire:model="appResesPanjang" placeholder="0" class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-gray-800 outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 mb-1">Lebar (m)</label>
                            <input type="number" step="0.01" wire:model="appResesLebar" placeholder="0" class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-gray-800 outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 mb-1">Tinggi (m)</label>
                            <input type="number" step="0.01" wire:model="appResesTinggi" placeholder="0" class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-gray-800 outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                {{-- Bagian Status & Progress --}}
                <div class="bg-gray-50/70 p-3.5 rounded-2xl border border-gray-100">
                    <p class="text-[11px] font-black uppercase tracking-wider text-gray-500 mb-2.5 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Status Survei & Progress Pekerjaan Fisik
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 mb-1">Status Survei Reses</label>
                            <select wire:model="appResesStatusSurvei" class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-indigo-700 outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="Selesai">Selesai</option>
                                <option value="Diproses">Diproses</option>
                                <option value="Ditinjau">Ditinjau</option>
                                <option value="Ditolak">Ditolak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 mb-1">Status Tindak Lanjut</label>
                            <select wire:model="appResesStatusTindakLanjut" class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-gray-800 outline-none focus:ring-1 focus:ring-indigo-500">
                                <option value="Sedang Dikerjakan">Sedang Dikerjakan</option>
                                <option value="Selesai">Selesai</option>
                                <option value="Survei Lapangan">Survei Lapangan</option>
                                <option value="Perencanaan">Perencanaan</option>
                            </select>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[10px] font-bold text-gray-600">Progress: <span class="font-black text-indigo-600">{{ $appResesProgress }}%</span></label>
                            </div>
                            <input type="range" min="0" max="100" step="5" wire:model.live="appResesProgress" class="w-full accent-indigo-600 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 mb-1">Tgl Mulai / Selesai</label>
                            <div class="grid grid-cols-2 gap-1">
                                <input type="date" wire:model="appResesTglMulai" title="Tgl Mulai" class="w-full bg-white border border-gray-200 rounded-lg px-1.5 py-1.5 text-[10px] font-bold text-gray-800 outline-none">
                                <input type="date" wire:model="appResesTglSelesai" title="Tgl Selesai" class="w-full bg-white border border-gray-200 rounded-lg px-1.5 py-1.5 text-[10px] font-bold text-gray-800 outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Unggah Foto (Foto Awal Progres & Foto Akhir Progres) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- 1. Foto Awal Progres (Disimpan ke Survei Reses: /api/survei-reses) --}}
                    <div class="p-3.5 bg-indigo-50/40 rounded-2xl border border-indigo-100 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-bold text-gray-800 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Foto Awal Progres (0%)
                                </label>
                                <span class="text-[9px] font-black text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-full">Survei Reses</span>
                            </div>
                            <p class="text-[10px] text-gray-500 mb-2 leading-tight">Foto kondisi eksisting / awal sebelum dikerjakan.</p>
                            
                            {{-- Foto Yang Sudah Tersimpan di App-Reses --}}
                            @if(!empty($appResesExistingPhotosAwal))
                                <div class="mb-3 p-2.5 bg-white rounded-xl border border-indigo-200 shadow-xs">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[10px] font-black text-indigo-900 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Foto Tersimpan di App-Reses ({{ count($appResesExistingPhotosAwal) }})
                                        </span>
                                        <span class="text-[9px] text-gray-400 font-bold">App-Reses</span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach($appResesExistingPhotosAwal as $oldUrl)
                                            <a href="{{ $oldUrl }}" target="_blank" class="relative group rounded-lg overflow-hidden border border-indigo-100 aspect-square bg-gray-50 block hover:ring-2 hover:ring-indigo-500 transition-all shadow-xs" title="Klik untuk lihat foto asli di tab baru">
                                                <img src="{{ $oldUrl }}" class="w-full h-full object-cover">
                                                <span class="absolute inset-0 bg-indigo-950/50 text-white text-[9px] font-bold flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                    Buka ↗
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[10px] font-bold text-gray-600">Unggah Foto Baru (Opsional):</span>
                            </div>
                            <input type="file" wire:model="appResesPhotosAwal" multiple accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-bold file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200 border border-dashed border-indigo-200 bg-white rounded-xl p-2 transition-all cursor-pointer">
                            
                            <div wire:loading wire:target="appResesPhotosAwal" class="text-xs text-indigo-600 font-bold mt-2 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>Memproses foto awal...</span>
                            </div>

                            @error('appResesPhotosAwal.*') <p class="text-xs text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Preview Grid Foto Awal Baru --}}
                        @if(!empty($appResesPhotosAwal))
                            <div class="grid grid-cols-3 gap-2 mt-2.5 p-2 bg-white rounded-xl border border-indigo-100">
                                @foreach($appResesPhotosAwal as $pIdx => $pItem)
                                    <div class="relative group rounded-lg overflow-hidden border border-gray-200 aspect-square bg-gray-100">
                                        <img src="{{ $pItem->temporaryUrl() }}" class="w-full h-full object-cover">
                                        <button type="button" wire:click="removeAppResesPhotoAwal({{ $pIdx }})" title="Hapus Foto" class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 shadow transition-transform hover:scale-110">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- 2. Foto Akhir Progres (Disimpan ke Pekerjaan SDA: /api/pekerjaan-sda) --}}
                    <div class="p-3.5 bg-emerald-50/40 rounded-2xl border border-emerald-100 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-bold text-gray-800 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Foto Akhir Progres (100%)
                                </label>
                                <span class="text-[9px] font-black text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">Pekerjaan SDA</span>
                            </div>
                            <p class="text-[10px] text-gray-500 mb-2 leading-tight">Foto hasil pekerjaan fisik / kondisi selesai.</p>
                            
                            {{-- Foto Yang Sudah Tersimpan di Pekerjaan SDA --}}
                            @if(!empty($appResesExistingPhotosAkhir))
                                <div class="mb-3 p-2.5 bg-white rounded-xl border border-emerald-200 shadow-xs">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-[10px] font-black text-emerald-900 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Foto Tersimpan di App-Reses ({{ count($appResesExistingPhotosAkhir) }})
                                        </span>
                                        <span class="text-[9px] text-gray-400 font-bold">App-Reses</span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach($appResesExistingPhotosAkhir as $oldUrl)
                                            <a href="{{ $oldUrl }}" target="_blank" class="relative group rounded-lg overflow-hidden border border-emerald-100 aspect-square bg-gray-50 block hover:ring-2 hover:ring-emerald-500 transition-all shadow-xs" title="Klik untuk lihat foto asli di tab baru">
                                                <img src="{{ $oldUrl }}" class="w-full h-full object-cover">
                                                <span class="absolute inset-0 bg-emerald-950/50 text-white text-[9px] font-bold flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                    Buka ↗
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[10px] font-bold text-gray-600">Unggah Foto Baru (Opsional):</span>
                            </div>
                            <input type="file" wire:model="appResesPhotosAkhir" multiple accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-bold file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200 border border-dashed border-emerald-200 bg-white rounded-xl p-2 transition-all cursor-pointer">
                            
                            <div wire:loading wire:target="appResesPhotosAkhir" class="text-xs text-emerald-600 font-bold mt-2 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>Memproses foto akhir...</span>
                            </div>

                            @error('appResesPhotosAkhir.*') <p class="text-xs text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Preview Grid Foto Akhir Baru --}}
                        @if(!empty($appResesPhotosAkhir))
                            <div class="grid grid-cols-3 gap-2 mt-2.5 p-2 bg-white rounded-xl border border-emerald-100">
                                @foreach($appResesPhotosAkhir as $pIdx => $pItem)
                                    <div class="relative group rounded-lg overflow-hidden border border-gray-200 aspect-square bg-gray-100">
                                        <img src="{{ $pItem->temporaryUrl() }}" class="w-full h-full object-cover">
                                        <button type="button" wire:click="removeAppResesPhotoAkhir({{ $pIdx }})" title="Hapus Foto" class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 shadow transition-transform hover:scale-110">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-3 flex gap-3 border-t border-gray-100">
                    <button type="button" wire:click="closeAppResesModal" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-2xl font-bold text-xs hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    @if($appResesSurveiId || $appResesPekerjaanId)
                        <button type="submit" 
                                wire:loading.attr="disabled"
                                wire:target="submitToAppReses"
                                class="flex-[2] bg-gradient-to-r from-amber-600 via-emerald-600 to-teal-600 text-white py-3 rounded-2xl font-bold text-xs shadow-lg shadow-amber-600/30 hover:from-amber-700 hover:to-teal-700 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="submitToAppReses" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Perbarui Data di API App-Reses (UPDATE)</span>
                            </span>
                            <span wire:loading wire:target="submitToAppReses" class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>Memperbarui Data di App-Reses...</span>
                            </span>
                        </button>
                    @else
                        <button type="submit" 
                                wire:loading.attr="disabled"
                                wire:target="submitToAppReses"
                                class="flex-[2] bg-gradient-to-r from-indigo-600 to-violet-600 text-white py-3 rounded-2xl font-bold text-xs shadow-lg shadow-indigo-600/30 hover:from-indigo-700 hover:to-violet-700 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="submitToAppReses" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                <span>Kirim Sekarang ke API App-Reses (DATA BARU)</span>
                            </span>
                            <span wire:loading wire:target="submitToAppReses" class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>Mengirim Data ke App-Reses...</span>
                            </span>
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
