<?php

use App\Models\InventoryTransaction;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

new class extends Component {
    use WithPagination;

    #[Url]
    public $search = '';
    public $perPage = 10;
    public $startDate = '';
    public $endDate = '';

    protected $listeners = ['global-search' => 'handleGlobalSearch'];

    public function handleGlobalSearch($search)
    {
        $this->search = $search;
        $this->resetPage();
    }

    public function with()
    {
        $query = InventoryTransaction::with(['material', 'user', 'material.category'])
            ->latest();

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
                  ->orWhere('note', 'like', '%' . $this->search . '%')
                  ->orWhereHas('material', function($mq) {
                      $mq->where('name', 'like', '%' . $this->search . '%');
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
            new \App\Exports\TransactionHistoryExport($this->search, $this->startDate, $this->endDate), 
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
            <div class="flex items-center gap-2 bg-white rounded-2xl px-4 py-2 ring-1 ring-accent/5 shadow-sm">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Filter:</label>
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
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Material</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Sumber / Tujuan</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Volume</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-center">Bukti</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Petugas</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($transactions as $t)
                    <tr class="hover:bg-base/30 transition-colors group">
                        <td class="px-8 py-5">
                            <span class="text-xs font-bold text-gray-700 block">{{ $t->created_at->format('d/m/Y') }}</span>
                            <span class="text-[10px] text-gray-400">{{ $t->created_at->format('H:i') }} WIB</span>
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
                                <span class="text-sm font-black text-gray-800">{{ $t->reference_number ?: '-' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-800">{{ $t->material->name }}</span>
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">{{ $t->material->category->name ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            @if($t->type === 'in')
                                <span class="text-xs text-gray-600 font-medium">Dari: <span class="font-bold">{{ $t->supplier ?: 'Restock Internal' }}</span></span>
                            @else
                                <span class="text-xs text-gray-600 font-medium">Tujuan: <span class="font-bold text-red-500">{{ $t->deliveryOrder->lokasi ?? 'Pengeluaran Barang' }}</span></span>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-right">
                            <div class="flex flex-col items-end">
                                <span @class([
                                    'text-base font-black',
                                    'text-emerald-600' => $t->type === 'in',
                                    'text-red-500' => $t->type === 'out'
                                ])>
                                    {{ $t->type === 'in' ? '+' : '-' }}{{ (float)($t->type === 'in' ? $t->volume_masuk : $t->volume_keluar) }}
                                </span>
                                <span class="text-[10px] font-bold text-gray-400 uppercase">{{ $t->material->unit }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex justify-center">
                                @if($t->image)
                                    <a href="{{ Storage::url($t->image) }}" target="_blank" class="group/img relative">
                                        <img src="{{ Storage::url($t->image) }}" class="w-10 h-10 rounded-lg object-cover ring-2 ring-white shadow-sm group-hover/img:scale-110 transition-transform">
                                        <div class="absolute inset-0 bg-gray-900/40 rounded-lg opacity-0 group-hover/img:opacity-100 flex items-center justify-center transition-opacity">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </div>
                                    </a>
                                @else
                                    <span class="text-[10px] font-bold text-gray-300 italic uppercase">No Photo</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-700">{{ $t->user->name ?? 'System' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-right">
                            @if($t->delivery_order_id)
                                <button wire:click="$dispatch('show-sj-detail', { id: {{ $t->delivery_order_id }} })" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-accent/5 hover:bg-accent text-accent hover:text-white rounded-lg text-[9px] font-black uppercase transition-all shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Detail SJ
                                </button>
                            @else
                                <button wire:click="$dispatch('show-trx-detail', { id: {{ $t->id }} })" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white rounded-lg text-[9px] font-black uppercase transition-all shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Detail Masuk
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-8 py-12 text-center">
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
</div>
