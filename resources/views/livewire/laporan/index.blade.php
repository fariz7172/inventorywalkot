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
        
        $applyKecamatanFilter = function($q) {
            if (auth()->check() && auth()->user()->kecamatan_id) {
                $userKecId = auth()->user()->kecamatan_id;
                $lokasiKecamatan = \App\Models\Rab::where('kecamatan_id', $userKecId)->pluck('lokasi');
                $q->where(function($subQ) use ($userKecId, $lokasiKecamatan) {
                    $subQ->whereHas('user', function($uq) use ($userKecId) {
                        $uq->where('kecamatan_id', $userKecId);
                    })->orWhereHas('deliveryOrder', function($dq) use ($lokasiKecamatan) {
                        $dq->whereIn('lokasi', $lokasiKecamatan);
                    });
                });
            }
            return $q;
        };

        $applyKecamatanFilter($query);
        
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

        // Calculate Summary for all materials in the period
        $materialsSummary = collect();
        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : null;
        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : null;

        // If specific material is selected, we already have calculations. 
        // But for the print summary table, we need it for all relevant materials.
        $targetMaterials = $this->material_id ? Material::where('id', $this->material_id)->get() : Material::all();

        foreach ($targetMaterials as $m) {
            $op = 0;
            if ($start) {
                $qOp = InventoryTransaction::where('material_id', $m->id)
                    ->where('created_at', '<', $start)
                    ->selectRaw('SUM(volume_masuk) - SUM(volume_keluar) as balance');
                $applyKecamatanFilter($qOp);
                $opTrx = $qOp->first();
                $op = (float)($opTrx->balance ?? 0);
            }

            $qSum = InventoryTransaction::where('material_id', $m->id)
                ->when($start, fn($q) => $q->where('created_at', '>=', $start))
                ->when($end, fn($q) => $q->where('created_at', '<=', $end))
                ->selectRaw('SUM(volume_masuk) as total_in, SUM(volume_keluar) as total_out');
            $applyKecamatanFilter($qSum);
            $sumTrx = $qSum->first();
            
            $tin = (float)($sumTrx->total_in ?? 0);
            $tout = (float)($sumTrx->total_out ?? 0);

            // Only include in summary if there is balance or activity
            if ($op != 0 || $tin != 0 || $tout != 0) {
                $materialsSummary->push((object)[
                    'name' => $m->name,
                    'unit' => $m->unit,
                    'opening' => $op,
                    'in' => $tin,
                    'out' => $tout,
                    'final' => $op + $tin - $tout
                ]);
            }
        }

        // Keep existing single-material variables for backward compatibility in view
        $openingBalance = 0;
        $totalIn = 0;
        $totalOut = 0;
        if ($this->material_id) {
            $sm = $materialsSummary->first();
            if ($sm) {
                $openingBalance = $sm->opening;
                $totalIn = $sm->in;
                $totalOut = $sm->out;
            }
        }

        return [
            'reportData' => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate(50),
            'allMaterials' => Material::orderBy('name', 'asc')->get(),
            'materialsSummary' => $materialsSummary,
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
                    <p class="text-xs md:text-sm text-gray-500 mt-0.5 truncate">Pantau mutasi barang masuk, keluar, dan sisa stok.</p>
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
                        <th class="text-left px-4 py-4 font-black text-gray-500 uppercase tracking-tighter text-[10px]">Tanggal</th>
                        <th class="text-left px-4 py-4 font-black text-gray-500 uppercase tracking-tighter text-[10px]">No. Referensi</th>
                        <th class="text-left px-4 py-4 font-black text-gray-500 uppercase tracking-tighter text-[10px]">Jenis Material</th>
                        <th class="text-center px-4 py-4 font-black text-gray-500 uppercase tracking-tighter text-[10px] bg-emerald-50/30">Masuk</th>
                        <th class="text-center px-4 py-4 font-black text-gray-500 uppercase tracking-tighter text-[10px] bg-red-50/30">Keluar</th>
                        <th class="text-center px-4 py-4 font-black text-gray-900 uppercase tracking-tighter text-[10px] bg-blue-50/30">Saldo</th>
                        <th class="text-center px-4 py-4 font-black text-gray-500 uppercase tracking-tighter text-[10px]">Satuan</th>
                        <th class="text-left px-4 py-4 font-black text-gray-500 uppercase tracking-tighter text-[10px]">No POL</th>
                        <th class="text-left px-4 py-4 font-black text-gray-500 uppercase tracking-tighter text-[10px]">Lokasi</th>
                        <th class="text-right px-4 py-4 font-black text-gray-500 uppercase tracking-tighter text-[10px]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm/40">
                    @if($material_id && $reportData->onFirstPage())
                    <tr class="bg-blue-50/10 font-bold italic">
                        <td class="px-4 py-3 text-gray-400 whitespace-nowrap">-</td>
                        <td class="px-4 py-3 font-mono text-[10px] text-gray-400 uppercase">INITIAL</td>
                        <td class="px-4 py-3 text-gray-500 uppercase tracking-widest text-[10px]">SALDO AWAL</td>
                        <td class="px-4 py-3 text-center text-gray-400">-</td>
                        <td class="px-4 py-3 text-center text-gray-400">-</td>
                        <td class="px-4 py-3 text-center font-black text-blue-700 bg-blue-50/20">
                            {{ number_format($openingBalance, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-400 font-bold text-[10px] uppercase">{{ $selectedMaterial->unit ?? '' }}</td>
                        <td class="px-4 py-3 text-gray-400">-</td>
                        <td class="px-4 py-3 text-gray-400">-</td>
                        <td class="px-4 py-3 text-right"></td>
                    </tr>
                    @endif

                    @foreach($reportData as $trx)
                    <tr class="hover:bg-base/40 transition-colors group">
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap font-medium">{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 font-mono text-[11px] font-black text-accent uppercase tracking-tight">
                            {{ $trx->reference_number ?: '-' }}
                        </td>
                        <td class="px-4 py-3 font-black text-gray-800">{{ $trx->material->name }}</td>
                        <td class="px-4 py-3 text-center text-emerald-600 font-black bg-emerald-50/10">
                            {{ $trx->volume_masuk > 0 ? '+ ' . (float)$trx->volume_masuk : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-red-600 font-black bg-red-50/10">
                            {{ $trx->volume_keluar > 0 ? '- ' . (float)$trx->volume_keluar : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center font-black text-blue-700 bg-blue-50/10 text-base">
                            {{ (float)$trx->balance_after }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-500 font-bold text-[10px] uppercase">{{ $trx->material->unit }}</td>
                        <td class="px-4 py-3 font-mono font-black uppercase text-gray-700">
                            {{ $trx->deliveryOrder->no_polisi ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 font-bold text-[11px]">
                            {{ $trx->deliveryOrder->lokasi ?? ($trx->description ?: 'Restock') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                @if($trx->delivery_order_id)
                                    <button wire:click="$dispatch('show-sj-detail', { id: {{ $trx->delivery_order_id }} })" class="inline-flex items-center gap-2 px-3 py-1.5 bg-accent/5 hover:bg-accent text-accent hover:text-white rounded-xl text-[10px] font-black uppercase transition-all shadow-sm active:scale-95">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        SJ Detail
                                    </button>
                                @else
                                    <button wire:click="$dispatch('show-trx-detail', { id: {{ $trx->id }} })" class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white rounded-xl text-[10px] font-black uppercase transition-all shadow-sm active:scale-95">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        {{-- Material Summary Table (Like Laporan Saldo) --}}
        <div class="mb-8">
            <h2 class="text-sm font-bold uppercase mb-2">I. RINGKASAN SALDO & MUTASI</h2>
            <table class="w-full border-collapse border border-black text-[9px]">
                <thead>
                    <tr class="bg-gray-100 font-bold">
                        <th class="border border-black px-2 py-1 text-center w-8">NO</th>
                        <th class="border border-black px-2 py-1 text-left">NAMA MATERIAL</th>
                        <th class="border border-black px-2 py-1 text-center w-16">SATUAN</th>
                        <th class="border border-black px-2 py-1 text-right w-24">SALDO AWAL</th>
                        <th class="border border-black px-2 py-1 text-right w-24">MASUK (+)</th>
                        <th class="border border-black px-2 py-1 text-right w-24">KELUAR (-)</th>
                        <th class="border border-black px-2 py-1 text-right w-24">SALDO AKHIR</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materialsSummary as $index => $summary)
                    <tr>
                        <td class="border border-black px-2 py-1 text-center">{{ $index + 1 }}</td>
                        <td class="border border-black px-2 py-1 font-bold">{{ $summary->name }}</td>
                        <td class="border border-black px-2 py-1 text-center uppercase">{{ $summary->unit }}</td>
                        <td class="border border-black px-2 py-1 text-right">{{ number_format($summary->opening, 0, ',', '.') }}</td>
                        <td class="border border-black px-2 py-1 text-right text-emerald-700 font-bold">{{ number_format($summary->in, 0, ',', '.') }}</td>
                        <td class="border border-black px-2 py-1 text-right text-red-700 font-bold">{{ number_format($summary->out, 0, ',', '.') }}</td>
                        <td class="border border-black px-2 py-1 text-right font-black bg-gray-50">{{ number_format($summary->final, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mb-4">
            <h2 class="text-sm font-bold uppercase mb-2">II. RINCIAN TRANSAKSI</h2>
            <table class="w-full border-collapse border border-black">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-black px-2 py-2 text-center text-[9px] uppercase font-bold w-6">No</th>
                        <th class="border border-black px-2 py-2 text-left text-[9px] uppercase font-bold">Tanggal</th>
                        <th class="border border-black px-2 py-2 text-left text-[9px] uppercase font-bold">No. Ref</th>
                        <th class="border border-black px-2 py-2 text-left text-[9px] uppercase font-bold">Material</th>
                        <th class="border border-black px-2 py-2 text-left text-[9px] uppercase font-bold">Uraian / Lokasi</th>
                        <th class="border border-black px-2 py-2 text-right text-[9px] uppercase font-bold">Masuk</th>
                        <th class="border border-black px-2 py-2 text-right text-[9px] uppercase font-bold">Keluar</th>
                        <th class="border border-black px-2 py-2 text-right text-[9px] uppercase font-bold">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @if($material_id)
                    <tr class="bg-gray-50 font-bold">
                        <td class="border border-black px-2 py-1.5 text-center">-</td>
                        <td class="border border-black px-2 py-1.5">-</td>
                        <td class="border border-black px-2 py-1.5 font-mono text-[8px]">INITIAL</td>
                        <td class="border border-black px-2 py-1.5 uppercase">{{ $selectedMaterial->name }}</td>
                        <td class="border border-black px-2 py-1.5 italic text-[9px]">SALDO AWAL PERIODE</td>
                        <td class="border border-black px-2 py-1.5 text-right">-</td>
                        <td class="border border-black px-2 py-1.5 text-right">-</td>
                        <td class="border border-black px-2 py-1.5 text-right font-black">{{ number_format($openingBalance, 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    @foreach($reportData as $index => $trx)
                    <tr>
                        <td class="border border-black px-2 py-1.5 text-center">{{ $index + 1 }}</td>
                        <td class="border border-black px-2 py-1.5">{{ $trx->created_at->format('d/m/Y') }}</td>
                        <td class="border border-black px-2 py-1.5 font-mono text-[8px] uppercase">{{ $trx->reference_number ?: '-' }}</td>
                        <td class="border border-black px-2 py-1.5 font-bold text-[8px] uppercase">{{ $trx->material->name }}</td>
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
        </div>

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
            const el = document.getElementById('print-area');
            if (!el) return;

            const originalTitle = document.title;
            const materialName = "{{ $selectedMaterial->name ?? '' }}";
            document.title = "" + materialName.toUpperCase();

            // Clone element to body to avoid nesting display issues
            const printClone = el.cloneNode(true);
            printClone.id = 'temp-print-area';
            printClone.classList.remove('hidden');
            printClone.classList.add('print-active');
            document.body.appendChild(printClone);

            window.print();
            
            document.body.removeChild(printClone);
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
            
            body > *:not(.print-active) {
                display: none !important;
            }

            .print-active {
                display: block !important;
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                padding: 0 !important;
                background: white !important;
            }
            
            .shadow-sm, .shadow-md, .shadow-lg, .shadow-xl, .shadow-2xl {
                box-shadow: none !important;
            }
        }
    </style>
</div>
