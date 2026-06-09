<?php

use App\Models\Rab;
use App\Models\Material;
use App\Models\Kecamatan;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RabTemplateExport;
use function Livewire\Volt\{state, computed, layout, on, uses};

uses([WithFileUploads::class]);

layout('layouts.admin');

state([
    'importFile' => null,
    'showModal' => false,
    'editingRab' => null,
    'lokasi' => '',
    'kecamatan_id' => '',
    'search' => '',
    
    // Manage Material RAB
    'showMaterialModal' => false,
    'isViewOnly' => false,
    'managingRab' => null,
    'rabMaterials' => [],
]);

on(['global-search' => function($search) {
    $this->search = $search;
}]);

$rabs = computed(function() {
    $query = Rab::withCount('materials')->with('kecamatan');
    
    $userKecamatanId = auth()->user()->kecamatan_id;
    if ($userKecamatanId) {
        $query->where('kecamatan_id', $userKecamatanId);
    }
    
    if ($this->search) {
        $query->where('lokasi', 'like', '%' . $this->search . '%');
    }
    return $query->get();
});

$allMaterials = computed(fn() => Material::orderBy('name', 'asc')->get());
$allKecamatans = computed(fn() => Kecamatan::orderBy('nama_kecamatan', 'asc')->get());

$openCreate = function() {
    $this->reset(['editingRab', 'lokasi', 'kecamatan_id']);
    $this->showModal = true;
};

$closeModal = function() {
    $this->showModal = false;
};

$closeMaterialModal = function() {
    $this->showMaterialModal = false;
};

$save = function() {
    $userKecamatanId = auth()->user()->kecamatan_id;
    
    $rules = [
        'lokasi' => 'required|string|max:255',
    ];
    
    if (!$userKecamatanId) {
        $rules['kecamatan_id'] = 'required|exists:kecamatans,id';
    }

    $this->validate($rules, [
        'kecamatan_id.required' => 'Wajib memilih kecamatan.'
    ]);

    $finalKecamatanId = $userKecamatanId ?: $this->kecamatan_id;

    if ($this->editingRab) {
        $rabToEdit = Rab::find($this->editingRab['id']);
        if ($rabToEdit->is_locked && !auth()->user()->hasAnyRole(['superadmin', 'sudin', 'kepala_gudang', 'gudang'])) {
            abort(403, 'Akses Ditolak: RAB telah dikunci.');
        }
        $rabToEdit->update([
            'lokasi' => $this->lokasi,
            'kecamatan_id' => $finalKecamatanId,
        ]);
        session()->flash('message', 'Data RAB berhasil diperbarui!');
    } else {
        Rab::create([
            'lokasi' => $this->lokasi,
            'kecamatan_id' => $finalKecamatanId,
        ]);
        session()->flash('message', 'Data RAB baru berhasil ditambahkan!');
    }

    $this->showModal = false;
};

$edit = function(Rab $rab) {
    if ($rab->is_locked && !auth()->user()->hasAnyRole(['superadmin', 'sudin', 'kepala_gudang', 'gudang'])) {
        abort(403, 'Akses Ditolak: RAB telah dikunci.');
    }
    $this->editingRab = $rab->toArray();
    $this->lokasi = $rab->lokasi;
    $this->kecamatan_id = $rab->kecamatan_id;
    $this->showModal = true;
};

$delete = function(Rab $rab) {
    if ($rab->is_locked && !auth()->user()->hasAnyRole(['superadmin', 'sudin', 'kepala_gudang', 'gudang'])) {
        abort(403, 'Akses Ditolak: RAB telah dikunci.');
    }
    $rab->delete();
    session()->flash('message', 'Data RAB berhasil dihapus.');
};

$toggleLock = function(Rab $rab) {
    if (!auth()->user()->hasAnyRole(['superadmin', 'sudin', 'kepala_gudang', 'gudang'])) {
        abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengunci/membuka RAB.');
    }
    $rab->update(['is_locked' => !$rab->is_locked]);
    session()->flash('message', 'Status Kunci RAB berhasil diubah.');
};

// --- Excel Import & Export ---
$downloadTemplate = function() {
    return Excel::download(new RabTemplateExport, 'Template_Stok_RAB.xlsx');
};

$importExcel = function() {
    $this->validate([
        'importFile' => 'required|file|mimes:xlsx,xls,csv|max:5120', // 5MB max
    ], [
        'importFile.required' => 'Pilih file Excel terlebih dahulu.',
        'importFile.mimes' => 'Format file harus .xlsx, .xls, atau .csv',
    ]);

    try {
        $collection = Excel::toCollection(collect([]), $this->importFile)->first();
        
        // Remove heading row
        $collection->shift();

        $importedMaterials = [];
        foreach ($collection as $row) {
            $materialName = $row[0];
            $targetVolume = $row[1];

            if (empty($materialName) || !is_numeric($targetVolume) || $targetVolume <= 0) {
                continue;
            }

            $material = Material::where('name', $materialName)->first();
            if ($material) {
                // Check if already in the list
                $exists = false;
                foreach ($this->rabMaterials as $index => $item) {
                    if ($item['material_id'] == $material->id) {
                        $this->rabMaterials[$index]['target_volume'] = $targetVolume;
                        $exists = true;
                        break;
                    }
                }
                
                if (!$exists) {
                    // Remove empty initial row if it's the only one
                    if (count($this->rabMaterials) === 1 && empty($this->rabMaterials[0]['material_id'])) {
                        $this->rabMaterials = [];
                    }
                    $this->rabMaterials[] = [
                        'material_id' => $material->id,
                        'target_volume' => (float)$targetVolume
                    ];
                }
            }
        }

        $this->importFile = null; // reset
        session()->flash('import_message', 'Berhasil menarik data dari Excel. Silakan periksa daftar di bawah lalu tekan Simpan.');

    } catch (\Exception $e) {
        $this->addError('importFile', 'Gagal memproses file. Pastikan format sesuai template.');
    }
};

// --- Material Management ---
$openManageMaterial = function(Rab $rab) {
    // If locked and not an admin, open as view only
    $this->isViewOnly = $rab->is_locked && !auth()->user()->hasAnyRole(['superadmin', 'sudin', 'kepala_gudang', 'gudang']);
    
    $this->managingRab = $rab;
    $this->rabMaterials = [];
    foreach ($rab->materials as $m) {
        $this->rabMaterials[] = [
            'material_id' => $m->id,
            'material_name' => $m->name,
            'material_unit' => $m->unit,
            'target_volume' => (float)$m->pivot->target_volume
        ];
    }
    if (empty($this->rabMaterials) && !$this->isViewOnly) {
        $this->rabMaterials[] = ['material_id' => '', 'target_volume' => 0];
    }
    $this->showMaterialModal = true;
};

$addRabMaterial = function() {
    $this->rabMaterials[] = ['material_id' => '', 'target_volume' => 0];
};

$removeRabMaterial = function($index) {
    unset($this->rabMaterials[$index]);
    $this->rabMaterials = array_values($this->rabMaterials);
};

$saveMaterials = function() {
    $this->validate([
        'rabMaterials.*.material_id' => 'required|exists:materials,id',
        'rabMaterials.*.target_volume' => 'required|numeric|min:0',
    ], [
        'required' => 'Wajib diisi.',
        'numeric' => 'Harus angka.',
        'min' => 'Min. 0',
    ]);

    $syncData = [];
    foreach ($this->rabMaterials as $item) {
        if (!empty($item['material_id'])) {
            $syncData[$item['material_id']] = ['target_volume' => $item['target_volume']];
        }
    }
    
    $this->managingRab->materials()->sync($syncData);
    
    session()->flash('message', 'Target Stok Material untuk RAB berhasil disimpan.');
    $this->showMaterialModal = false;
};

?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rencana Anggaran Biaya (RAB)</h1>
            <p class="text-sm text-gray-500">Kelola data RAB, Lokasi, dan Kuota Material.</p>
        </div>
        <button wire:click="openCreate" class="bg-accent text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-accent/20 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah RAB
        </button>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold">
            {{ session('message') }}
        </div>
    @endif
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($this->rabs as $rab)
        <div class="bg-white rounded-[2rem] shadow-card ring-1 ring-accent/5 p-6 relative group overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-accent/5 rounded-full transition-all group-hover:scale-150"></div>
            
            <div class="relative z-10 flex flex-col h-full">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center text-accent">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="flex gap-1">
                        @if(!$rab->is_locked || auth()->user()->hasAnyRole(['superadmin', 'sudin', 'kepala_gudang', 'gudang']))
                        <button wire:click="edit({{ $rab->id }})" class="p-1.5 text-gray-400 hover:text-accent transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button wire:click="delete({{ $rab->id }})" wire:confirm="Hapus data RAB ini?" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                        @endif
                    </div>
                </div>
                
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="text-sm font-bold text-gray-400">ID: #{{ $rab->id }}</h3>
                    @if($rab->kecamatan)
                        <span class="px-2 py-0.5 rounded-md bg-gray-100 text-[10px] font-bold text-gray-500 uppercase">{{ $rab->kecamatan->nama_kecamatan }}</span>
                    @endif
                    @if($rab->is_locked)
                        <span class="px-2 py-0.5 rounded-md bg-red-100 text-[10px] font-bold text-red-600 flex items-center gap-1 uppercase">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Terkunci
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-[10px] font-bold text-emerald-600 flex items-center gap-1 uppercase">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                            Terbuka
                        </span>
                    @endif
                </div>
                <p class="text-lg font-bold text-gray-900 mb-4 flex-1">{{ $rab->lokasi }}</p>
                
                <div class="pt-4 border-t border-warm/60 flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Total Item</span>
                    <span class="text-sm font-black text-accent">{{ $rab->materials_count }} Material</span>
                </div>
                
                <div class="flex items-center gap-2 mt-4">
                    <button wire:click="openManageMaterial({{ $rab->id }})" class="flex-1 bg-accent/10 text-accent font-bold text-xs py-2 rounded-xl hover:bg-accent hover:text-white transition-colors flex items-center justify-center gap-1">
                        @if($rab->is_locked && !auth()->user()->hasAnyRole(['superadmin', 'sudin', 'kepala_gudang', 'gudang']))
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Lihat Detail RAB
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Kelola Stok
                        @endif
                    </button>

                    @hasanyrole('superadmin|sudin|kepala_gudang|gudang')
                    <button wire:click="toggleLock({{ $rab->id }})" class="flex-1 {{ $rab->is_locked ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-600' : 'bg-red-50 text-red-600 hover:bg-red-600' }} font-bold text-xs py-2 rounded-xl hover:text-white transition-colors flex items-center justify-center gap-1">
                        @if($rab->is_locked)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                        Buka Kunci
                        @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Kunci RAB
                        @endif
                    </button>
                    @endhasanyrole
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Modal Create/Edit RAB --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="closeModal"></div>
        <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up">
            <h2 class="text-xl font-bold text-gray-900 mb-2">{{ $editingRab ? 'Edit RAB' : 'Tambah RAB Baru' }}</h2>
            <p class="text-xs text-gray-500 mb-6">Masukkan data lokasi untuk RAB.</p>
            
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Lokasi</label>
                    <input type="text" wire:model="lokasi" placeholder="Contoh: Gedung A, Proyek B" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                    @error('lokasi') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                @if(!auth()->user()->kecamatan_id)
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Kecamatan</label>
                    <select wire:model="kecamatan_id" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none text-gray-700">
                        <option value="">-- Pilih Kecamatan --</option>
                        @foreach($this->allKecamatans as $kecamatan)
                            <option value="{{ $kecamatan->id }}">{{ $kecamatan->nama_kecamatan }}</option>
                        @endforeach
                    </select>
                    @error('kecamatan_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                @endif

                <div class="pt-4 flex gap-3">
                    <button type="button" wire:click="closeModal" class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-2xl font-bold text-sm">Batal</button>
                    <button type="submit" class="flex-1 bg-accent text-white py-3 rounded-2xl font-bold text-sm shadow-lg shadow-accent/20">Simpan RAB</button>
                </div>
            </form>
        </div>
    </div>
    @endif
    
    {{-- Modal Kelola Material RAB --}}
    @if($showMaterialModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="closeMaterialModal"></div>
        <div class="relative bg-white w-full max-w-2xl max-h-[90vh] flex flex-col rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-2">{{ $isViewOnly ? 'Detail Target Material RAB' : 'Kelola Stok Material RAB' }}</h2>
                <p class="text-xs text-gray-500">{{ $isViewOnly ? 'Daftar material untuk lokasi:' : 'Tentukan target stok untuk lokasi:' }} <span class="font-bold text-accent">{{ $managingRab->lokasi ?? '' }}</span></p>
                @if($isViewOnly)
                    <div class="mt-3 bg-red-50 border border-red-100 text-red-600 px-3 py-2 rounded-lg text-xs font-bold flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Status RAB ini sudah Terkunci. Anda hanya dapat melihat datanya.
                    </div>
                @endif
            </div>
            
            @if(!$isViewOnly)
            {{-- Excel Tools --}}
            <div class="mb-6 bg-base/50 p-4 rounded-2xl border border-warm/40 flex flex-col sm:flex-row gap-4 items-end justify-between">
                <div>
                    <h3 class="text-xs font-bold text-gray-700 mb-1">Import Data Massal</h3>
                    <p class="text-[10px] text-gray-500 mb-2">Gunakan template untuk mengisi ribuan barang sekaligus tanpa salah ketik.</p>
                    <button type="button" wire:click="downloadTemplate" class="text-xs font-bold text-white bg-gray-800 px-4 py-2 rounded-xl hover:bg-gray-900 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        1. Download Template
                    </button>
                </div>
                <div class="flex flex-col sm:flex-row items-end sm:items-center gap-2 w-full sm:w-auto">
                    <div class="w-full sm:w-auto">
                        <input type="file" wire:model="importFile" accept=".xlsx,.xls,.csv" class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-accent/10 file:text-accent hover:file:bg-accent hover:file:text-white transition-all cursor-pointer">
                        <div wire:loading wire:target="importFile" class="text-[10px] text-accent mt-1 animate-pulse">Mengunggah file...</div>
                        @error('importFile') <p class="text-[9px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>
                    <button type="button" wire:click="importExcel" class="w-full sm:w-auto text-xs font-bold text-white bg-accent px-4 py-2 rounded-xl hover:bg-accent-dark transition-colors flex items-center justify-center gap-2 shadow-md shadow-accent/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        2. Import Data
                    </button>
                </div>
            </div>
            @endif

            @if (session()->has('import_message'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-4 text-[11px] font-bold">
                    {{ session('import_message') }}
                </div>
            @endif
            
            <form wire:submit="saveMaterials" class="flex flex-col flex-1 min-h-0">
                <div class="flex-1 overflow-y-auto pr-2 space-y-3 mb-6">
                    @if($isViewOnly && empty($rabMaterials))
                        <div class="text-center py-8 text-gray-400 text-sm">Belum ada material di RAB ini.</div>
                    @endif

                    @foreach($rabMaterials as $index => $item)
                    <div class="flex flex-col sm:flex-row gap-3 bg-base/50 p-3 rounded-xl border border-warm/40 items-end">
                        <div class="flex-1">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Material</label>
                            @if($isViewOnly)
                                <div class="w-full bg-gray-100 rounded-lg px-3 py-2 text-xs text-gray-700 font-bold border border-warm/60">
                                    {{ $item['material_name'] ?? 'Tidak diketahui' }}
                                </div>
                            @else
                                <select wire:model="rabMaterials.{{ $index }}.material_id" class="w-full bg-white rounded-lg px-3 py-2 text-xs text-gray-700 border border-warm/60 focus:ring-1 focus:ring-accent outline-none">
                                    <option value="">-- Pilih --</option>
                                    @foreach($this->allMaterials as $m)
                                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                                    @endforeach
                                </select>
                                @error('rabMaterials.'.$index.'.material_id') <p class="text-[9px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                            @endif
                        </div>
                        <div class="w-full sm:w-32">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Target Kuota</label>
                            @if($isViewOnly)
                                <div class="w-full bg-gray-100 rounded-lg px-3 py-2 text-xs text-gray-700 font-bold border border-warm/60">
                                    {{ $item['target_volume'] }} {{ $item['material_unit'] ?? '' }}
                                </div>
                            @else
                                <input type="number" step="any" wire:model="rabMaterials.{{ $index }}.target_volume" class="w-full bg-white rounded-lg px-3 py-2 text-xs text-gray-700 border border-warm/60 focus:ring-1 focus:ring-accent outline-none">
                                @error('rabMaterials.'.$index.'.target_volume') <p class="text-[9px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                            @endif
                        </div>
                        @if(!$isViewOnly)
                        <button type="button" wire:click="removeRabMaterial({{ $index }})" class="w-8 h-8 bg-red-50 text-red-500 rounded-lg flex items-center justify-center hover:bg-red-500 hover:text-white transition-all mb-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        @endif
                    </div>
                    @endforeach
                    
                    @if(!$isViewOnly)
                    <button type="button" wire:click="addRabMaterial" class="w-full text-xs font-bold text-accent bg-accent/10 px-3 py-3 rounded-xl hover:bg-accent hover:text-white transition-all flex items-center justify-center gap-2 border border-accent/20 border-dashed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah Baris Material
                    </button>
                    @endif
                </div>

                <div class="pt-4 flex gap-3 border-t border-gray-100">
                    <button type="button" wire:click="closeMaterialModal" class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-2xl font-bold text-sm">Tutup</button>
                    @if(!$isViewOnly)
                    <button type="submit" class="flex-[2] bg-accent text-white py-3 rounded-2xl font-bold text-sm shadow-lg shadow-accent/20">Simpan Pengaturan Stok</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
