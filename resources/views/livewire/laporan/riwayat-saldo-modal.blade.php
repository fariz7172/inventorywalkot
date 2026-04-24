<?php

use App\Models\InventoryTransaction;
use App\Models\Material;
use Livewire\Volt\Component;

new class extends Component {
    public $isOpen  = false;
    public $material = null;
    public $transactions = [];
    public $startDate = '';
    public $endDate   = '';

    protected $listeners = ['show-riwayat-saldo' => 'showRiwayat'];

    public function showRiwayat($material_id, $start_date = '', $end_date = '')
    {
        $this->startDate = $start_date;
        $this->endDate   = $end_date;

        $this->material = Material::find($material_id);

        $query = InventoryTransaction::with('deliveryOrder')
            ->where('material_id', $material_id);

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        $this->transactions = $query->orderBy('created_at', 'asc')->get();
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen       = false;
        $this->material     = null;
        $this->transactions = [];
    }
};
?>

<div
    x-data="{ open: @entangle('isOpen') }"
    x-show="open"
    class="fixed inset-0 z-[999] overflow-y-auto"
    x-cloak
>
    {{-- Overlay --}}
    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"
        wire:click="closeModal"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-3xl ring-1 ring-black/5 overflow-hidden"
            @click.stop
        >
            @if($material)
            {{-- Header --}}
            <div class="bg-accent px-8 py-8 text-white flex justify-between items-start">
                <div>
                    <span class="inline-block px-2 py-1 rounded bg-white/20 text-[10px] font-black uppercase tracking-widest mb-2">Riwayat Transaksi</span>
                    <h3 class="text-2xl font-black">{{ $material->name }}</h3>
                    @if($startDate && $endDate)
                        <p class="text-white/70 text-xs mt-1 font-bold">
                            Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                        </p>
                    @endif
                </div>
                <button wire:click="closeModal" class="bg-white/10 hover:bg-white/20 p-2 rounded-xl transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Summary Bar --}}
            <div class="grid grid-cols-3 divide-x divide-warm/60 bg-base/60 border-b border-warm/60">
                <div class="px-6 py-4 text-center">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Masuk</p>
                    <p class="text-lg font-black text-emerald-600">
                        +{{ (float) $transactions->sum('volume_masuk') }}
                        <span class="text-xs font-bold text-gray-400 ml-0.5">{{ $material->unit }}</span>
                    </p>
                </div>
                <div class="px-6 py-4 text-center">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Keluar</p>
                    <p class="text-lg font-black text-red-500">
                        -{{ (float) $transactions->sum('volume_keluar') }}
                        <span class="text-xs font-bold text-gray-400 ml-0.5">{{ $material->unit }}</span>
                    </p>
                </div>
                <div class="px-6 py-4 text-center">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Saldo Akhir</p>
                    <p class="text-lg font-black text-accent">
                        {{ (float) ($transactions->last()->balance_after ?? 0) }}
                        <span class="text-xs font-bold text-gray-400 ml-0.5">{{ $material->unit }}</span>
                    </p>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-y-auto max-h-96">
                @if($transactions->count())
                <table class="w-full text-[11px]">
                    <thead class="sticky top-0 bg-warm/40 border-b border-warm/60">
                        <tr>
                            <th class="px-4 py-3 text-left font-black text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 py-3 text-left font-black text-gray-500 uppercase tracking-wider">No. Referensi</th>
                            <th class="px-4 py-3 text-center font-black text-emerald-600 uppercase tracking-wider bg-emerald-50/50">Masuk</th>
                            <th class="px-4 py-3 text-center font-black text-red-500 uppercase tracking-wider bg-red-50/50">Keluar</th>
                            <th class="px-4 py-3 text-center font-black text-accent uppercase tracking-wider bg-blue-50/50">Saldo</th>
                            <th class="px-4 py-3 text-left font-black text-gray-500 uppercase tracking-wider">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-warm/40">
                        @foreach($transactions as $t)
                        <tr class="hover:bg-base/60 transition-colors">
                            <td class="px-4 py-3 text-gray-600">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 font-mono font-bold text-accent uppercase text-[10px]">
                                {{ $t->reference_number ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-emerald-600 bg-emerald-50/20">
                                {{ $t->volume_masuk > 0 ? '+' . (float)$t->volume_masuk : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-red-500 bg-red-50/20">
                                {{ $t->volume_keluar > 0 ? '-' . (float)$t->volume_keluar : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center font-black text-accent bg-blue-50/20">
                                {{ (float)$t->balance_after }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $t->deliveryOrder->lokasi ?? ($t->supplier ?: ($t->note ?: 'Restock')) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="px-8 py-16 text-center">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="text-gray-400 font-bold text-sm">Tidak ada transaksi pada periode ini.</p>
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-8 py-5 bg-base/30 border-t border-gray-50 flex justify-end">
                <button wire:click="closeModal" class="bg-base hover:bg-warm/60 text-gray-700 px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest transition-all">
                    Tutup
                </button>
            </div>
            @endif
        </div>
    </div>
</div>
