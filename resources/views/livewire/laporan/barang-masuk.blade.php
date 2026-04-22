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

    public function with()
    {
        $query = InventoryTransaction::with(['material', 'user', 'material.category'])
            ->latest();

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
        
        @if($search)
        <div class="px-4 py-2 bg-accent/10 border border-accent/20 rounded-xl flex items-center gap-3">
            <span class="text-[10px] font-black text-accent uppercase tracking-widest">Filter Aktif:</span>
            <span class="text-xs font-bold text-gray-700">"{{ $search }}"</span>
            <button wire:click="$set('search', '')" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        @endif
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-sm ring-1 ring-accent/5 overflow-hidden">
        <div class="p-8 border-b border-gray-50 flex flex-col md:flex-row justify-between gap-4 bg-white">
            <div class="relative max-w-sm w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari No. Surat, Barang, atau Supplier..." class="w-full bg-base rounded-2xl pl-11 pr-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
            </div>

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
                                <div class="w-7 h-7 rounded-full bg-accent/10 flex items-center justify-center text-[10px] font-black text-accent uppercase">
                                    {{ substr($t->user->name ?? '?', 0, 1) }}
                                </div>
                                <span class="text-xs font-bold text-gray-700">{{ $t->user->name ?? 'System' }}</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-8 py-12 text-center">
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
