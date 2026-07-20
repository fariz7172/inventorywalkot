<?php

use App\Models\DeliveryOrder;
use App\Models\Material;
use App\Models\Category;
use App\Models\Rab;
use function Livewire\Volt\{state, rules, computed, layout, mount, usesFileUploads};

layout('layouts.admin');
usesFileUploads();

// State Form
state([
    'surat_jalan_no' => '',
    'tanggal' => date('Y-m-d'),
    'lokasi' => '',
    'is_manual_lokasi' => false,
    'pemohon' => '',
    'petugas' => 'SANJAYA',
    'penerima' => '',
    'no_polisi' => '',
    'pelaksana_kecamatan' => '',
    'keterangan' => '',
    'nota_dinas_photo' => null,
    'progress_photo' => [],
    'selected_materials' => [['material_id' => '', 'requested_volume' => 0]]
]);

$remainingQuotas = computed(function() {
    $quotas = [];
    if (!$this->lokasi || $this->is_manual_lokasi) {
        return $quotas;
    }

    $query = Rab::with('materials')->where('lokasi', $this->lokasi);
    if (auth()->user()->hasRole('kecamatan_admin')) {
        $query->where('kecamatan_id', auth()->user()->kecamatan_id);
    }
    
    $rab = $query->first();
    if (!$rab) return $quotas;

    foreach ($rab->materials as $mat) {
        $targetVolume = (float)$mat->pivot->target_volume;
        
        $usedVolume = \Illuminate\Support\Facades\DB::table('delivery_order_materials')
            ->join('delivery_orders', 'delivery_order_materials.delivery_order_id', '=', 'delivery_orders.id')
            ->where('delivery_orders.lokasi', $this->lokasi)
            ->where('delivery_order_materials.material_id', $mat->id)
            ->sum('delivery_order_materials.requested_volume');
            
        $quotas[$mat->id] = max(0, $targetVolume - (float)$usedVolume);
    }
    
    return $quotas;
});

$categories = computed(fn() => Category::with('materials')->get());
$allMaterials = computed(fn() => Material::orderBy('name', 'asc')->get());
$rabs = computed(function() {
    $query = Rab::has('materials')->orderBy('lokasi', 'asc');
    
    if (auth()->user()->hasRole('kecamatan_admin')) {
        if (auth()->user()->kecamatan_id) {
            $query->where('kecamatan_id', auth()->user()->kecamatan_id);
        } else {
            // Mencegah query `where('kecamatan_id', null)` yang akan menampilkan data tanpa kecamatan
            $query->where('id', '<', 0); 
        }
    }
    
    return $query->get();
});

$hasPreviousHistory = computed(function() {
    if (!$this->lokasi || $this->is_manual_lokasi) {
        return false;
    }
    return \App\Models\DeliveryOrder::where('lokasi', $this->lokasi)->exists();
});

mount(function() {
    if (!auth()->user()->hasRole('superadmin') && !auth()->user()->hasRole('sudin') && !auth()->user()->hasRole('kecamatan_admin') && !auth()->user()->hasRole('pemel')) {
        abort(403, 'Akses Ditolak. Hanya Superadmin, Sudin, dan Admin Kecamatan yang dapat membuat Surat Jalan.');
    }
});

// Action: Tambah Baris Material
$addMaterial = function () {
    $this->selected_materials[] = ['material_id' => '', 'requested_volume' => 0];
};

// Action: Hapus Foto Nota Dinas
$removePhoto = function () {
    $this->nota_dinas_photo = null;
};

// Action: Hapus Foto Progress
$removeProgressPhoto = function ($index) {
    if (isset($this->progress_photo[$index])) {
        unset($this->progress_photo[$index]);
        $this->progress_photo = array_values($this->progress_photo);
    }
};

// Action: Hapus Baris Material
$removeMaterial = function ($index) {
    unset($this->selected_materials[$index]);
    $this->selected_materials = array_values($this->selected_materials);
};

$save = function () {
    $rules = [
        'surat_jalan_no' => 'required|unique:delivery_orders,surat_jalan_no',
        'tanggal' => 'required|date',
        'lokasi' => 'required',
        'pemohon' => 'required',
        'petugas' => 'required',
        'penerima' => 'required',
        'selected_materials.*.material_id' => 'required|exists:materials,id',
        'selected_materials.*.requested_volume' => 'required|numeric|min:0.01',
        'nota_dinas_photo' => 'nullable|image|max:2048',
    ];
    $messages = [
        'required' => 'Kolom ini wajib diisi.',
        'unique' => 'Nomor ini sudah terdaftar.',
        'numeric' => 'Harus berupa angka.',
        'min' => 'Jumlah minimal adalah 0.01.',
        'image' => 'File harus berupa gambar.',
        'max' => 'Ukuran gambar maksimal 2MB.',
    ];

    if (!$this->is_manual_lokasi) {
        if ($this->hasPreviousHistory) {
            $rules['progress_photo'] = 'required|array|min:1';
            $rules['progress_photo.*'] = 'image|max:2048';
            $messages['progress_photo.required'] = 'Silahkan upload Foto Progress untuk lokasi ini.';
            $messages['progress_photo.*.image'] = 'Semua file progress harus berupa gambar.';
            $messages['progress_photo.*.max'] = 'Ukuran gambar progress maksimal 2MB per file.';
        } else {
            $rules['progress_photo'] = 'nullable|array';
            $rules['progress_photo.*'] = 'image|max:2048';
            $messages['progress_photo.*.image'] = 'Semua file progress harus berupa gambar.';
            $messages['progress_photo.*.max'] = 'Ukuran gambar progress maksimal 2MB per file.';
        }
    }

    $this->validate($rules, $messages);

    // Validasi Stok & RAB
    foreach ($this->selected_materials as $index => $item) {
        if (!empty($item['material_id'])) {
            $material = Material::find($item['material_id']);
            if ($material && $item['requested_volume'] > $material->current_volume) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "selected_materials.{$index}.requested_volume" => "Maaf, Stok Kosong atau Melebihi RAB(Tersisa: " . (float)$material->current_volume . " {$material->unit})"
                ]);
            }

            // Limit RAB
            if (!$this->is_manual_lokasi && $this->lokasi) {
                $sisaRAB = isset($this->remainingQuotas[$item['material_id']]) ? $this->remainingQuotas[$item['material_id']] : 0;
                if ($item['requested_volume'] > $sisaRAB) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "selected_materials.{$index}.requested_volume" => "Melebihi Limit RAB Lokasi Ini (Sisa: " . $sisaRAB . " {$material->unit})"
                    ]);
                }
            }
        }
    }

    $photoPath = null;
    if ($this->nota_dinas_photo) {
        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        $image = $manager->read($this->nota_dinas_photo->getRealPath());
        $encoded = $image->toWebp(75);
        $filename = 'nota_dinas/' . uniqid('nd_') . '.webp';
        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, (string) $encoded);
        $photoPath = $filename;
    }

    $progressPhotoPath = null;
    if (!empty($this->progress_photo)) {
        $paths = [];
        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        foreach ($this->progress_photo as $photo) {
            $image = $manager->read($photo->getRealPath());
            $encoded = $image->toWebp(75);
            $filename = 'progress_photos/' . uniqid('pp_') . '.webp';
            \Illuminate\Support\Facades\Storage::disk('public')->put($filename, (string) $encoded);
            $paths[] = $filename;
        }
        $progressPhotoPath = implode(',', $paths);
    }

    $order = DeliveryOrder::create([
        'surat_jalan_no' => $this->surat_jalan_no,
        'tanggal' => $this->tanggal,
        'lokasi' => $this->lokasi,
        'pemohon' => $this->pemohon,
        'petugas' => $this->petugas,
        'penerima' => $this->penerima,
        'no_polisi' => $this->no_polisi,
        'pelaksana_kecamatan' => $this->pelaksana_kecamatan,
        'keterangan' => $this->keterangan,
        'status' => 'draft',
        'nota_dinas_photo' => $photoPath,
        'progress_photo' => $progressPhotoPath
    ]);

    // Simpan Daftar Material ke Tabel Pivot
    foreach ($this->selected_materials as $item) {
        if (!empty($item['material_id']) && $item['requested_volume'] > 0) {
            $order->materials()->attach($item['material_id'], [
                'requested_volume' => $item['requested_volume']
            ]);
        }
    }

    // Auto-register material to RAB location so it appears in planning
    $rab = Rab::where('lokasi', $this->lokasi)->first();
    if (!$rab && $this->is_manual_lokasi) {
        $rab = Rab::create([
            'lokasi' => $this->lokasi,
            'kecamatan_id' => auth()->user()->kecamatan_id
        ]);
    }

    if ($rab) {
        $rabSync = [];
        foreach ($this->selected_materials as $item) {
            if (!empty($item['material_id'])) {
                $rabSync[$item['material_id']] = [];
            }
        }
        $rab->materials()->syncWithoutDetaching($rabSync);
    }

    session()->flash('message', 'Surat Jalan berhasil dibuat dan berstatus Draft.');
    return $this->redirect('/dashboard/surat-jalan', navigate: true);
};

?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Buat Surat Jalan Baru</h1>
            <p class="text-sm text-gray-500">Isi data pengiriman barang ke lokasi tujuan.</p>
        </div>
        <a href="/dashboard/surat-jalan" wire:navigate class="text-sm font-semibold text-gray-500 hover:text-accent flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
            <h2 class="text-sm font-bold text-accent uppercase tracking-wider mb-4 border-b border-warm/60 pb-2">1. Informasi Pengiriman</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">No. Surat Jalan</label>
                    <input type="text" wire:model="surat_jalan_no" placeholder="Masukkan No. Surat Jalan" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 font-mono font-bold border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all @error('surat_jalan_no') border-red-500 @enderror">
                    @error('surat_jalan_no') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tanggal</label>
                    <input type="date" wire:model="tanggal" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all @error('tanggal') border-red-500 @enderror">
                    @error('tanggal') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-gray-600">Lokasi Tujuan</label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" wire:model.live="is_manual_lokasi" class="rounded text-accent focus:ring-accent border-warm/60">
                            <span class="text-[10px] font-bold text-gray-500 uppercase">Input Manual</span>
                        </label>
                    </div>
                    
                    @if($is_manual_lokasi)
                        <div class="bg-accent/5 border border-accent/20 rounded-xl p-4 mb-3">
                            <label class="block text-xs font-bold text-accent mb-2">Silahkan Upload(Photo Bukti) Surat Permintaan Barang NOTA DINAS</label>
                            
                            @if (!$nota_dinas_photo)
                                <input type="file" wire:model="nota_dinas_photo" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 mb-2">
                                @error('nota_dinas_photo') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                                <div wire:loading wire:target="nota_dinas_photo" class="text-xs text-accent font-bold">Uploading...</div>
                            @else
                                <div class="mt-2 relative inline-block group">
                                    <img src="{{ $nota_dinas_photo->temporaryUrl() }}" class="h-24 rounded-lg object-cover border border-warm/40">
                                    <button type="button" wire:click="removePhoto" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1.5 shadow-md hover:bg-red-600 transition-all z-10" title="Hapus Foto">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @endif
                        </div>
                        @if ($nota_dinas_photo)
                            <textarea wire:model="lokasi" rows="2" placeholder="Masukkan detail lokasi manual..." class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all @error('lokasi') border-red-500 @enderror"></textarea>
                        @endif
                    @else
                        <div x-data="{
                                open: false,
                                search: '',
                                selected: @entangle('lokasi').live,
                                options: {{ json_encode($this->rabs->pluck('lokasi')->toArray()) }},
                                get filteredOptions() {
                                    if (this.search === '') {
                                        return this.options;
                                    }
                                    return this.options.filter(opt => opt.toLowerCase().includes(this.search.toLowerCase()));
                                }
                            }"
                            class="relative w-full"
                            @click.away="open = false"
                        >
                            <div @click="open = !open"
                                 class="w-full bg-base rounded-xl px-4 py-2.5 text-sm border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all cursor-pointer flex justify-between items-center @error('lokasi') border-red-500 @enderror">
                                <span x-text="selected ? selected : '-- Pilih Lokasi --'" :class="selected ? 'text-gray-700 font-bold' : 'text-gray-500'"></span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>

                            <div x-show="open" 
                                 x-transition.opacity
                                 style="display: none;"
                                 class="absolute z-50 w-full mt-1 bg-white border border-warm/60 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                                 
                                 <div x-show="options.length > 10" class="p-2 sticky top-0 bg-white border-b border-warm/30 shadow-sm">
                                     <input type="text" x-model="search" placeholder="Cari lokasi..." 
                                            class="w-full bg-gray-50 rounded-lg px-3 py-2 text-sm border border-warm/30 focus:outline-none focus:ring-1 focus:ring-accent"
                                            @click.stop>
                                 </div>

                                 <ul class="py-1">
                                     <template x-for="option in filteredOptions" :key="option">
                                         <li @click="selected = option; open = false; search = ''"
                                             class="px-4 py-2 text-sm text-gray-700 hover:bg-accent hover:text-white cursor-pointer transition-colors"
                                             x-text="option">
                                         </li>
                                     </template>
                                     <li x-show="filteredOptions.length === 0" class="px-4 py-2 text-sm text-gray-400 italic">
                                         Lokasi tidak ditemukan...
                                     </li>
                                 </ul>
                            </div>
                        </div>
                    @endif
                    @error('lokasi') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                    
                    @if(!$is_manual_lokasi && $this->hasPreviousHistory)
                        <div class="bg-accent/5 border border-accent/20 rounded-xl p-4 mt-3 mb-3">
                            <label class="block text-xs font-bold text-accent mb-2">Silahkan Upload Photo Progress Yang Sudah Dikerjakan (Bisa lebih dari 1)</label>
                            
                            @if (empty($progress_photo))
                                <input type="file" wire:model="progress_photo" multiple accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 mb-2">
                                @error('progress_photo') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                                @error('progress_photo.*') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                                <div wire:loading wire:target="progress_photo" class="text-xs text-accent font-bold">Uploading...</div>
                            @else
                                <div class="mt-2 flex flex-wrap gap-3">
                                    @foreach($progress_photo as $index => $photo)
                                    <div class="relative inline-block group">
                                        <img src="{{ $photo->temporaryUrl() }}" class="h-24 w-24 rounded-lg object-cover border border-warm/40">
                                        <button type="button" wire:click="removeProgressPhoto({{ $index }})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1.5 shadow-md hover:bg-red-600 transition-all z-10" title="Hapus Foto">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="mt-3">
                                    <label class="block text-[10px] font-bold text-gray-400 mb-1">Pilih Ulang Foto (Ganti Semua Foto)</label>
                                    <input type="file" wire:model="progress_photo" multiple accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-[10px] file:font-semibold file:bg-gray-100 file:text-gray-600 hover:file:bg-gray-200">
                                </div>
                                @error('progress_photo') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                                @error('progress_photo.*') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                                <div wire:loading wire:target="progress_photo" class="text-xs text-accent font-bold mt-2">Uploading...</div>
                            @endif
                        </div>
                    @endif
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Kecamatan / Pelaksana</label>
                    <input type="text" wire:model="pelaksana_kecamatan" placeholder="Contoh: Kec. Cilincing" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Pemohon</label>
                    <input type="text" wire:model="pemohon" placeholder="Nama Pemohon" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all @error('pemohon') border-red-500 @enderror">
                    @error('pemohon') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Petugas (Admin)</label>
                    <input type="text" wire:model="petugas" placeholder="Nama Petugas" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all @error('petugas') border-red-500 @enderror">
                    @error('petugas') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Penerima Barang</label>
                    <input type="text" wire:model="penerima" placeholder="Nama Penerima" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all @error('penerima') border-red-500 @enderror">
                    @error('penerima') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">No. Polisi Kendaraan</label>
                    <input type="text" wire:model="no_polisi" placeholder="Contoh: B 1234 ABC" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Keterangan Tambahan</label>
                    <input type="text" wire:model="keterangan" placeholder="..." class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
            <div class="flex items-center justify-between mb-4 border-b border-warm/60 pb-2">
                <h2 class="text-sm font-bold text-accent uppercase tracking-wider">2. Daftar Material</h2>
                <button type="button" wire:click="addMaterial" class="text-xs font-bold text-accent bg-accent/10 px-3 py-1.5 rounded-lg hover:bg-accent hover:text-white transition-all flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Baris
                </button>
            </div>

            <div class="space-y-3">
                @foreach($selected_materials as $index => $item)
                <div class="flex flex-col sm:flex-row gap-3 bg-base/40 p-3 rounded-xl border border-warm/40 sm:items-end">
                    <div class="flex-1 w-full min-w-0">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Pilih Material</label>
                        
                        @php
                            $materialOptions = [];
                            foreach($this->allMaterials as $m) {
                                $sisaRABText = ($lokasi && !$is_manual_lokasi) ? (isset($this->remainingQuotas[$m->id]) ? $this->remainingQuotas[$m->id] : 0) : '?';
                                $label = $m->name . ' (Stok: ' . (float)$m->current_volume . ' | RAB Sisa: ' . $sisaRABText . ')';
                                $materialOptions[] = ['id' => $m->id, 'label' => $label];
                            }
                        @endphp
                        
                        <div x-data="{
                                open: false,
                                search: '',
                                selectedId: @entangle('selected_materials.' . $index . '.material_id').live,
                                options: {{ json_encode($materialOptions) }},
                                get filteredOptions() {
                                    if (this.search === '') return this.options;
                                    return this.options.filter(opt => opt.label.toLowerCase().includes(this.search.toLowerCase()));
                                },
                                get selectedLabel() {
                                    const selectedOpt = this.options.find(opt => opt.id == this.selectedId);
                                    return selectedOpt ? selectedOpt.label : '-- Pilih Material --';
                                }
                            }"
                            class="relative w-full"
                            @click.away="open = false"
                        >
                            <div @click="open = !open"
                                 class="w-full bg-white rounded-lg px-3 py-2 text-xs border border-warm/60 focus:ring-1 focus:ring-accent outline-none cursor-pointer flex justify-between items-center gap-2 @error('selected_materials.'.$index.'.material_id') border-red-500 @enderror">
                                <span x-text="selectedLabel" :class="selectedId ? 'text-gray-700 font-medium' : 'text-gray-500'" class="truncate flex-1 text-left block"></span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>

                            <div x-show="open" 
                                 x-transition.opacity
                                 style="display: none;"
                                 class="absolute z-50 w-full mt-1 bg-white border border-warm/60 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                                 
                                 <div x-show="options.length > 10" class="p-2 sticky top-0 bg-white border-b border-warm/30 shadow-sm z-10">
                                     <input type="text" x-model="search" placeholder="Cari material..." 
                                            class="w-full bg-gray-50 rounded-md px-3 py-1.5 text-xs border border-warm/30 focus:outline-none focus:ring-1 focus:ring-accent"
                                            @click.stop>
                                 </div>

                                 <ul class="py-1">
                                     <template x-for="option in filteredOptions" :key="option.id">
                                         <li @click="selectedId = option.id; open = false; search = ''"
                                             class="px-3 py-2 text-xs text-gray-700 hover:bg-accent hover:text-white cursor-pointer transition-colors"
                                             x-text="option.label">
                                         </li>
                                     </template>
                                     <li x-show="filteredOptions.length === 0" class="px-3 py-2 text-xs text-gray-400 italic">
                                         Material tidak ditemukan...
                                     </li>
                                 </ul>
                            </div>
                        </div>
                        
                        @error('selected_materials.'.$index.'.material_id') <p class="text-[9px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>
                    <div class="w-full sm:w-32">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jumlah Keluar</label>
                        <input type="number" step="any" wire:model="selected_materials.{{ $index }}.requested_volume" class="w-full bg-white rounded-lg px-3 py-2 text-xs text-gray-700 border border-warm/60 focus:ring-1 focus:ring-accent outline-none @error('selected_materials.'.$index.'.requested_volume') border-red-500 @enderror">
                        @error('selected_materials.'.$index.'.requested_volume')
                            <p class="text-[9px] text-red-500 mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>
                    @if(count($selected_materials) > 1)
                    <button type="button" wire:click="removeMaterial({{ $index }})" class="w-8 h-8 bg-red-50 text-red-500 rounded-lg flex items-center justify-center hover:bg-red-500 hover:text-white transition-all mb-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="bg-accent text-white px-10 py-3 rounded-2xl font-bold hover:bg-accent-dark transition-all shadow-xl shadow-accent/40 flex items-center gap-2">
                Simpan & Kirim ke Gudang
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7m0 0l-7 7m7-7H6"/>
                </svg>
            </button>
        </div>
    </form>
</div>
