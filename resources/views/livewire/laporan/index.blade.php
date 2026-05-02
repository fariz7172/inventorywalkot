<?php
use App\Models\InventoryTransaction;
use App\Models\Material;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;
use Carbon\Carbon;


new class extends Component {
    use WithPagination;

    public function rendering($view)
    {
        $view->layout('layouts.admin');
    }

    #[Url]
    public $period = 'all';
    
    #[Url]
    public $material_id = '';
    
    public $search = '';
    
    #[Url]
    public $startDate = '';
    
    #[Url]
    public $endDate = '';

    #[Url]
    public $type = ''; // 'in' or 'out'

    protected $listeners = ['global-search' => 'handleGlobalSearch'];

    public function handleGlobalSearch($search)
    {
        $this->search = $search;
        $this->resetPage();
    }

    public function with()
    {
        $query = InventoryTransaction::with(['material', 'deliveryOrder']);
        
        if ($this->material_id) {
            $query->where('material_id', $this->material_id);
        }

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('reference_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('material', fn($mq) => $mq->where('name', 'like', '%' . $this->search . '%'));
            });
        }

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        if ($this->period === 'today') {
            $query->whereDate('created_at', now());
        } elseif ($this->period === 'weekly') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->period === 'monthly') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        } elseif ($this->period === 'yearly') {
            $query->whereYear('created_at', now()->year);
        }

        // Calculate Summary for selected period & material
        $openingBalance = 0;
        $totalIn = 0;
        $totalOut = 0;

        if ($this->material_id) {
            $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : null;
            $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : null;

            if ($start) {
                $openingTrx = InventoryTransaction::where('material_id', $this->material_id)
                    ->where('created_at', '<', $start)
                    ->selectRaw('SUM(volume_masuk) - SUM(volume_keluar) as balance')
                    ->first();
                $openingBalance = (float)($openingTrx->balance ?? 0);
            }

            $summaryTrx = InventoryTransaction::where('material_id', $this->material_id)
                ->when($start, fn($q) => $q->where('created_at', '>=', $start))
                ->when($end, fn($q) => $q->where('created_at', '<=', $end))
                ->selectRaw('SUM(volume_masuk) as total_in, SUM(volume_keluar) as total_out')
                ->first();
            
            $totalIn = (float)($summaryTrx->total_in ?? 0);
            $totalOut = (float)($summaryTrx->total_out ?? 0);
        }

        return [
            'reportData' => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate(50),
            'allMaterials' => Material::orderBy('name', 'asc')->get(),
            'openingBalance' => $openingBalance,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'finalBalance' => $openingBalance + $totalIn - $totalOut,
            'selectedMaterial' => $this->material_id ? Material::find($this->material_id) : null
        ];

    }

    public function exportExcel()
    {
        $filename = 'laporan-inventory-' . $this->period . '-' . now()->format('YmdHis') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\InventoryReportExport($this->period, $this->material_id, $this->search, $this->startDate, $this->endDate, $this->type), 
            $filename
        );
    }
};
?>

<div>
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
        <div>
            <div class="flex items-center gap-4">
                @if($material_id || $startDate || $endDate || $search || $type != '')
                <a href="/dashboard/laporan" wire:navigate class="flex-none p-2.5 bg-white rounded-2xl border border-warm/60 text-gray-400 hover:text-accent hover:border-accent transition-all group shadow-sm active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                @endif
                <div class="min-w-0">
                    <h1 class="text-2xl md:text-3xl font-black text-gray-900 tracking-tight truncate">Laporan Inventory</h1>
                    <p class="text-xs md:text-sm text-gray-500 mt-0.5 truncate">Pantau mutasi barang masuk, keluar, dan sisa stok secara real-time.</p>
                </div>
            </div>
        </div>


        <div class="flex flex-wrap items-center gap-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:flex gap-3 w-full lg:w-auto">
                <select wire:model.live="material_id" class="w-full lg:w-auto bg-white rounded-2xl px-4 py-2.5 text-sm font-bold border border-warm/60 focus:ring-4 focus:ring-accent/10 focus:border-accent outline-none transition-all shadow-sm">
                    <option value="">Semua Material</option>
                    @foreach($allMaterials as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="type" class="w-full lg:w-auto bg-white rounded-2xl px-4 py-2.5 text-sm font-bold border border-warm/60 focus:ring-4 focus:ring-accent/10 focus:border-accent outline-none transition-all shadow-sm">
                    <option value="">Semua Transaksi</option>
                    <option value="in">Barang Masuk</option>
                    <option value="out">Barang Keluar</option>
                </select>
                <select wire:model.live="period" class="w-full lg:w-auto bg-white rounded-2xl px-4 py-2.5 text-sm font-bold border border-warm/60 focus:ring-4 focus:ring-accent/10 focus:border-accent outline-none transition-all shadow-sm">
                    <option value="today">Hari Ini</option>
                    <option value="weekly">Minggu Ini</option>
                    <option value="monthly">Bulan Ini</option>
                    <option value="yearly">Tahun Ini</option>
                    <option value="all">Semua Waktu</option>
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                <div class="flex-1 lg:flex-none flex items-center gap-2 bg-white rounded-2xl px-4 py-2 border border-warm/60 shadow-sm min-w-[280px]">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest whitespace-nowrap">Periode:</label>
                    <input type="date" wire:model.live="startDate" class="text-xs text-gray-600 outline-none w-full bg-transparent font-bold">
                    <span class="text-gray-300">/</span>
                    <input type="date" wire:model.live="endDate" class="text-xs text-gray-600 outline-none w-full bg-transparent font-bold">
                    <button wire:click="$set('startDate', ''); $set('endDate', '')" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <button wire:click="exportExcel" class="w-full sm:w-auto bg-emerald-500 text-white px-6 py-2.5 rounded-2xl text-sm font-black flex items-center justify-center gap-2 hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-500/20 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Excel
                </button>
                <button onclick="printReport()" class="w-full sm:w-auto bg-gray-900 text-white px-6 py-2.5 rounded-2xl text-sm font-black flex items-center justify-center gap-2 hover:bg-black transition-all shadow-lg shadow-gray-900/20 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak
                </button>

            </div>
        </div>
    </div>
    @if($material_id)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 animate-fade-in">
        <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-warm/60 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2">Saldo Awal</span>
            <div class="flex items-baseline gap-1">
                <span class="text-2xl font-black text-gray-900">{{ number_format($openingBalance, 0, ',', '.') }}</span>
                <span class="text-[10px] font-bold text-gray-400 uppercase">{{ $selectedMaterial->unit ?? '' }}</span>
            </div>
            <p class="text-[9px] text-gray-400 mt-2 font-medium">
                @if($startDate)
                    Per tanggal {{ Carbon::parse($startDate)->format('d/m/Y') }}
                @else
                    Sejak awal sistem
                @endif
            </p>
        </div>


        <div class="bg-emerald-50/50 p-6 rounded-[2rem] shadow-sm border border-emerald-100 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 text-emerald-600">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </div>
            <span class="text-[10px] font-black text-emerald-600/60 uppercase tracking-widest block mb-2">Total Masuk (+)</span>
            <div class="flex items-baseline gap-1">
                <span class="text-2xl font-black text-emerald-700">{{ number_format($totalIn, 0, ',', '.') }}</span>
                <span class="text-[10px] font-bold text-emerald-600/40 uppercase">{{ $selectedMaterial->unit ?? '' }}</span>
            </div>
            <p class="text-[9px] text-emerald-600/60 mt-2 font-medium">Selama periode terpilih</p>
        </div>

        <div class="bg-red-50/50 p-6 rounded-[2rem] shadow-sm border border-red-100 relative overflow-hidden group hover:shadow-md transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-10 text-red-600">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
            </div>
            <span class="text-[10px] font-black text-red-600/60 uppercase tracking-widest block mb-2">Total Keluar (-)</span>
            <div class="flex items-baseline gap-1">
                <span class="text-2xl font-black text-red-700">{{ number_format($totalOut, 0, ',', '.') }}</span>
                <span class="text-[10px] font-bold text-red-600/40 uppercase">{{ $selectedMaterial->unit ?? '' }}</span>
            </div>
            <p class="text-[9px] text-red-600/60 mt-2 font-medium">Selama periode terpilih</p>
        </div>

        <div class="bg-accent p-6 rounded-[2rem] shadow-lg shadow-accent/20 relative overflow-hidden group hover:shadow-xl transition-all">
            <div class="absolute top-0 right-0 p-4 opacity-20 text-white">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <span class="text-[10px] font-black text-white/60 uppercase tracking-widest block mb-2">Saldo Akhir</span>
            <div class="flex items-baseline gap-1">
                <span class="text-2xl font-black text-white">{{ number_format($finalBalance, 0, ',', '.') }}</span>
                <span class="text-[10px] font-bold text-white/60 uppercase">{{ $selectedMaterial->unit ?? '' }}</span>
            </div>
            <p class="text-[9px] text-white/60 mt-2 font-medium">Hingga akhir periode</p>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-[2.5rem] shadow-card ring-1 ring-accent/5 overflow-hidden border border-warm/20">
        <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-warm scrollbar-track-base">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-warm/30 border-b border-warm/60">
                        <th class="text-left px-6 py-5 font-black text-gray-500 uppercase tracking-tighter text-[10px]">Tanggal</th>
                        <th class="text-left px-6 py-5 font-black text-gray-500 uppercase tracking-tighter text-[10px]">No. Referensi</th>
                        <th class="text-left px-6 py-5 font-black text-gray-500 uppercase tracking-tighter text-[10px]">Jenis Material</th>
                        <th class="text-center px-6 py-5 font-black text-gray-500 uppercase tracking-tighter text-[10px] bg-emerald-50/30">Masuk</th>
                        <th class="text-center px-6 py-5 font-black text-gray-500 uppercase tracking-tighter text-[10px] bg-red-50/30">Keluar</th>
                        <th class="text-center px-6 py-5 font-black text-gray-900 uppercase tracking-tighter text-[10px] bg-blue-50/30">Saldo</th>
                        <th class="text-center px-6 py-5 font-black text-gray-500 uppercase tracking-tighter text-[10px]">Satuan</th>
                        <th class="text-left px-6 py-5 font-black text-gray-500 uppercase tracking-tighter text-[10px]">No POL</th>
                        <th class="text-left px-6 py-5 font-black text-gray-500 uppercase tracking-tighter text-[10px]">Lokasi</th>
                        <th class="text-right px-6 py-5 font-black text-gray-500 uppercase tracking-tighter text-[10px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm/40">
                    @foreach($reportData as $trx)
                    <tr class="hover:bg-base/40 transition-colors group">
                        <td class="px-6 py-4 text-gray-600 whitespace-nowrap font-medium">{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 font-mono text-[11px] font-black text-accent uppercase tracking-tight">
                            {{ $trx->reference_number ?: '-' }}
                        </td>
                        <td class="px-6 py-4 font-black text-gray-800">{{ $trx->material->name }}</td>
                        <td class="px-6 py-4 text-center text-emerald-600 font-black bg-emerald-50/10">
                            {{ $trx->volume_masuk > 0 ? '+ ' . (float)$trx->volume_masuk : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center text-red-600 font-black bg-red-50/10">
                            {{ $trx->volume_keluar > 0 ? '- ' . (float)$trx->volume_keluar : '-' }}
                        </td>
                        <td class="px-6 py-4 text-center font-black text-blue-700 bg-blue-50/10 text-base">
                            {{ (float)$trx->balance_after }}
                        </td>
                        <td class="px-6 py-4 text-center text-gray-500 font-bold text-[10px] uppercase">{{ $trx->material->unit }}</td>
                        <td class="px-6 py-4 font-mono font-black uppercase text-gray-700">
                            {{ $trx->deliveryOrder->no_polisi ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-gray-700 font-bold text-[11px]">
                            {{ $trx->deliveryOrder->lokasi ?? ($trx->description ?: 'Restock') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                @if($trx->delivery_order_id)
                                    <button wire:click="$dispatch('show-sj-detail', { id: {{ $trx->delivery_order_id }} })" class="inline-flex items-center gap-2 px-4 py-2 bg-accent/5 hover:bg-accent text-accent hover:text-white rounded-xl text-[10px] font-black uppercase transition-all shadow-sm active:scale-95">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        SJ Detail
                                    </button>
                                @else
                                    <button wire:click="$dispatch('show-trx-detail', { id: {{ $trx->id }} })" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white rounded-xl text-[10px] font-black uppercase transition-all shadow-sm active:scale-95">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        In Detail
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-6 bg-base/10 border-t border-warm/40">
            {{ $reportData->links() }}
        </div>
    </div>

    {{-- Print Area --}}
    <div id="print-area" class="hidden print-target bg-white text-black text-xs" style="font-family: 'Times New Roman', serif;">
        <img src="{{ asset('assets/kop.png') }}" class="w-full h-auto mb-8">
        
        <div class="text-center mb-8">
            <h1 class="text-xl font-bold uppercase underline">LAPORAN MUTASI BARANG</h1>
            @if($selectedMaterial)
                <p class="text-lg font-bold mt-1 uppercase">{{ $selectedMaterial->name }}</p>
            @endif
            <p class="text-sm mt-1">Periode: {{ $startDate ? Carbon::parse($startDate)->format('d/m/Y') : '-' }} s/d {{ $endDate ? Carbon::parse($endDate)->format('d/m/Y') : Carbon::now()->format('d/m/Y') }}</p>
        </div>

        @if($selectedMaterial)
        <div class="grid grid-cols-4 gap-4 mb-8 border border-black p-4">
            <div class="text-center border-r border-black">
                <p class="text-[9px] font-bold uppercase mb-1">Saldo Awal</p>
                <p class="text-lg font-black">{{ number_format($openingBalance, 0, ',', '.') }}</p>
            </div>
            <div class="text-center border-r border-black">
                <p class="text-[9px] font-bold uppercase mb-1">Total Masuk (+)</p>
                <p class="text-lg font-black">{{ number_format($totalIn, 0, ',', '.') }}</p>
            </div>
            <div class="text-center border-r border-black">
                <p class="text-[9px] font-bold uppercase mb-1">Total Keluar (-)</p>
                <p class="text-lg font-black">{{ number_format($totalOut, 0, ',', '.') }}</p>
            </div>
            <div class="text-center">
                <p class="text-[9px] font-bold uppercase mb-1">Saldo Akhir</p>
                <p class="text-lg font-black">{{ number_format($finalBalance, 0, ',', '.') }}</p>
            </div>
        </div>
        @endif

        <table class="w-full border-collapse border border-black">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-black px-2 py-2 text-center text-[9px] uppercase font-bold w-6">No</th>
                    <th class="border border-black px-2 py-2 text-left text-[9px] uppercase font-bold">Tanggal</th>
                    <th class="border border-black px-2 py-2 text-left text-[9px] uppercase font-bold">No. Ref</th>
                    <th class="border border-black px-2 py-2 text-left text-[9px] uppercase font-bold">Uraian / Lokasi</th>
                    <th class="border border-black px-2 py-2 text-right text-[9px] uppercase font-bold">Masuk</th>
                    <th class="border border-black px-2 py-2 text-right text-[9px] uppercase font-bold">Keluar</th>
                    <th class="border border-black px-2 py-2 text-right text-[9px] uppercase font-bold">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $index => $trx)
                <tr>
                    <td class="border border-black px-2 py-1.5 text-center">{{ $index + 1 }}</td>
                    <td class="border border-black px-2 py-1.5">{{ $trx->created_at->format('d/m/Y') }}</td>
                    <td class="border border-black px-2 py-1.5">{{ $trx->reference_number ?: '-' }}</td>
                    <td class="border border-black px-2 py-1.5 text-[8px]">
                        {{ $trx->deliveryOrder->lokasi ?? ($trx->supplier ?: ($trx->description ?: 'Mutasi Stok')) }}
                        @if($trx->deliveryOrder && $trx->deliveryOrder->no_polisi)
                            ({{ $trx->deliveryOrder->no_polisi }})
                        @endif
                    </td>
                    <td class="border border-black px-2 py-1.5 text-right font-bold">{{ $trx->volume_masuk > 0 ? number_format($trx->volume_masuk, 0, ',', '.') : '-' }}</td>
                    <td class="border border-black px-2 py-1.5 text-right font-bold">{{ $trx->volume_keluar > 0 ? number_format($trx->volume_keluar, 0, ',', '.') : '-' }}</td>
                    <td class="border border-black px-2 py-1.5 text-right font-black bg-gray-50">{{ number_format($trx->balance_after, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-12 flex justify-end pr-8">
            <div class="text-center w-64">
                <p class="text-sm">Jakarta, {{ Carbon::now()->translatedFormat('d F Y') }}</p>
                <p class="text-sm font-bold mt-1">Petugas Gudang / Admin,</p>
                <div class="h-24"></div>
                <p class="text-sm font-bold underline uppercase">{{ auth()->user()->name }}</p>
            </div>
        </div>
    </div>

    <script>
        window.printReport = function() {
            const originalTitle = document.title;
            const materialName = "{{ $selectedMaterial->name ?? 'Inventory' }}";
            document.title = "LAPORAN MUTASI - " + materialName.toUpperCase();

            document.getElementById('print-area').classList.add('print-active');
            window.print();
            document.getElementById('print-area').classList.remove('print-active');
            
            document.title = originalTitle;
        }
    </script>


    <style>
        .print-target { display: none; }
        
        @media print {
            @page {
                size: portrait;
                margin: 1.5cm;
            }
            
            body * { visibility: hidden; }
            .print-active, .print-active * { visibility: visible; }
            .print-active {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                display: block !important;
                padding: 0 !important;
            }
            .shadow-sm, .shadow-md, .shadow-lg, .shadow-xl, .shadow-2xl {
                box-shadow: none !important;
            }
            .animate-fade-in {
                animation: none !important;
            }
        }
    </style>
</div>

