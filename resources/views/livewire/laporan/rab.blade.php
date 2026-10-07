<?php

use App\Models\Rab;
use App\Models\DeliveryOrder;
use App\Models\Kecamatan;
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
]);

mount(function() {
    $user = auth()->user();
    if ($user) {
        $isKecamatanUser = $user->hasRole('kecamatan_admin') || 
            ($user->kecamatan_id && !$user->hasAnyRole(['superadmin', 'sudin', 'pemel', 'seksi_pompa', 'pompa']));
        
        if ($isKecamatanUser && $user->kecamatan_id) {
            $this->selectedKecamatanId = (string) $user->kecamatan_id;
        }
    }
});

$kecamatans = computed(fn() => Kecamatan::orderBy('nama_kecamatan', 'asc')->get());

$rabs = computed(function() {
    $q = Rab::with('kecamatan')->orderBy('lokasi', 'asc');
    
    $user = auth()->user();
    $isKecamatanUser = $user && (
        $user->hasRole('kecamatan_admin') || 
        ($user->kecamatan_id && !$user->hasAnyRole(['superadmin', 'sudin', 'pemel', 'seksi_pompa', 'pompa']))
    );

    if ($isKecamatanUser) {
        if ($user->kecamatan_id) {
            $q->where('kecamatan_id', $user->kecamatan_id);
        } else {
            $q->where('id', '<', 0);
        }
    } else {
        if ($this->selectedKecamatanId !== '') {
            if ($this->selectedKecamatanId === 'null') {
                $q->whereNull('kecamatan_id');
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
                <option value="">-- Semua Kecamatan --</option>
                <option value="null">-- Nota Dinas --</option>
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
                                {{ $rabItem->kecamatan->nama_kecamatan ?? ($rabItem->kecamatan_id ? 'Kecamatan ID: '.$rabItem->kecamatan_id : 'Nota Dinas') }}
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
                                <button wire:click="$set('selectedRabId', '{{ $rabItem->id }}')" class="bg-blue-50 text-blue-600 font-bold px-3 py-1.5 rounded-xl hover:bg-blue-600 hover:text-white transition-colors text-xs inline-flex items-center gap-1">
                                    <span>Pilih Lokasi</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
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
</div>
