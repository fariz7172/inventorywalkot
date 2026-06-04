<?php

use App\Models\DeliveryOrder;
use Livewire\Volt\Component;

new class extends Component {
    public $isOpen = false;
    public $order = null;

    protected $listeners = ['show-sj-detail' => 'showDetail'];

    public function showDetail($id)
    {
        $this->order = DeliveryOrder::with(['materials'])->find($id);
        if ($this->order) {
            $this->isOpen = true;
        }
    }

    public function closeModal()
    {
        $this->isOpen = false;
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
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" 
        wire:click="closeModal"
    ></div>

    {{-- Modal Content --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div 
            x-show="open" 
            x-transition:enter="ease-out duration-300" 
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
            x-transition:leave="ease-in duration-200" 
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
            class="relative transform overflow-hidden rounded-[2.5rem] bg-white shadow-2xl transition-all w-full max-w-2xl ring-1 ring-black/5"
        >
            @if($order)
                {{-- Header --}}
                <div class="bg-accent px-8 py-10 text-white relative">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="inline-block px-2 py-1 rounded bg-white/20 text-[10px] font-black uppercase tracking-widest mb-3">Detail Surat Jalan</span>
                            <h3 class="text-3xl font-black">{{ $order->surat_jalan_no }}</h3>
                            <p class="text-white/70 text-sm mt-1 font-bold">{{ $order->tanggal->format('d F Y') }}</p>
                        </div>
                        <button wire:click="closeModal" class="bg-white/10 hover:bg-white/20 p-2 rounded-xl transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-8 py-8 space-y-8">
                    {{-- Info Cards --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-base/40 p-4 rounded-2xl border border-warm/60">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Tujuan Pengiriman</p>
                            <p class="text-sm font-bold text-gray-800">{{ $order->lokasi }}</p>
                        </div>
                        <div class="bg-base/40 p-4 rounded-2xl border border-warm/60">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">No. Polisi</p>
                            <p class="text-sm font-bold text-gray-800 uppercase">{{ $order->no_polisi ?: '-' }}</p>
                        </div>
                    </div>

                    {{-- Materials List --}}
                    <div>
                        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            Daftar Barang
                        </h4>
                        <div class="space-y-2">
                            @foreach($order->materials as $m)
                            <div class="flex items-center justify-between p-4 bg-white rounded-2xl border border-warm/60 hover:border-accent/30 transition-colors">
                                <div>
                                    <p class="text-sm font-black text-gray-800">{{ $m->name }}</p>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter">{{ $m->category->name ?? '-' }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-lg font-black text-accent">{{ $m->pivot->requested_volume }}</span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase ml-1">{{ $m->unit }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Footer Info --}}
                    <div class="pt-6 border-t border-gray-50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center text-[10px] font-black text-accent uppercase">
                                {{ substr($order->petugas ?? '?', 0, 1) }}
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Petugas</p>
                                <p class="text-xs font-bold text-gray-800">{{ $order->petugas ?: 'System' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span @class([
                                'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest',
                                'bg-amber-100 text-amber-600' => $order->status === 'draft',
                                'bg-emerald-100 text-emerald-600' => $order->status === 'shipped'
                            ])>
                                {{ $order->status === 'draft' ? 'Menunggu Konfirmasi' : 'Sudah Dikirim' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Action --}}
                <div class="px-8 pb-8 flex gap-3">
                    <button wire:click="closeModal" class="flex-1 bg-base hover:bg-warm/60 text-gray-700 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all">
                        Tutup
                    </button>
                    @if(auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang') || auth()->user()->hasRole('admin'))
                    <a href="{{ route('surat-jalan.show', $order->id) }}" class="flex-1 bg-accent text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest text-center shadow-lg shadow-accent/20 hover:bg-accent-dark transition-all">
                        Kelola Dokumen
                    </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

