<?php
use App\Models\InventoryTransaction;
use App\Models\Material;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Livewire\Attributes\Url;

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

        return [
            'reportData' => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate(50),
            'allMaterials' => Material::orderBy('name', 'asc')->get(),
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
            <h1 class="text-2xl md:text-3xl font-black text-gray-900 tracking-tight">Laporan Inventory</h1>
            <p class="text-sm text-gray-500 mt-1">Pantau mutasi barang masuk, keluar, dan sisa stok secara real-time.</p>
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
            </div>
        </div>
    </div>

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
</div>
