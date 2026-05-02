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
    public $openingBalance = 0;
    public $totalIn = 0;
    public $totalOut = 0;
    public $finalBalance = 0;

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

        // 1. Hitung Saldo Awal (Historical before startDate)
        $this->openingBalance = InventoryTransaction::where('material_id', $material_id)
            ->whereDate('created_at', '<', $this->startDate)
            ->selectRaw('SUM(volume_masuk) - SUM(volume_keluar) as balance')
            ->value('balance') ?? 0;

        $this->transactions = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();

        // 2. Hitung Total In/Out di Periode ini
        $this->totalIn = $this->transactions->sum('volume_masuk');
        $this->totalOut = $this->transactions->sum('volume_keluar');

        // 3. Saldo Akhir
        $this->finalBalance = $this->openingBalance + $this->totalIn - $this->totalOut;

        $this->isOpen = true;
    }

    public function applyFilter()
    {
        if($this->material) {
            $this->showRiwayat($this->material->id, $this->startDate, $this->endDate);
        }
    }

    public function exportExcel()
    {
        if (!$this->material) return;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MaterialHistoryExport($this->material->id, $this->startDate, $this->endDate),
            'Riwayat-' . str_replace(' ', '-', $this->material->name) . '-' . date('Ymd') . '.xlsx'
        );
    }

    public function closeModal()
    {
        $this->isOpen         = false;
        $this->material       = null;
        $this->transactions   = [];
        $this->openingBalance = 0;
        $this->totalIn        = 0;
        $this->totalOut       = 0;
        $this->finalBalance   = 0;
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
            class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-4xl ring-1 ring-black/5 overflow-hidden"
            @click.stop
        >
            @if($material)
            {{-- Header --}}
            <div class="bg-accent px-8 py-6 text-white flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div>
                    <span class="inline-block px-2 py-1 rounded bg-white/20 text-[10px] font-black uppercase tracking-widest mb-1">Riwayat Transaksi</span>
                    <h3 class="text-2xl font-black">{{ $material->name }}</h3>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="flex items-center gap-2 bg-white/10 p-1.5 rounded-xl border border-white/20">
                        <div class="flex flex-col">
                            <span class="text-[9px] text-white/70 font-bold uppercase tracking-wider px-2">Dari</span>
                            <input type="date" wire:model="startDate" class="bg-transparent border-none text-white text-xs font-bold focus:ring-0 cursor-pointer py-1" style="color-scheme: dark;">
                        </div>
                        <span class="text-white/30 font-bold">-</span>
                        <div class="flex flex-col">
                            <span class="text-[9px] text-white/70 font-bold uppercase tracking-wider px-2">Sampai</span>
                            <input type="date" wire:model="endDate" class="bg-transparent border-none text-white text-xs font-bold focus:ring-0 cursor-pointer py-1" style="color-scheme: dark;">
                        </div>
                        <button wire:click="applyFilter" class="bg-white text-accent px-4 py-2 rounded-lg text-xs font-black hover:bg-gray-100 transition-colors uppercase tracking-widest shadow-sm ml-1">
                            Filter
                        </button>
                    </div>
                    <button wire:click="closeModal" class="bg-white/10 hover:bg-white/20 p-2.5 rounded-xl transition-colors ml-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Summary Bar --}}
            <div class="grid grid-cols-4 divide-x divide-gray-200 bg-white border-b border-gray-100">
                <div class="px-4 py-6 text-center">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Saldo Awal</p>
                    <p class="text-xl font-black text-gray-900">
                        {{ number_format($openingBalance, 0, ',', '.') }}
                        <span class="text-xs font-bold text-gray-400 ml-0.5">{{ $material->unit }}</span>
                    </p>
                </div>
                <div class="px-4 py-6 text-center">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Masuk (+)</p>
                    <p class="text-xl font-black text-green-600">
                        +{{ number_format($totalIn, 0, ',', '.') }}
                    </p>
                </div>
                <div class="px-4 py-6 text-center">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Keluar (-)</p>
                    <p class="text-xl font-black text-red-600">
                        -{{ number_format($totalOut, 0, ',', '.') }}
                    </p>
                </div>
                <div class="px-4 py-6 text-center bg-blue-50/30">
                    <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-1">Stock Sisa</p>
                    <p class="text-xl font-black text-blue-700">
                        {{ number_format($finalBalance, 0, ',', '.') }}
                        <span class="text-xs font-bold text-blue-400 ml-0.5">{{ $material->unit }}</span>
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
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 flex justify-end items-center gap-3">
                <a href="{{ route('laporan.print-riwayat', ['id' => $material->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" target="_blank" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-blue-200 flex items-center gap-2 hover:bg-blue-700 transition-all uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak Print
                </a>
                <button wire:click="exportExcel" class="bg-green-600 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-green-200 flex items-center gap-2 hover:bg-green-700 transition-all uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download Excel
                </button>
                <button wire:click="closeModal" class="px-6 py-2.5 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-100 transition-all uppercase tracking-widest">
                    Tutup
                </button>
            </div>
            @endif
        </div>
    </div>
</div>
