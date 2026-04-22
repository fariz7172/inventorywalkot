<?php

use App\Models\DeliveryOrder;
use function Livewire\Volt\{state, computed, layout};

layout('layouts.admin');

// State untuk filter
state(['search' => '', 'status' => '']);

// Query data
$deliveryOrders = computed(function () {
    return DeliveryOrder::query()
        ->when($this->search, fn($q) => $q->where('surat_jalan_no', 'like', '%' . $this->search . '%')
            ->orWhere('lokasi', 'like', '%' . $this->search . '%'))
        ->when($this->status, fn($q) => $q->where('status', $this->status))
        ->latest()
        ->paginate(10);
});

$deleteOrder = function (DeliveryOrder $order) {
    $order->delete();
};

?>

<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Daftar Surat Jalan</h1>
            <p class="text-sm text-gray-500 mt-0.5">Kelola permintaan pengiriman barang ke gudang.</p>
        </div>
        <a href="/dashboard/surat-jalan/create" wire:navigate class="flex items-center gap-2 bg-accent text-white px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-accent-dark transition-all shadow-md shadow-accent/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Buat Surat Jalan
        </a>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-4 mb-5 flex flex-col sm:flex-row gap-3">
        <div class="flex items-center bg-base rounded-xl px-3 py-2 gap-2 flex-1 border border-warm/60">
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" wire:model.live="search" placeholder="Cari No. Surat Jalan atau Lokasi..." class="bg-transparent text-sm outline-none w-full text-gray-600 placeholder-gray-400 border-none focus:ring-0"/>
        </div>
        <select wire:model.live="status" class="bg-base rounded-xl px-3 py-2 text-sm text-gray-600 outline-none border border-warm/60 focus:ring-accent/30 transition-all">
            <option value="">Semua Status</option>
            <option value="draft">Draft (Ordered)</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped (Sent)</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-warm/40 border-b border-warm/60">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">No. Surat Jalan</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tujuan / Lokasi</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pemohon</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm/40">
                    @forelse($this->deliveryOrders as $order)
                    <tr class="hover:bg-base/60 transition-colors">
                        <td class="px-5 py-4">
                            <p class="font-bold text-accent font-mono text-xs">{{ $order->surat_jalan_no }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600">{{ $order->tanggal }}</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-800 text-xs">{{ $order->lokasi }}</p>
                            <p class="text-[10px] text-gray-400">{{ $order->pelaksana_kecamatan }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 font-medium">{{ $order->pemohon }}</td>
                        <td class="px-5 py-4">
                            @php
                                $statusClasses = [
                                    'draft' => 'bg-amber-50 text-amber-600',
                                    'processing' => 'bg-blue-50 text-blue-600',
                                    'shipped' => 'bg-emerald-50 text-emerald-600',
                                ];
                            @endphp
                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg {{ $statusClasses[$order->status] }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <a href="/dashboard/surat-jalan/{{ $order->id }}" wire:navigate class="w-8 h-8 rounded-lg hover:bg-accent/10 flex items-center justify-center transition-colors group">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @if($order->status === 'draft')
                                <button wire:click="deleteOrder({{ $order->id }})" wire:confirm="Yakin ingin menghapus data ini?" class="w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center transition-colors group">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-gray-400 italic">Belum ada data surat jalan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-warm/60 bg-warm/20">
            {{ $this->deliveryOrders->links() }}
        </div>
    </div>
</div>
