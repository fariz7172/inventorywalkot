<?php

use function Livewire\Volt\{state, computed, layout, mount};
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Material;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

layout('layouts.admin');

state([
    'showCreateModal' => false,
    'showDetailModal' => false,
    'isEditing' => false,
    'selectedOpnameId' => null,
    'opnames' => [],
    'materials' => [],
    'opname_date' => date('Y-m-d'),
    'notes' => '',
    'approverNotes' => '',
    'opnameItems' => [], // physical volume
    'itemNotes' => [], // individual item notes
    'selectedOpname' => null,
    'hasPendingOpname' => false,
    'needsDifferenceConfirmation' => false,
    'filterPeriod' => 'all', // all, this_week, this_month, this_year
]);

mount(function() {
    $this->loadData();
});

$loadData = function() {
    $this->materials = Material::all();
    foreach($this->materials as $m) {
        if(!isset($this->opnameItems[$m->id]) && !$this->isEditing) {
            $this->opnameItems[$m->id] = (float)$m->current_volume;
            $this->itemNotes[$m->id] = '';
        }
    }
    
    $query = StockOpname::with(['user', 'approver'])->latest();
    
    if ($this->filterPeriod === 'this_week') {
        $query->whereBetween('opname_date', [now()->startOfWeek()->format('Y-m-d'), now()->endOfWeek()->format('Y-m-d')]);
    } elseif ($this->filterPeriod === 'this_month') {
        $query->whereMonth('opname_date', now()->month)
              ->whereYear('opname_date', now()->year);
    } elseif ($this->filterPeriod === 'this_year') {
        $query->whereYear('opname_date', now()->year);
    }
    
    $this->opnames = $query->get();
    $this->hasPendingOpname = StockOpname::where('status', 'pending')->exists();
};

$openCreate = function() {
    $this->isEditing = false;
    $this->selectedOpnameId = null;
    $this->opname_date = date('Y-m-d');
    $this->notes = '';
    $this->needsDifferenceConfirmation = false;
    foreach($this->materials as $m) {
        $this->opnameItems[$m->id] = (float)$m->current_volume;
        $this->itemNotes[$m->id] = '';
    }
    $this->showCreateModal = true;
};

$editOpname = function($id) {
    $opname = StockOpname::with('items')->findOrFail($id);
    if ($opname->status !== 'pending' || $opname->user_id !== auth()->id()) return;

    $this->selectedOpnameId = $opname->id;
    $this->opname_date = \Carbon\Carbon::parse($opname->opname_date)->format('Y-m-d');
    $this->notes = $opname->notes;
    $this->needsDifferenceConfirmation = false;
    
    foreach($opname->items as $item) {
        $this->opnameItems[$item->material_id] = (float)$item->physical_volume;
        $this->itemNotes[$item->material_id] = $item->notes ?? '';
    }
    
    $this->isEditing = true;
    $this->showCreateModal = true;
};

$deleteOpname = function($id) {
    $opname = StockOpname::findOrFail($id);
    if ($opname->status === 'pending' && $opname->user_id === auth()->id()) {
        $opname->delete();
    }
    $this->loadData();
};

$saveOpname = function($force = false) {
    $this->validate([
        'opname_date' => 'required|date',
        'notes' => 'nullable|string',
    ]);

    if (!$this->isEditing && $this->hasPendingOpname) {
        session()->flash('error', 'Masih ada Opname yang berstatus Pending. Harap tunggu di-ACC atau batalkan terlebih dahulu.');
        return;
    }

    if (!$force) {
        $hasDifference = false;
        foreach($this->materials as $m) {
            $physical = isset($this->opnameItems[$m->id]) ? (float)$this->opnameItems[$m->id] : (float)$m->current_volume;
            if ($physical != (float)$m->current_volume) {
                $hasDifference = true;
                break;
            }
        }
        
        if ($hasDifference) {
            $this->needsDifferenceConfirmation = true;
            return;
        }
    }

    DB::transaction(function() {
        if ($this->isEditing && $this->selectedOpnameId) {
            $opname = StockOpname::findOrFail($this->selectedOpnameId);
            $opname->update([
                'opname_date' => $this->opname_date,
                'notes' => $this->notes,
            ]);
            $opname->items()->delete();
        } else {
            $opname = StockOpname::create([
                'user_id' => auth()->id(),
                'opname_date' => $this->opname_date,
                'notes' => $this->notes,
                'status' => 'pending'
            ]);
        }

        foreach($this->materials as $m) {
            $physical = isset($this->opnameItems[$m->id]) ? (float)$this->opnameItems[$m->id] : (float)$m->current_volume;
            $diff = $physical - (float)$m->current_volume;
            $iNote = $this->itemNotes[$m->id] ?? null;

            StockOpnameItem::create([
                'stock_opname_id' => $opname->id,
                'material_id' => $m->id,
                'system_volume' => $m->current_volume,
                'physical_volume' => $physical,
                'difference' => $diff,
                'notes' => $iNote
            ]);
        }
    });

    $this->showCreateModal = false;
    $this->isEditing = false;
    $this->needsDifferenceConfirmation = false;
    $this->notes = '';
    $this->loadData();
};

$openDetail = function($id) {
    $this->selectedOpname = StockOpname::with(['items.material', 'user', 'approver'])->findOrFail($id);
    $this->approverNotes = '';
    $this->showDetailModal = true;
};

$closeDetail = function() {
    $this->showDetailModal = false;
    $this->selectedOpname = null;
    $this->approverNotes = '';
};

$approveOpname = function($id) {
    if (!auth()->user()->hasRole('superadmin')) return;

    DB::transaction(function() use ($id) {
        $opname = StockOpname::with('items')->findOrFail($id);
        
        if ($opname->status !== 'pending') return;

        $opname->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approver_notes' => $this->approverNotes
        ]);

        foreach($opname->items as $item) {
            if ($item->difference != 0) {
                InventoryTransaction::create([
                    'material_id' => $item->material_id,
                    'type' => $item->difference > 0 ? 'in' : 'out',
                    'volume_masuk' => $item->difference > 0 ? abs($item->difference) : 0,
                    'volume_keluar' => $item->difference < 0 ? abs($item->difference) : 0,
                    'reference_number' => 'OPN-' . $opname->id,
                    'note' => 'Penyesuaian Opname (Fisik: ' . $item->physical_volume . ', Sistem: ' . $item->system_volume . ')',
                    'user_id' => auth()->id()
                ]);
            }
        }
    });

    $this->showDetailModal = false;
    $this->loadData();
};

$rejectOpname = function($id) {
    if (!auth()->user()->hasRole('superadmin')) return;

    $this->validate([
        'approverNotes' => 'required|string|min:3'
    ], [
        'approverNotes.required' => 'Catatan penolakan harus diisi agar Gudang tahu alasannya.'
    ]);

    $opname = StockOpname::findOrFail($id);
    if ($opname->status === 'pending') {
        $opname->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approver_notes' => $this->approverNotes
        ]);
    }

    $this->showDetailModal = false;
    $this->loadData();
};

?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Stock Opname</h1>
            <p class="text-sm text-gray-500 mt-1">Pencocokan fisik barang dan persetujuan penyesuaian saldo.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('laporan.stock-opname') }}" class="bg-white border border-gray-300 text-gray-700 px-5 py-2.5 rounded-xl font-bold transition-all shadow-sm hover:bg-gray-50 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Lihat Laporan Rekap
            </a>
            
            @if(auth()->user()->hasRole('gudang'))
                @if($hasPendingOpname)
                    <div class="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-xl text-sm font-bold border border-yellow-200">
                        Selesaikan Opname yang masih Pending
                    </div>
                @else
                    <button wire:click="openCreate" class="bg-accent hover:bg-accent-light text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-md flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Buat Opname
                    </button>
                @endif
            @endif
        </div>
    </div>

    @if (session()->has('error'))
        <div class="mb-4 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="flex justify-between items-end mb-4">
        <div class="w-64">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Filter Periode</label>
            <div class="relative">
                <select wire:model.live="filterPeriod" wire:change="loadData" class="w-full appearance-none bg-white border border-gray-300 text-gray-700 py-2.5 px-4 pr-8 rounded-xl focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent shadow-sm transition-all cursor-pointer font-medium">
                    <option value="all">Menampilkan Semua Data</option>
                    <option value="this_week">Minggu Ini</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="this_year">Tahun Ini</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Table List Opname -->
    <div class="bg-white rounded-2xl shadow-card overflow-hidden ring-1 ring-accent/5">
        <table class="w-full text-sm text-left">
            <thead class="bg-warm/30 text-gray-600 font-semibold border-b border-warm/60">
                <tr>
                    <th class="px-6 py-4">Tgl Opname</th>
                    <th class="px-6 py-4">Dibuat Oleh</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-warm/30">
                @forelse($opnames as $op)
                <tr class="hover:bg-base/50 transition-colors">
                    <td class="px-6 py-4 font-medium text-gray-900">{{ \Carbon\Carbon::parse($op->opname_date)->format('d M Y') }}</td>
                    <td class="px-6 py-4">{{ $op->user->name }}</td>
                    <td class="px-6 py-4">
                        @if($op->status === 'pending')
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Menunggu ACC</span>
                        @elseif($op->status === 'approved')
                            <span class="bg-emerald-100 text-emerald-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Disetujui</span>
                        @else
                            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Ditolak</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <button wire:click="openDetail({{ $op->id }})" class="text-accent hover:text-accent-dark font-semibold">Lihat Detail</button>
                            
                            @if($op->status === 'pending' && $op->user_id === auth()->id())
                                <span class="text-gray-300">|</span>
                                <button wire:click="editOpname({{ $op->id }})" class="text-blue-600 hover:text-blue-800 font-semibold">Edit</button>
                                <button wire:click="deleteOpname({{ $op->id }})" class="text-red-600 hover:text-red-800 font-semibold" onclick="return confirm('Yakin ingin membatalkan opname ini?')">Batalkan</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">Belum ada riwayat Stock Opname.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- CREATE MODAL (Hanya Gudang) -->
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-4xl max-h-[90vh] flex flex-col rounded-3xl shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-warm/30">
                <h3 class="text-xl font-bold text-gray-800">{{ $isEditing ? 'Edit Stock Opname' : 'Buat Laporan Stock Opname' }}</h3>
                <button wire:click="$set('showCreateModal', false)" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Opname</label>
                        <input type="date" wire:model="opname_date" class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-accent/50 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan</label>
                        <input type="text" wire:model="notes" placeholder="Cth: Opname akhir bulan April..." class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-accent/50 outline-none">
                    </div>
                </div>

                <div class="border rounded-xl overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 font-semibold border-b">
                            <tr>
                                <th class="px-4 py-3">Barang / Material</th>
                                <th class="px-4 py-3 text-center">Stok Sistem</th>
                                <th class="px-4 py-3 text-center w-32">Stok Fisik</th>
                                <th class="px-4 py-3">Keterangan Selisih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($materials as $m)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $m->name }} <span class="text-xs text-gray-500">({{ $m->unit }})</span></td>
                                <td class="px-4 py-3 text-center font-bold text-gray-500">{{ (float)$m->current_volume }}</td>
                                <td class="px-4 py-2">
                                    <input type="number" step="0.01" wire:model="opnameItems.{{ $m->id }}" class="w-full text-center rounded-lg border-gray-300 focus:ring-accent focus:border-accent">
                                </td>
                                <td class="px-4 py-2">
                                    <input type="text" wire:model="itemNotes.{{ $m->id }}" placeholder="Opsional (rusak, hilang...)" class="w-full text-sm rounded-lg border-gray-300 focus:ring-accent focus:border-accent">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
                <button wire:click="$set('showCreateModal', false)" class="px-5 py-2.5 rounded-xl font-bold text-gray-600 hover:bg-gray-200 transition-colors">Batal</button>
                <button wire:click="saveOpname(false)" class="px-5 py-2.5 rounded-xl font-bold bg-accent hover:bg-accent-light text-white transition-colors">{{ $isEditing ? 'Simpan Perubahan' : 'Kirim Ajuan Opname' }}</button>
            </div>
        </div>
    </div>

    <!-- Peringatan Selisih Stok (Difference Confirmation) -->
    @if($needsDifferenceConfirmation)
    <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-md flex flex-col rounded-3xl shadow-2xl overflow-hidden p-6 text-center animate-fade-in-up">
            <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Konfirmasi Selisih Stok</h3>
            <p class="text-gray-600 mb-6 text-sm">
                Anda memasukkan angka Stok Fisik yang <b>BERBEDA</b> dari Stok Sistem (ada kelebihan atau kekurangan) pada beberapa barang.<br><br>
                Apakah Anda sudah mengecek seluruh area gudang secara teliti dan yakin angka ini bukan salah ketik (typo)?
            </p>
            <div class="flex gap-3 justify-center">
                <button wire:click="$set('needsDifferenceConfirmation', false)" class="px-5 py-2.5 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors flex-1">Cek Kembali</button>
                <button wire:click="saveOpname(true)" class="px-5 py-2.5 rounded-xl font-bold bg-yellow-500 hover:bg-yellow-600 text-white transition-colors shadow-md flex-1">Ya, Sudah Benar</button>
            </div>
        </div>
    </div>
    @endif
    @endif

    <!-- DETAIL MODAL -->
    @if($showDetailModal && $selectedOpname)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-5xl max-h-[90vh] flex flex-col rounded-3xl shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-warm/30">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Detail Stock Opname</h3>
                    <p class="text-xs text-gray-500">Dibuat oleh {{ $selectedOpname->user->name }} pada {{ \Carbon\Carbon::parse($selectedOpname->opname_date)->format('d M Y') }}</p>
                </div>
                <button wire:click="closeDetail" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                @if($selectedOpname->notes)
                    <div class="mb-4 p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <span class="font-bold text-sm text-gray-700">Catatan:</span> {{ $selectedOpname->notes }}
                    </div>
                @endif
                
                <div class="border rounded-xl overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 font-semibold border-b">
                            <tr>
                                <th class="px-4 py-3">Material</th>
                                <th class="px-4 py-3 text-center">Sistem</th>
                                <th class="px-4 py-3 text-center">Fisik</th>
                                <th class="px-4 py-3 text-center">Selisih</th>
                                <th class="px-4 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($selectedOpname->items as $item)
                            <tr class="hover:bg-gray-50 {{ $item->difference != 0 ? 'bg-orange-50/30' : '' }}">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $item->material->name }} <span class="text-xs text-gray-500">({{ $item->material->unit }})</span></td>
                                <td class="px-4 py-3 text-center font-bold text-gray-500">{{ (float)$item->system_volume }}</td>
                                <td class="px-4 py-3 text-center font-bold text-gray-800">{{ (float)$item->physical_volume }}</td>
                                <td class="px-4 py-3 text-center font-bold {{ $item->difference > 0 ? 'text-emerald-600' : ($item->difference < 0 ? 'text-red-600' : 'text-gray-400') }}">
                                    {{ $item->difference > 0 ? '+'.(float)$item->difference : ($item->difference == 0 ? '-' : (float)$item->difference) }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 italic text-xs">{{ $item->notes ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex flex-col gap-4">
                <!-- Jika ada Catatan dari Superadmin (History) -->
                @if($selectedOpname->approver_notes)
                    <div class="p-3 {{ $selectedOpname->status === 'approved' ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200' }} border rounded-xl">
                        <span class="font-bold text-sm {{ $selectedOpname->status === 'approved' ? 'text-emerald-700' : 'text-red-700' }}">Catatan Superadmin:</span>
                        <p class="text-sm text-gray-700 mt-1">{{ $selectedOpname->approver_notes }}</p>
                    </div>
                @endif

                <!-- Input Catatan Superadmin (Saat Pending) -->
                @if(auth()->user()->hasRole('superadmin') && $selectedOpname->status === 'pending')
                    <div class="w-full">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Catatan Superadmin <span class="text-xs font-normal text-gray-500">(Wajib jika menolak)</span></label>
                        <textarea wire:model="approverNotes" rows="2" class="w-full rounded-xl border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-accent/50 outline-none" placeholder="Tuliskan alasan penolakan atau catatan tambahan persetujuan..."></textarea>
                        @error('approverNotes') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="flex justify-between items-center w-full">
                    <div>
                        @if($selectedOpname->status === 'approved')
                            <span class="text-sm font-bold text-emerald-600">Disetujui oleh {{ $selectedOpname->approver->name ?? '-' }}</span>
                        @elseif($selectedOpname->status === 'rejected')
                            <span class="text-sm font-bold text-red-600">Ditolak oleh {{ $selectedOpname->approver->name ?? '-' }}</span>
                        @else
                            <span class="text-sm font-bold text-yellow-600">Menunggu Persetujuan</span>
                        @endif
                    </div>

                    <div class="flex gap-3">
                        <button onclick="printStockOpname()" class="px-5 py-2.5 rounded-xl font-bold bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Cetak
                        </button>
                        <button wire:click="closeDetail" class="px-5 py-2.5 rounded-xl font-bold text-gray-600 hover:bg-gray-200 transition-colors">Tutup</button>
                        
                        @if(auth()->user()->hasRole('superadmin') && $selectedOpname->status === 'pending')
                            <button wire:click="rejectOpname({{ $selectedOpname->id }})" class="px-5 py-2.5 rounded-xl font-bold bg-red-100 text-red-600 hover:bg-red-200 transition-colors">Tolak</button>
                            <button wire:click="approveOpname({{ $selectedOpname->id }})" class="px-5 py-2.5 rounded-xl font-bold bg-emerald-500 text-white hover:bg-emerald-600 transition-colors shadow-md">Setujui & Sesuaikan Stok</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden Print Section --}}
        <div id="print-area-opname" class="hidden print:block bg-white p-6 text-black leading-tight" style="font-family: 'Times New Roman', serif;">
            {{-- Kop Surat --}}
            <img src="{{ asset('assets/kop.png') }}" class="w-full h-auto mb-8">

            <div class="text-center mb-6">
                <h1 class="text-lg font-bold underline uppercase leading-tight">
                    BERITA ACARA PEMERIKSAAN FISIK<br>
                    (BERITA ACARA STOCK OPNAME/BASO)
                </h1>
                <p class="text-sm font-bold mt-1">Nomor: {{ $selectedOpname->notes ?: '……………………………' }}</p>
            </div>

            @php
                $carbonDate = \Carbon\Carbon::parse($selectedOpname->opname_date);
                $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                
                $dayName = $days[$carbonDate->dayOfWeek];
                $monthName = $months[$carbonDate->month];

                // Roles for signers
                $namaSuperAdmin = $selectedOpname->approver->name ?? '………………………………';
                $namaGudang = $selectedOpname->user->name ?? '………………………………';
            @endphp

            <div class="text-justify mb-4 text-[13px] leading-relaxed">
                <p>Pada Hari ini <span class="font-bold">{{ $dayName }}</span> Tanggal <span class="font-bold">{{ $carbonDate->day }}</span> Bulan <span class="font-bold">{{ $monthName }}</span> Tahun <span class="font-bold">{{ $carbonDate->year }}</span> yang bertanda tangan dibawah ini:</p>
                
                <div class="mt-2 ml-8 mb-4">
                    <p>Nama : <span class="font-bold underline">{{ $namaSuperAdmin }}</span></p>
                    <p>Jabatan : <span class="font-bold">Super Admin</span></p>
                </div>

                <p>Sesuai Dengan Peraturan Dalam Negeri No 19 Tahun 2016 Tentang Pedoman Pengolahan Barang Milik Daerah, Kami Melakukan Pemeriksaan Setempat atas Sisa Barang Persediaan (stock Opname) Yang Dikelola Oleh :</p>
                
                <div class="mt-2 ml-8 mb-4">
                    <p>Nama : <span class="font-bold underline">{{ $namaGudang }}</span></p>
                    <p>Jabatan : <span class="font-bold">Pengurus Barang/Pengurus Barang Pembantu</span></p>
                </div>

                <p>Berdasarkan Keputusan Gurbernur ……… Nomor ……………… Tahun……….. Tanggal………. Ditugaskan Untuk Mengurus Barang, Berdasarkan Hasil Pemeriksaan Fisik Barang (Stok Opname), Kami Mendapatkan Hasil Sebagai Berikut:</p>
            </div>

            <table class="w-full border-collapse border border-black text-[12px] mb-6">
                <thead>
                    <tr>
                        <th class="border border-black px-2 py-1 text-center w-8">No</th>
                        <th class="border border-black px-3 py-1 text-left">Uraian Nama Barang</th>
                        <th class="border border-black px-2 py-1 text-center">Satuan</th>
                        <th class="border border-black px-2 py-1 text-center">Volume (Fisik)</th>
                        <th class="border border-black px-2 py-1 text-center">Jumlah</th>
                        <th class="border border-black px-3 py-1 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($selectedOpname->items as $index => $item)
                    <tr>
                        <td class="border border-black px-2 py-1 text-center">{{ $index + 1 }}</td>
                        <td class="border border-black px-3 py-1 font-bold uppercase">{{ $item->material->name }}</td>
                        <td class="border border-black px-2 py-1 text-center uppercase">{{ $item->material->unit }}</td>
                        <td class="border border-black px-2 py-1 text-center font-bold">
                            {{ (float)$item->physical_volume }}
                        </td>
                        <td class="border border-black px-2 py-1 text-center font-bold">
                            {{ (float)$item->physical_volume }}
                        </td>
                        <td class="border border-black px-3 py-1 italic text-[10px]">{{ $item->notes ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="text-[13px] mb-8 leading-relaxed text-justify">Demikian Berita Acara Stock Opname ini dibuat dalam rangkap 2 (dua) untuk digunakan sebagaimana mestinya.</p>

            <div class="grid grid-cols-2 text-center text-[13px] mt-10">
                <div>
                    <p>Jakarta, {{ $carbonDate->day }} {{ $monthName }} {{ $carbonDate->year }}</p>
                    <p class="mt-1 font-bold">Yang Memeriksa Barang,</p>
                    <p class="font-bold text-[10px] uppercase">(Super Admin)</p>
                    
                    <div class="h-24"></div>
                    
                    <p class="font-bold underline uppercase">{{ $namaSuperAdmin }}</p>
                    <p class="text-[11px]">NIP: ……………………………</p>
                </div>
                <div>
                    <p class="invisible">Jakarta, ...</p>
                    <p class="mt-1 font-bold">Pengurus Barang/Pengurus Barang Pembantu,</p>
                    <p class="font-bold text-[10px] uppercase">(Gudang)</p>
                    
                    <div class="h-24"></div>
                    
                    <p class="font-bold underline uppercase">{{ $namaGudang }}</p>
                    <p class="text-[11px]">NIP: ……………………………</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        function printStockOpname() {
            const printArea = document.getElementById('print-area-opname');
            if (!printArea) {
                alert('Data cetak tidak ditemukan. Silakan buka detail terlebih dahulu.');
                return;
            }
            
            const printContents = printArea.innerHTML;
            const ref = "{{ $selectedOpname?->notes ?? 'Draft' }}";
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank', 'height=600,width=800');
            
            printWindow.document.write('<html><head><title>Stock Opname - ' + ref + '</title>');
            printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">');
            printWindow.document.write('<style>');
            printWindow.document.write('body { font-family: "Times New Roman", serif; padding: 20px; color: black; background: white; }');
            printWindow.document.write('@@page { margin: 1.5cm; }');
            printWindow.document.write('table { border-collapse: collapse; width: 100%; border: 1px solid black !important; }');
            printWindow.document.write('th, td { border: 1px solid black !important; padding: 8px; }');
            printWindow.document.write('img { max-width: 100%; height: auto; }');
            printWindow.document.write('</style></head><body>');
            printWindow.document.write(printContents);
            printWindow.document.write('</body></html>');
            
            printWindow.document.close();
            
            // Wait for styles/images to load
            setTimeout(() => {
                if (printWindow) {
                    printWindow.focus();
                    printWindow.print();
                    printWindow.close();
                }
            }, 700);
        }
    </script>
</div>
