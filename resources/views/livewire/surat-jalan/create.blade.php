<?php

use App\Models\DeliveryOrder;
use App\Models\Material;
use App\Models\Category;
use function Livewire\Volt\{state, rules, computed, layout};

layout('layouts.admin');

// State Form
state([
    'surat_jalan_no' => 'SJ-' . date('YmdHis'),
    'tanggal' => date('Y-m-d'),
    'lokasi' => '',
    'pemohon' => '',
    'petugas' => '',
    'no_polisi' => '',
    'pelaksana_kecamatan' => '',
    'keterangan' => '',
    'selected_materials' => [['material_id' => '', 'requested_volume' => 0]]
]);

$categories = computed(fn() => Category::with('materials')->get());
$allMaterials = computed(fn() => Material::all());

mount(function() {
    if (!auth()->user()->hasRole('superadmin')) {
        return $this->redirect('/dashboard/surat-jalan', navigate: true);
    }
});

// Action: Tambah Baris Material
$addMaterial = function () {
    $this->selected_materials[] = ['material_id' => '', 'requested_volume' => 0];
};

// Action: Hapus Baris Material
$removeMaterial = function ($index) {
    unset($this->selected_materials[$index]);
    $this->selected_materials = array_values($this->selected_materials);
};

// Action: Simpan
$save = function () {
    $this->validate([
        'surat_jalan_no' => 'required|unique:delivery_orders,surat_jalan_no',
        'tanggal' => 'required|date',
        'lokasi' => 'required',
        'selected_materials.*.material_id' => 'required|exists:materials,id',
    ]);

    $order = DeliveryOrder::create([
        'surat_jalan_no' => $this->surat_jalan_no,
        'tanggal' => $this->tanggal,
        'lokasi' => $this->lokasi,
        'pemohon' => $this->pemohon,
        'petugas' => $this->petugas,
        'no_polisi' => $this->no_polisi,
        'pelaksana_kecamatan' => $this->pelaksana_kecamatan,
        'keterangan' => $this->keterangan,
        'status' => 'draft'
    ]);

    // Simpan Daftar Material ke Tabel Pivot
    foreach ($this->selected_materials as $item) {
        if (!empty($item['material_id']) && $item['requested_volume'] > 0) {
            $order->materials()->attach($item['material_id'], [
                'requested_volume' => $item['requested_volume']
            ]);
        }
    }

    session()->flash('message', 'Surat Jalan berhasil dibuat sebagai Draft.');
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
                    <input type="text" wire:model="surat_jalan_no" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 font-mono font-bold border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all" readonly>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tanggal</label>
                    <input type="date" wire:model="tanggal" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Lokasi Tujuan</label>
                    <input type="text" wire:model="lokasi" placeholder="Contoh: Proyek A, Gedung B" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Kecamatan / Pelaksana</label>
                    <input type="text" wire:model="pelaksana_kecamatan" placeholder="Contoh: Kec. Gambir" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Pemohon</label>
                    <input type="text" wire:model="pemohon" placeholder="Nama Pemohon" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Petugas (Admin)</label>
                    <input type="text" wire:model="petugas" placeholder="Nama Petugas" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
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
                <div class="flex flex-col sm:flex-row gap-3 bg-base/40 p-3 rounded-xl border border-warm/40 items-end">
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Pilih Material</label>
                        <select wire:model="selected_materials.{{ $index }}.material_id" class="w-full bg-white rounded-lg px-3 py-2 text-xs text-gray-700 border border-warm/60 focus:ring-1 focus:ring-accent outline-none">
                            <option value="">-- Pilih --</option>
                            @foreach($this->allMaterials as $m)
                                <option value="{{ $m->id }}">{{ $m->name }} (Stok: {{ $m->current_volume }} {{ $m->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full sm:w-24">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jumlah Keluar</label>
                        <input type="number" step="0.01" wire:model="selected_materials.{{ $index }}.requested_volume" class="w-full bg-white rounded-lg px-3 py-2 text-xs text-gray-700 border border-warm/60 focus:ring-1 focus:ring-accent outline-none">
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
