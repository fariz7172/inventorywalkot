<?php

use App\Models\DeliveryOrder;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Livewire\Volt\Component;

new class extends Component {
    public DeliveryOrder $order;

    public function mount(DeliveryOrder $order)
    {
        $this->order = $order->load('materials');
    }

    public function rendering($view)
    {
        $view->layout('layouts.admin');
    }

    public function processShipment(InventoryService $service)
    {
        try {
            $formattedItems = [];
            foreach ($this->order->materials as $m) {
                $formattedItems[] = [
                    'material_id' => $m->id,
                    'volume_keluar' => $m->pivot->requested_volume,
                ];
            }

            if (empty($formattedItems)) {
                throw new \Exception("Daftar barang kosong. Tidak ada yang bisa dikirim.");
            }

            $service->processDelivery($this->order->id, $formattedItems);
            
            session()->flash('message', 'Barang berhasil diproses dan stok telah terpotong.');
            return $this->redirect('/dashboard/surat-jalan', navigate: true);
        } catch (\Exception $e) {
            $this->addError('process', $e->getMessage());
        }
    }
}

?>

<div class="max-w-4xl mx-auto" x-data>
    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Surat Jalan</h1>
            <div class="flex items-center gap-2 mt-1">
                <p class="text-sm text-gray-500">Nomor: <span class="font-mono font-bold text-accent">{{ $order->surat_jalan_no }}</span></p>
                <span class="text-[10px] px-2 py-0.5 rounded bg-gray-100 text-gray-400 font-bold uppercase tracking-widest">
                    Role Anda: {{ auth()->user()->getRoleNames()->implode(', ') ?: 'Tidak Ada Role' }}
                </span>
            </div>
        </div>
        <a href="/dashboard/surat-jalan" wire:navigate class="text-sm font-semibold text-gray-500 hover:text-accent flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="bg-white text-gray-700 border border-gray-200 px-4 py-2 rounded-xl font-bold text-sm shadow-sm flex items-center gap-2 hover:bg-gray-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak (Dot Matrix)
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
                <h2 class="text-sm font-bold text-accent uppercase tracking-wider mb-4 border-b border-warm/60 pb-2">Informasi Pengiriman</h2>
                <div class="grid grid-cols-2 gap-y-4 text-sm">
                    <div class="text-gray-400">Tanggal</div>
                    <div class="font-bold text-gray-800">{{ $order->tanggal->format('d F Y') }}</div>
                    <div class="text-gray-400">Lokasi Tujuan</div>
                    <div class="font-bold text-gray-800">{{ $order->lokasi }}</div>
                    <div class="text-gray-400">Kecamatan</div>
                    <div class="font-bold text-gray-800">{{ $order->pelaksana_kecamatan ?: '-' }}</div>
                    <div class="text-gray-400">No. Polisi</div>
                    <div class="font-bold text-gray-800 uppercase">{{ $order->no_polisi ?: '-' }}</div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
                <h2 class="text-sm font-bold text-accent uppercase tracking-wider mb-4 border-b border-warm/60 pb-2">Daftar Material</h2>
                <div class="space-y-3">
                    @forelse($order->materials as $m)
                    <div class="flex items-center justify-between p-3 bg-base/40 rounded-xl border border-warm/40">
                        <div>
                            <p class="text-sm font-bold text-gray-800">{{ $m->name }}</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-widest font-black">{{ $m->category->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-black text-gray-900">{{ $m->pivot->requested_volume }} <span class="text-xs text-gray-400">{{ $m->unit }}</span></p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-6">
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Detail barang tidak ditemukan</p>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Mungkin surat jalan ini dibuat sebelum update sistem.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
                <h2 class="text-sm font-bold text-accent uppercase tracking-wider mb-4 border-b border-warm/60 pb-2">Status & Aksi</h2>
                <div class="mb-6">
                    @if($order->status === 'draft')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-600">
                            <span class="w-2 h-2 bg-amber-500 rounded-full mr-2"></span>
                            Menunggu Gudang
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-600">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2"></span>
                            Sudah Dikirim
                        </span>
                    @endif
                </div>

                @error('process')
                    <div class="p-3 bg-red-50 text-red-500 text-xs rounded-lg mb-4 font-bold border border-red-100">
                        {{ $message }}
                    </div>
                @enderror

                @if($order->status === 'draft' && auth()->user()->hasRole('gudang'))
                    <button wire:click="processShipment" wire:loading.attr="disabled" class="w-full bg-accent text-white py-4 rounded-xl font-bold shadow-lg shadow-accent/30 hover:bg-accent-dark transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg wire:loading.remove class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg wire:loading class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove>Konfirmasi Kirim</span>
                        <span wire:loading>Memproses...</span>
                    </button>
                    <p class="text-[10px] text-gray-400 text-center mt-3 italic font-medium px-2">Klik tombol di atas saat barang benar-benar keluar dari gudang.</p>
                @elseif($order->status === 'draft')
                    <div class="p-4 bg-warm/30 rounded-xl border border-warm/60 text-center">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Menunggu Konfirmasi</p>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Hanya petugas gudang yang dapat melakukan konfirmasi pengiriman.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        @media screen {
            #dot-matrix-print { display: none; }
        }

        @media print {
            body * { visibility: hidden; }
            #dot-matrix-print, #dot-matrix-print * { visibility: visible; }
            #dot-matrix-print {
                display: block !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 21cm; /* Lebar F4 standar */
                height: 10.5cm; /* 1/3 dari Panjang F4 (33cm / 3) */
                font-family: 'Courier New', Courier, monospace;
                font-size: 9pt;
                color: black;
                line-height: 1;
            }

            @page {
                size: landscape;
                margin: 0.2cm;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 3px;
            }
            
            th, td {
                border: 1px solid black;
                padding: 1px 4px;
                text-align: left;
            }

            .no-border td {
                border: none !important;
            }

            .header-compact {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid black;
                margin-bottom: 5px;
                padding-bottom: 2px;
            }
        }
    </style>

    <div id="dot-matrix-print">
        <div class="header-compact">
            <div style="font-weight: bold; font-size: 11pt;">SURAT JALAN</div>
            <div style="text-align: right;">
                <strong>No: {{ $order->surat_jalan_no }}</strong> | Tgl: {{ $order->tanggal->format('d/m/Y') }}
            </div>
        </div>

        <table class="no-border" style="width: 100%; margin-bottom: 3px;">
            <tr>
                <td style="width: 10%;">Tujuan</td>
                <td style="width: 2%;">:</td>
                <td style="width: 53%;"><strong>{{ $order->lokasi }}</strong> ({{ $order->pelaksana_kecamatan ?: '-' }})</td>
                <td style="width: 12%;">No. Pol</td>
                <td style="width: 2%;">:</td>
                <td style="width: 21%;"><strong>{{ $order->no_polisi ?: '-' }}</strong></td>
            </tr>
        </table>

        <table>
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="width: 5%; text-align: center;">NO</th>
                    <th style="width: 70%;">DESKRIPSI MATERIAL</th>
                    <th style="width: 12%; text-align: center;">QTY</th>
                    <th style="width: 13%;">SATUAN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->materials as $index => $m)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $m->name }}</td>
                    <td style="text-align: center;"><strong>{{ (float)$m->pivot->requested_volume }}</strong></td>
                    <td>{{ $m->unit }}</td>
                </tr>
                @endforeach
                {{-- Minimal baris untuk estetika nota --}}
                @for($i = count($order->materials); $i < 3; $i++)
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                @endfor
            </tbody>
        </table>

        <div style="margin-top: 5px;">
            <table class="no-border" style="text-align: center; width: 100%;">
                <tr style="font-weight: bold;">
                    <td>Penerima</td>
                    <td>Sopir</td>
                    <td>Gudang</td>
                    <td>Mengetahui</td>
                </tr>
                <tr>
                    <td style="height: 35px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>( ________ )</td>
                    <td>( ________ )</td>
                    <td>( {{ auth()->user()->name }} )</td>
                    <td>( ________ )</td>
                </tr>
            </table>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 5px;">
            <div style="font-size: 7pt; font-style: italic; border-top: 1px dotted black; width: 75%;">
                * Putih: Kantor | Merah: Penerima | Kuning: Gudang | Hijau: Arsip
                <br>Dicetak pada: {{ now()->format('d/m/Y H:i') }}
            </div>
            <div style="text-align: center;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data={{ urlencode(request()->url()) }}" style="width: 55px; height: 55px; border: 1px solid #000; padding: 2px;">
                <div style="font-size: 6pt; font-weight: bold; margin-top: 2px;">SCAN DATA</div>
            </div>
        </div>
    </div>
</div>
