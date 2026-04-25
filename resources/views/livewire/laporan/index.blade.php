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
            new \App\Exports\InventoryReportExport($this->period, $this->material_id, $this->search, $this->startDate, $this->endDate), 
            $filename
        );
    }
};
?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Inventory</h1>
            <p class="text-sm text-gray-500 mt-0.5">Pantau mutasi barang masuk, keluar, dan sisa stok.</p>
        </div>
        <div class="flex gap-2">
            <select wire:model.live="material_id" class="bg-white rounded-xl px-4 py-2 text-sm font-bold border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
                <option value="">Semua Material</option>
                @foreach($allMaterials as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="period" class="bg-white rounded-xl px-4 py-2 text-sm font-bold border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
                <option value="today">Hari Ini</option>
                <option value="weekly">Minggu Ini</option>
                <option value="monthly">Bulan Ini</option>
                <option value="yearly">Tahun Ini</option>
                <option value="all">Semua Waktu</option>
            </select>
            <div class="flex items-center gap-2 bg-white rounded-xl px-4 py-1.5 border border-warm/60 shadow-sm">
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Periode:</label>
                <input type="date" wire:model.live="startDate" class="text-xs text-gray-600 outline-none w-28 bg-transparent">
                <span class="text-gray-300">/</span>
                <input type="date" wire:model.live="endDate" class="text-xs text-gray-600 outline-none w-28 bg-transparent">
                <button wire:click="$set('startDate', ''); $set('endDate', '')" class="text-[9px] font-bold text-red-500 hover:text-red-600 uppercase ml-1">Reset</button>
            </div>
            <button wire:click="exportExcel" class="bg-emerald-500 text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export
            </button>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] shadow-card ring-1 ring-accent/5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead>
                    <tr class="bg-warm/40 border-b border-warm/60">
                        <th class="text-left px-4 py-4 font-bold text-gray-500 uppercase">Tanggal</th>
                        <th class="text-left px-4 py-4 font-bold text-gray-500 uppercase">No. Referensi</th>
                        <th class="text-left px-4 py-4 font-bold text-gray-500 uppercase">Jenis Material</th>
                        <th class="text-center px-4 py-4 font-bold text-gray-500 uppercase bg-emerald-50/50">Volume Masuk</th>
                        <th class="text-center px-4 py-4 font-bold text-gray-500 uppercase bg-red-50/50">Volume Keluar</th>
                        <th class="text-center px-4 py-4 font-bold text-gray-900 uppercase bg-blue-50/50">Sisa (Saldo)</th>
                        <th class="text-center px-4 py-4 font-bold text-gray-500 uppercase">Satuan</th>
                        <th class="text-left px-4 py-4 font-bold text-gray-500 uppercase">No POL</th>
                        <th class="text-left px-4 py-4 font-bold text-gray-500 uppercase">Lokasi</th>
                        <th class="text-right px-4 py-4 font-bold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm/40">
                    @foreach($reportData as $trx)
                    <tr class="hover:bg-base/60 transition-colors">
                        <td class="px-4 py-3.5 text-gray-600">{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3.5 font-mono text-xs font-bold text-accent uppercase">
                            {{ $trx->reference_number ?: '-' }}
                        </td>
                        <td class="px-4 py-3.5 font-bold text-gray-800">{{ $trx->material->name }}</td>
                        <td class="px-4 py-3.5 text-center text-emerald-600 font-bold bg-emerald-50/20">
                            {{ $trx->volume_masuk > 0 ? '+ ' . (float)$trx->volume_masuk : '-' }}
                        </td>
                        <td class="px-4 py-3.5 text-center text-red-600 font-bold bg-red-50/20">
                            {{ $trx->volume_keluar > 0 ? '- ' . (float)$trx->volume_keluar : '-' }}
                        </td>
                        <td class="px-4 py-3.5 text-center font-black text-blue-700 bg-blue-50/20">
                            {{ (float)$trx->balance_after }}
                        </td>
                        <td class="px-4 py-3.5 text-center text-gray-500 font-medium">{{ $trx->material->unit }}</td>
                        <td class="px-4 py-3.5 font-mono font-bold uppercase text-gray-700">
                            {{ $trx->deliveryOrder->no_polisi ?? '-' }}
                        </td>
                        <td class="px-4 py-3.5 text-gray-700 font-medium">
                            {{ $trx->deliveryOrder->lokasi ?? ($trx->description ?: 'Restock') }}
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            @if($trx->delivery_order_id)
                                <button wire:click="$dispatch('show-sj-detail', { id: {{ $trx->delivery_order_id }} })" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-accent/5 hover:bg-accent text-accent hover:text-white rounded-lg text-[9px] font-black uppercase transition-all shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Detail SJ
                                </button>
                            @else
                                <button wire:click="$dispatch('show-trx-detail', { id: {{ $trx->id }} })" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white rounded-lg text-[9px] font-black uppercase transition-all shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Detail Masuk
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-8 py-6 bg-base/20 border-t border-gray-50">
            {{ $reportData->links() }}
        </div>
    </div>
</div>
