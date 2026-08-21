<?php

use App\Models\DeliveryOrder;
use App\Models\Material;
use App\Models\Category;
use App\Models\Rab;
use function Livewire\Volt\{state, rules, computed, layout, mount};

layout('layouts.admin');

// State Form
state([
    'order' => null,
    'surat_jalan_no' => '',
    'tanggal' => '',
    'lokasi' => '',
    'pemohon' => '',
    'petugas' => '',
    'penerima' => '',
    'no_polisi' => '',
    'pelaksana_kecamatan' => '',
    'keterangan' => '',
    'selected_materials' => []
]);

$categories = computed(fn() => Category::with('materials')->get());
$allMaterials = computed(fn() => Material::orderBy('name', 'asc')->get());
$rabs = computed(fn() => Rab::orderBy('lokasi', 'asc')->get());

$remainingQuotas = computed(function() {
    $quotas = [];
    if (!$this->lokasi) return $quotas;

    $query = Rab::with('materials')->where('lokasi', $this->lokasi);
    $userKecamatanId = auth()->user()->kecamatan_id;
    if ($userKecamatanId) {
        $query->where('kecamatan_id', $userKecamatanId);
    }
    
    $rab = $query->first();
    if (!$rab) return $quotas;

    foreach ($rab->materials as $mat) {
        $targetVolume = (float)$mat->pivot->target_volume;
        
        $usedVolumeQuery = \Illuminate\Support\Facades\DB::table('delivery_order_materials')
            ->join('delivery_orders', 'delivery_order_materials.delivery_order_id', '=', 'delivery_orders.id')
            ->where('delivery_orders.lokasi', $this->lokasi)
            ->where('delivery_order_materials.material_id', $mat->id);

        if ($this->order) {
            $usedVolumeQuery->where('delivery_orders.id', '!=', $this->order->id);
        }
            
        $usedVolume = $usedVolumeQuery->sum('delivery_order_materials.requested_volume');
            
        $quotas[$mat->id] = max(0, $targetVolume - (float)$usedVolume);
    }
    
    return $quotas;
});

mount(function(DeliveryOrder $order) {
    $user = auth()->user();
    $canEdit = $user->hasRole('superadmin') || $user->hasRole('sudin');
    
    if (!$canEdit && ($user->hasRole('pemel') || $user->hasRole('kecamatan_admin'))) {
        if ($order->status === 'draft' || $order->status === 'rejected') {
            $canEdit = true;
        }
    }
    
    if (!$canEdit) {
        abort(403, 'Akses Ditolak. Anda tidak memiliki izin untuk mengedit Surat Jalan ini.');
    }

    $this->order = $order->load('materials');
    $this->surat_jalan_no = $order->surat_jalan_no;
    $this->tanggal = $order->tanggal->format('Y-m-d');
    $this->lokasi = $order->lokasi;
    $this->pemohon = $order->pemohon;
    $this->petugas = $order->petugas;
    $this->penerima = $order->penerima;
    $this->no_polisi = $order->no_polisi;
    $this->pelaksana_kecamatan = $order->pelaksana_kecamatan;
    $this->keterangan = $order->keterangan;

    foreach ($order->materials as $m) {
        $this->selected_materials[] = [
            'material_id' => $m->id,
            'requested_volume' => (float)$m->pivot->requested_volume
        ];
    }
});

// Action: Tambah Baris Material
$addMaterial = function () {
    if ($this->order->status === 'shipped' || $this->order->status === 'processing') return;
    $this->selected_materials[] = ['material_id' => '', 'requested_volume' => 0];
};

// Action: Hapus Baris Material
$removeMaterial = function ($index) {
    if ($this->order->status === 'shipped' || $this->order->status === 'processing') return;
    unset($this->selected_materials[$index]);
    $this->selected_materials = array_values($this->selected_materials);
};

// Action: Simpan
$save = function () {
    $rules = [
        'surat_jalan_no' => 'required|unique:delivery_orders,surat_jalan_no,' . $this->order->id,
        'tanggal' => 'required|date',
        'lokasi' => 'required',
        'pemohon' => 'required',
        'petugas' => 'required',
        'penerima' => 'required',
    ];

    if ($this->order->status !== 'shipped') {
        $rules['selected_materials.*.material_id'] = 'required|exists:materials,id';
        $rules['selected_materials.*.requested_volume'] = 'required|numeric|min:0.01';
    }

    $this->validate($rules, [
        'required' => 'Kolom ini wajib diisi.',
        'unique' => 'Nomor ini sudah terdaftar.',
        'numeric' => 'Harus berupa angka.',
        'min' => 'Jumlah minimal adalah 0.01.',
    ]);

    // Validasi Limit RAB jika bukan status shipped
    if ($this->order->status !== 'shipped' && $this->lokasi) {
        foreach ($this->selected_materials as $index => $item) {
            if (!empty($item['material_id'])) {
                $material = Material::find($item['material_id']);
                $sisaRAB = isset($this->remainingQuotas[$item['material_id']]) ? $this->remainingQuotas[$item['material_id']] : 0;
                if ($item['requested_volume'] > $sisaRAB) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "selected_materials.{$index}.requested_volume" => "Melebihi Limit RAB Lokasi Ini (Sisa: " . $sisaRAB . " {$material->unit})"
                    ]);
                }
            }
        }
    }

    // Update Metadata
    $this->order->update([
        'surat_jalan_no' => $this->surat_jalan_no,
        'tanggal' => $this->tanggal,
        'lokasi' => $this->lokasi,
        'pemohon' => $this->pemohon,
        'petugas' => $this->petugas,
        'penerima' => $this->penerima,
        'no_polisi' => $this->no_polisi,
        'pelaksana_kecamatan' => $this->pelaksana_kecamatan,
        'keterangan' => $this->keterangan,
    ]);

    // Update Materials ONLY if not shipped or processing
    if ($this->order->status !== 'shipped' && $this->order->status !== 'processing') {
        $syncData = [];
        $rabSync = [];
        foreach ($this->selected_materials as $item) {
            if (!empty($item['material_id'])) {
                $syncData[$item['material_id']] = ['requested_volume' => $item['requested_volume']];
                $rabSync[$item['material_id']] = [];
            }
        }
        $this->order->materials()->sync($syncData);

        // Auto-register material to RAB location
        $rab = Rab::where('lokasi', $this->lokasi)->first();
        if ($rab) {
            $rab->materials()->syncWithoutDetaching($rabSync);
        }
    }

    session()->flash('message', 'Perubahan Surat Jalan berhasil disimpan.');
    return $this->redirect('/dashboard/surat-jalan', navigate: true);
};

?>

<div class="max-w-4xl mx-auto pb-12">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Surat Jalan</h1>
            <p class="text-sm text-gray-500 mt-1">Ubah informasi surat jalan atau daftar barang.</p>
        </div>
        <a href="/dashboard/surat-jalan" wire:navigate class="text-sm font-bold text-gray-400 hover:text-accent transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>

    @if($order->status === 'shipped')
    <div class="bg-amber-50 border border-amber-200 p-4 rounded-2xl mb-6 flex gap-3 items-start">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <div>
            <p class="text-sm font-bold text-amber-800">Perhatian: Surat Jalan Sudah Terkirim</p>
            <p class="text-xs text-amber-600 mt-1 leading-relaxed">
                Surat jalan ini sudah berstatus <strong>Shipped</strong>. Anda hanya dapat mengubah informasi umum (metadata). 
                Daftar barang dan jumlahnya tidak dapat diubah karena sudah memotong stok inventaris.
            </p>
        </div>
    </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- Card 1: Informasi Umum --}}
        <div class="bg-white rounded-3xl shadow-card ring-1 ring-accent/5 p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2 flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center text-accent">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-800">Informasi Pengiriman</h2>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">No. Surat Jalan</label>
                    <input type="text" wire:model="surat_jalan_no" placeholder="Masukkan nomor surat jalan" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all @error('surat_jalan_no') border-red-500 @enderror">
                    @error('surat_jalan_no') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tanggal</label>
                    <input type="datetime-local" wire:model="tanggal" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-accent/50 outline-none transition-all @error('tanggal') border-red-500 @enderror">
                    @error('tanggal') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Lokasi Tujuan (RAB)</label>
                    <select wire:model="lokasi" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all @error('lokasi') border-red-500 @enderror">
                        <option value="">-- Pilih Lokasi RAB --</option>
                        @foreach($this->rabs as $rab)
                            <option value="{{ $rab->lokasi }}">{{ $rab->lokasi }}</option>
                        @endforeach
                    </select>
                    @error('lokasi') <p class="text-[10px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Kecamatan / Pelaksana</label>
                    <input type="text" wire:model="pelaksana_kecamatan" placeholder="Contoh: Kec. Gambir" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
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

        {{-- Card 2: Daftar Material --}}
        <div class="bg-white rounded-3xl shadow-card ring-1 ring-accent/5 p-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h2 class="text-lg font-bold text-gray-800">Daftar Barang</h2>
                </div>
                @if($order->status !== 'shipped' && $order->status !== 'processing')
                <button type="button" wire:click="addMaterial" class="text-xs font-bold text-accent hover:text-accent-dark flex items-center gap-1.5 bg-accent/5 px-4 py-2 rounded-xl transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Barang
                </button>
                @endif
            </div>

            <div class="space-y-4">
                @foreach($selected_materials as $index => $item)
                <div class="flex flex-col sm:flex-row gap-3 bg-base/40 p-3 rounded-xl border border-warm/40 items-end @if($order->status === 'shipped' || $order->status === 'processing') opacity-60 grayscale @endif">
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Pilih Material</label>
                        <select wire:model="selected_materials.{{ $index }}.material_id" 
                            @if($order->status === 'shipped' || $order->status === 'processing') disabled @endif
                            class="w-full bg-white rounded-lg px-3 py-2 text-xs text-gray-700 border border-warm/60 focus:ring-1 focus:ring-accent outline-none @error('selected_materials.'.$index.'.material_id') border-red-500 @enderror">
                            <option value="">-- Pilih --</option>
                            @foreach($this->allMaterials as $m)
                                @php
                                    $sisaRABText = $lokasi ? (isset($this->remainingQuotas[$m->id]) ? $this->remainingQuotas[$m->id] : 0) : '?';
                                @endphp
                                <option value="{{ $m->id }}">{{ $m->name }} (Stok: {{ (float)$m->current_volume }} | RAB Sisa: {{ $sisaRABText }})</option>
                            @endforeach
                        </select>
                        @error('selected_materials.'.$index.'.material_id') <p class="text-[9px] text-red-500 mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>
                    <div class="w-full sm:w-32">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jumlah Keluar</label>
                        <input type="number" step="any" wire:model="selected_materials.{{ $index }}.requested_volume" 
                            @if($order->status === 'shipped' || $order->status === 'processing') disabled @endif
                            @if($order->status === 'shipped') disabled @endif
                            class="w-full bg-white rounded-lg px-3 py-2 text-xs text-gray-700 border border-warm/60 focus:ring-1 focus:ring-accent outline-none @error('selected_materials.'.$index.'.requested_volume') border-red-500 @enderror">
                        @error('selected_materials.'.$index.'.requested_volume')
                            <p class="text-[9px] text-red-500 mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>
                    @if($order->status !== 'shipped' && $order->status !== 'processing')
                    <button type="button" wire:click="removeMaterial({{ $index }})" class="w-full sm:w-10 h-10 rounded-lg bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    @endif
                </div>
                @endforeach
            </div>

            @error('stock')
            <div class="mt-6 p-4 bg-red-50 border border-red-100 rounded-2xl flex items-center gap-3 text-red-600 animate-bounce">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <p class="text-xs font-bold">{{ $message }}</p>
            </div>
            @enderror
        </div>

        <div class="flex justify-end gap-3">
            <button type="submit" class="bg-accent text-white px-8 py-3 rounded-2xl font-bold text-sm hover:bg-accent-dark transition-all shadow-lg shadow-accent/25 flex items-center gap-2 group">
                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
