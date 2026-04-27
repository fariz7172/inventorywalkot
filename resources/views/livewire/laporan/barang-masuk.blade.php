<?php

use App\Models\InventoryTransaction;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

new class extends Component {
    use WithPagination;

    #[Url]
    public $search = '';
    #[Url]
    public $filterType = 'all';
    public $perPage = 10;
    public $startDate = '';
    public $endDate = '';

    // Detail Modal State
    public $showDetailModal = false;
    public $selectedGroup = null;

    protected $listeners = ['global-search' => 'handleGlobalSearch'];

    public function handleGlobalSearch($search)
    {
        $this->search = $search;
        $this->resetPage();
    }

    public function updated($property)
    {
        if (in_array($property, ['search', 'filterType', 'startDate', 'endDate'])) {
            $this->resetPage();
        }
    }

    public function openDetail($ref, $doId, $type, $date, $userId)
    {
        $query = InventoryTransaction::with(['material', 'user'])
            ->where('type', $type)
            ->where('user_id', $userId)
            ->whereDate('created_at', $date);

        if ($doId) {
            $query->where('delivery_order_id', $doId);
        } else {
            $query->where('reference_number', $ref);
        }

        $items = $query->get();

        $this->selectedGroup = [
            'reference' => $ref,
            'type' => $type,
            'date' => $date,
            'items' => $items,
            'user' => $items->first()->user->name ?? 'System',
            'supplier' => $items->first()->supplier,
            'lokasi' => $items->first()->deliveryOrder->lokasi ?? null,
            'image' => $items->first()->image
        ];

        $this->showDetailModal = true;
    }

    public function with()
    {
        $query = InventoryTransaction::query()
            ->select(
                'reference_number',
                'delivery_order_id',
                'type',
                'user_id',
                DB::raw('DATE(created_at) as date'),
                DB::raw('MAX(created_at) as latest_created_at'),
                DB::raw('MAX(image) as latest_image'),
                DB::raw('MAX(supplier) as latest_supplier'),
                DB::raw('COUNT(*) as total_items')
            )
            ->with(['user', 'deliveryOrder'])
            ->groupBy('reference_number', 'delivery_order_id', 'type', 'user_id', 'date')
            ->orderBy('latest_created_at', 'desc');

        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('reference_number', 'like', '%' . $this->search . '%')
                  ->orWhere('supplier', 'like', '%' . $this->search . '%')
                  ->orWhereHas('deliveryOrder', function($dq) {
                      $dq->where('lokasi', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return [
            'transactions' => $query->paginate($this->perPage),
        ];
    }

    public function exportExcel()
    {
        $filename = 'rekap-transaksi-' . now()->format('YmdHis') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\TransactionHistoryExport($this->search, $this->startDate, $this->endDate, $this->filterType), 
            $filename
        );
    }

    public function rendering($view)
    {
        $view->layout('layouts.admin');
    }
};

?>

<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rekapitulasi Transaksi Barang</h1>
            <p class="text-sm text-gray-500 text-pretty max-w-xl">Daftar lengkap riwayat pergerakan material (Masuk & Keluar) yang tercatat di sistem inventory.</p>
        </div>
        
        @if($search || $startDate || $endDate)
        <div class="flex flex-wrap gap-2 items-center">
            @if($search)
            <div class="px-4 py-2 bg-accent/10 border border-accent/20 rounded-xl flex items-center gap-3">
                <span class="text-[10px] font-black text-accent uppercase tracking-widest">Pencarian:</span>
                <span class="text-xs font-bold text-gray-700">"{{ $search }}"</span>
                <button wire:click="$set('search', '')" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            @endif
            @if($startDate || $endDate)
            <div class="px-4 py-2 bg-blue-50 border border-blue-100 rounded-xl flex items-center gap-3">
                <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest">Periode:</span>
                <span class="text-xs font-bold text-gray-700">{{ $startDate ?: '...' }} s/d {{ $endDate ?: '...' }}</span>
                <button wire:click="$set('startDate', ''); $set('endDate', '')" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            @endif
        </div>
        @endif

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1 bg-white p-1.5 rounded-2xl ring-1 ring-accent/5 shadow-sm">
                <button wire:click="$set('filterType', 'all')" @class([
                    'px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
                    'bg-accent text-white shadow-lg shadow-accent/20' => $filterType === 'all',
                    'text-gray-400 hover:text-accent hover:bg-accent/5' => $filterType !== 'all'
                ])>Semua</button>
                <button wire:click="$set('filterType', 'in')" @class([
                    'px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
                    'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20' => $filterType === 'in',
                    'text-gray-400 hover:text-emerald-500 hover:bg-emerald-500/5' => $filterType !== 'in'
                ])>Masuk</button>
                <button wire:click="$set('filterType', 'out')" @class([
                    'px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
                    'bg-red-500 text-white shadow-lg shadow-red-500/20' => $filterType === 'out',
                    'text-gray-400 hover:text-red-500 hover:bg-red-500/5' => $filterType !== 'out'
                ])>Keluar</button>
            </div>
            
            <div class="flex items-center gap-2 bg-white rounded-2xl px-4 py-2 ring-1 ring-accent/5 shadow-sm">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Periode:</label>
                <input type="date" wire:model.live="startDate" class="text-xs text-gray-600 outline-none w-28 bg-transparent">
                <span class="text-gray-300">/</span>
                <input type="date" wire:model.live="endDate" class="text-xs text-gray-600 outline-none w-28 bg-transparent">
            </div>
            <button wire:click="exportExcel" class="bg-emerald-500 text-white px-5 py-2.5 rounded-2xl text-xs font-black uppercase tracking-widest flex items-center gap-2 hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Excel
            </button>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-sm ring-1 ring-accent/5 overflow-hidden">
        <div class="p-8 border-b border-gray-50 flex flex-col md:flex-row justify-between gap-4 bg-white">
            <div></div>

            <div class="flex items-center gap-6">
                <div class="flex items-center gap-4 text-[10px] font-black uppercase tracking-tighter text-gray-400">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Barang Masuk
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        Barang Keluar
                    </div>
                </div>

                <div class="h-8 w-px bg-gray-100"></div>

                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-gray-400 uppercase">Baris:</label>
                    <select wire:model.live="perPage" class="bg-base border-none rounded-xl text-sm font-bold text-gray-700 focus:ring-2 focus:ring-accent/20 outline-none px-4 py-2">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-base/50">
                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Tanggal</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Jenis / Ref</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Ringkasan</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Sumber / Tujuan</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-center">Bukti</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Petugas</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($transactions as $t)
                    <tr wire:click="openDetail('{{ $t->reference_number }}', '{{ $t->delivery_order_id }}', '{{ $t->type }}', '{{ $t->date }}', {{ $t->user_id }})" class="hover:bg-base/30 transition-colors group cursor-pointer">
                        <td class="px-8 py-5">
                            <span class="text-xs font-bold text-gray-700 block">{{ \Carbon\Carbon::parse($t->latest_created_at)->format('d/m/Y') }}</span>
                            <span class="text-[10px] text-gray-400">{{ \Carbon\Carbon::parse($t->latest_created_at)->format('H:i') }} WIB</span>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex flex-col">
                                <span @class([
                                    'text-[10px] font-black uppercase tracking-widest mb-1',
                                    'text-emerald-600' => $t->type === 'in',
                                    'text-red-500' => $t->type === 'out'
                                ])>
                                    {{ $t->type === 'in' ? 'Masuk' : 'Keluar' }}
                                </span>
                                <span class="text-sm font-black text-gray-800">{{ $t->reference_number ?: ($t->deliveryOrder->surat_jalan_no ?? '-') }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-800">{{ $t->total_items }} Item Barang</span>
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter italic">Klik untuk detail</span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            @if($t->type === 'in')
                                <span class="text-xs text-gray-600 font-medium">Dari: <span class="font-bold">{{ $t->latest_supplier ?: 'Restock Internal' }}</span></span>
                            @else
                                <span class="text-xs text-gray-600 font-medium">Tujuan: <span class="font-bold text-red-500">{{ $t->deliveryOrder->lokasi ?? 'Pengeluaran Barang' }}</span></span>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-center">
                            @if($t->latest_image)
                                <div class="flex justify-center">
                                    <img src="{{ Storage::url($t->latest_image) }}" class="w-10 h-10 rounded-lg object-cover ring-2 ring-white shadow-sm">
                                </div>
                            @else
                                <span class="text-[10px] font-bold text-gray-300 italic uppercase">No Photo</span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <span class="text-xs font-bold text-gray-700">{{ $t->user->name ?? 'System' }}</span>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <button class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-accent/5 group-hover:bg-accent text-accent group-hover:text-white rounded-lg text-[9px] font-black uppercase transition-all shadow-sm">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Detail
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-8 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="text-sm font-bold text-gray-400">Tidak ada riwayat transaksi yang ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-8 py-6 bg-base/20 border-t border-gray-50">
            {{ $transactions->links() }}
        </div>
    </div>

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedGroup)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showDetailModal', false)"></div>
        <div class="relative bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up overflow-hidden">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Detail {{ $selectedGroup['type'] === 'in' ? 'Surat Masuk' : 'Surat Keluar' }}</h2>
                    <p class="text-xs text-gray-500 mt-1 font-mono">Ref: {{ $selectedGroup['reference'] ?: '-' }}</p>
                </div>
                <button wire:click="$set('showDetailModal', false)" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-8">
                <div class="bg-base/50 p-4 rounded-2xl">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Sumber / Tujuan</p>
                    <p class="text-sm font-bold text-gray-700">{{ $selectedGroup['type'] === 'in' ? ($selectedGroup['supplier'] ?: 'Restock Internal') : ($selectedGroup['lokasi'] ?: 'Internal') }}</p>
                </div>
                <div class="bg-base/50 p-4 rounded-2xl">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Petugas / Waktu</p>
                    <p class="text-sm font-bold text-gray-700">{{ $selectedGroup['user'] }} - {{ \Carbon\Carbon::parse($selectedGroup['date'])->format('d M Y') }}</p>
                </div>
            </div>

            <div class="max-h-[40vh] overflow-y-auto mb-8 pr-2">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                            <th class="py-3 text-left">Nama Barang</th>
                            <th class="py-3 text-right">Volume</th>
                            <th class="py-3 text-left pl-4">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($selectedGroup['items'] as $item)
                        <tr>
                            <td class="py-4">
                                <p class="font-bold text-gray-800">{{ $item->material->name }}</p>
                                <p class="text-[10px] text-gray-400 uppercase">{{ $item->material->category->name ?? '-' }}</p>
                            </td>
                            <td class="py-4 text-right">
                                <span @class([
                                    'font-black',
                                    'text-emerald-600' => $selectedGroup['type'] === 'in',
                                    'text-red-500' => $selectedGroup['type'] === 'out'
                                ])>
                                    {{ $selectedGroup['type'] === 'in' ? '+' : '-' }}{{ (float)($selectedGroup['type'] === 'in' ? $item->volume_masuk : $item->volume_keluar) }}
                                </span>
                                <span class="text-[10px] font-bold text-gray-400 uppercase ml-1">{{ $item->material->unit }}</span>
                            </td>
                            <td class="py-4 pl-4">
                                <p class="text-xs text-gray-500 italic">{{ $item->note ?: '-' }}</p>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($selectedGroup['image'])
            <div class="mb-8">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Foto Bukti Fisik</p>
                <img src="{{ Storage::url($selectedGroup['image']) }}" class="w-full h-48 object-cover rounded-3xl ring-4 ring-base shadow-inner">
            </div>
            @endif

            <div class="pt-4 flex gap-3">
                <button onclick="printBeritaAcara()" class="flex-1 bg-emerald-500 text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-500/20 hover:bg-emerald-600 transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Berita Acara
                </button>
                <button wire:click="$set('showDetailModal', false)" class="flex-1 bg-gray-900 text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-gray-900/20 hover:bg-gray-800 transition-all">Tutup Detail</button>
            </div>
        </div>
    </div>

    <div id="print-area" class="hidden print:block bg-white p-6 text-black leading-tight" style="font-family: 'Times New Roman', serif;">
        {{-- Kop Surat --}}
        <img src="{{ asset('assets/kop.png') }}" class="w-full h-auto mb-8">

        @php
            $isOut = ($selectedGroup['type'] === 'out');
            $carbonDate = \Carbon\Carbon::parse($selectedGroup['date']);
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            $dayName = $days[$carbonDate->dayOfWeek];
            $monthName = $months[$carbonDate->month];
            
            $sumberTujuan = $selectedGroup['type'] === 'in' ? ($selectedGroup['supplier'] ?: 'Restock Internal') : ($selectedGroup['lokasi'] ?: 'Internal');
            
            // Labels
            if ($isOut) {
                $title1 = "BERITA ACARA SERAH TERIMA BARANG";
                $title2 = "DISTRIBUSI/PENGELUARAN";
                $labelPihakSatu = 'Pengurus Barang/Pengurus Barang Pembantu';
                $labelPihakDua = 'Pemakai Persediaan';
            } else {
                $title1 = "BERITA ACARA SERAH TERIMA BARANG";
                $title2 = "PENGADAAN/PEROLEHAN";
                $labelPihakSatu = 'PPHP/PPK/PPTK/Penyediaan Barang/Pihak Ketiga/Setaranya';
                $labelPihakDua = 'Pengguna Barang/Pengurus Barang Pembantu/Pengurus Barang UPB';
            }
        @endphp

        <div class="text-center mb-6">
            <h1 class="text-lg font-bold underline uppercase leading-tight">
                {{ $title1 }}<br>
                {{ $title2 }}
            </h1>
            <p class="text-sm font-bold mt-1">Nomor: {{ $selectedGroup['reference'] ?: '……………………………' }}</p>
        </div>

        <div class="text-justify mb-4 text-[13px]">
            <p>Pada Hari ini <span class="font-bold">{{ $dayName }}</span> Tanggal <span class="font-bold">{{ $carbonDate->day }}</span> Bulan <span class="font-bold">{{ $monthName }}</span> Tahun <span class="font-bold">{{ $carbonDate->year }}</span> </p>
            <p>yang bertanda tangan dibawah ini:</p>
            
            <div class="mt-3 ml-8 space-y-0.5">
                <p>Nama : <span class="font-bold">{{ $selectedGroup['user'] }}</span></p>
                <p>Jabatan : <span class="font-bold text-[11px]">{{ $isOut ? $labelPihakSatu : $labelPihakDua }}</span></p>
            </div>

            <p class="mt-3">
                @if($isOut)
                    Telah menyerahkan barang persedian yang diterima oleh <span class="font-bold text-sm underline">{{ $sumberTujuan }}</span> 
                @else
                    Telah menerima barang persedian yang diserahkan oleh PPHP/PPK/PPTK/Penyedia Barang/Pihak Ketiga <span class="font-bold text-sm underline">{{ $sumberTujuan }}</span> 
                @endif
                sesuai dengan Berita Acara Pemeriksaan Barang Nomor <span class="font-bold">{{ $selectedGroup['reference'] ?: '……' }}</span> 
                Tanggal <span class="font-bold">{{ $carbonDate->day }}</span> Bulan <span class="font-bold">{{ $monthName }}</span> Tahun <span class="font-bold">{{ $carbonDate->year }}</span>. 
                Sebagaimana daftar terlampir. Daftar barang yang {{ $isOut ? 'diserahkan' : 'diterima' }} sebagai berikut:
            </p>
        </div>

        @if($isOut)
            {{-- Table for Distribusi (More Columns) --}}
            <table class="w-full border-collapse border border-black text-[10px] mb-6">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="border border-black px-1 py-1 text-center w-6" rowspan="2">No</th>
                        <th class="border border-black px-2 py-1 text-left" rowspan="2">Uraian Nama Barang</th>
                        <th class="border border-black px-1 py-1 text-center" rowspan="2">Harga Satuan</th>
                        <th class="border border-black px-1 py-1 text-center" rowspan="2">Satuan</th>
                        <th class="border border-black px-1 py-1 text-center" rowspan="2">Volume</th>
                        <th class="border border-black px-1 py-1 text-center" colspan="3">Jumlah</th>
                        <th class="border border-black px-2 py-1 text-left" rowspan="2">Keterangan</th>
                    </tr>
                    <tr class="bg-gray-50">
                        <th class="border border-black px-1 py-1 text-center">Harga</th>
                        <th class="border border-black px-1 py-1 text-center">PPN (11%)</th>
                        <th class="border border-black px-1 py-1 text-center">Harga Setelah Pajak</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($selectedGroup['items'] as $index => $item)
                    <tr>
                        <td class="border border-black px-1 py-1 text-center">{{ $index + 1 }}</td>
                        <td class="border border-black px-2 py-1 font-bold uppercase">{{ $item->material->name }}</td>
                        <td class="border border-black px-1 py-1 text-right">0</td>
                        <td class="border border-black px-1 py-1 text-center uppercase">{{ $item->material->unit }}</td>
                        <td class="border border-black px-1 py-1 text-center font-bold">
                            {{ (float)$item->volume_keluar }}
                        </td>
                        <td class="border border-black px-1 py-1 text-right">0</td>
                        <td class="border border-black px-1 py-1 text-right">0</td>
                        <td class="border border-black px-1 py-1 text-right">0</td>
                        <td class="border border-black px-2 py-1 italic text-[9px]">{{ $item->note ?: '-' }}</td>
                    </tr>
                    @endforeach
                    <tr class="font-bold bg-gray-50">
                        <td colspan="5" class="border border-black px-2 py-1 text-right uppercase">Jumlah</td>
                        <td class="border border-black px-1 py-1 text-right">0</td>
                        <td class="border border-black px-1 py-1 text-right">0</td>
                        <td class="border border-black px-1 py-1 text-right">0</td>
                        <td class="border border-black"></td>
                    </tr>
                </tbody>
            </table>
        @else
            {{-- Table for Pengadaan (Original) --}}
            <table class="w-full border-collapse border border-black text-[12px] mb-6">
                <thead>
                    <tr>
                        <th class="border border-black px-2 py-1 text-center w-8">No</th>
                        <th class="border border-black px-3 py-1 text-left">Uraian Nama Barang</th>
                        <th class="border border-black px-3 py-1 text-center w-20">Satuan</th>
                        <th class="border border-black px-3 py-1 text-center w-20">Volume</th>
                        <th class="border border-black px-3 py-1 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($selectedGroup['items'] as $index => $item)
                    <tr>
                        <td class="border border-black px-2 py-1.5 text-center">{{ $index + 1 }}</td>
                        <td class="border border-black px-3 py-1.5 font-bold uppercase">{{ $item->material->name }}</td>
                        <td class="border border-black px-3 py-1.5 text-center uppercase">{{ $item->material->unit }}</td>
                        <td class="border border-black px-3 py-1.5 text-center font-bold text-sm">
                            {{ (float)$item->volume_masuk }}
                        </td>
                        <td class="border border-black px-3 py-1.5 italic text-[10px]">{{ $item->note ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p class="text-[13px] mb-8">Demikian Berita Acara Serah Terima Barang ini dibuat dalam rangkap 2 (dua) untuk digunakan sebagaimana mestinya.</p>

        <div class="grid grid-cols-2 text-center text-[13px]">
            <div>
                <p>Jakarta, {{ $carbonDate->day }} {{ $monthName }} {{ $carbonDate->year }}</p>
                <p class="mt-1">Yang menyerahkan Barang,</p>
                <p class="font-bold text-[10px] uppercase max-w-[200px] mx-auto leading-tight mt-1">{{ $labelPihakSatu }}</p>
                
                <div class="h-20"></div>
                
                <p class="font-bold underline uppercase">{{ $isOut ? $selectedGroup['user'] : $sumberTujuan }}</p>
            </div>
            <div>
                <p class="invisible">Jakarta, ...</p>
                <p class="mt-1">Yang menerima Barang,</p>
                <p class="font-bold text-[10px] uppercase max-w-[200px] mx-auto leading-tight mt-1">{{ $labelPihakDua }}</p>
                
                <div class="h-20"></div>
                
                <p class="font-bold underline uppercase">{{ $isOut ? $sumberTujuan : $selectedGroup['user'] }}</p>
            </div>
        </div>
    </div>

    @endif

    <style>
        @media print {
            @page { margin: 1cm; }
            body * {
                visibility: hidden;
            }
            #print-area, #print-area * {
                visibility: visible;
            }
            #print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                display: block !important;
                padding: 0 !important;
            }
        }
    </style>

    <script>
        function printBeritaAcara() {
            const originalTitle = document.title;
            const ref = "{{ $selectedGroup['reference'] ?? 'Draft' }}";
            document.title = "Berita Acara - " + ref;
            window.print();
            document.title = originalTitle;
        }
    </script>
</div>
