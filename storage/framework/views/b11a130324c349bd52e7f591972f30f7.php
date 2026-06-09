<?php

use App\Models\DeliveryOrder;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Livewire\Volt\Component;

?>

<div class="max-w-4xl mx-auto" x-data>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('message')): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold">
            <?php echo e(session('message')); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Surat Jalan</h1>
            <div class="flex items-center gap-2 mt-1">
                <p class="text-sm text-gray-500">Nomor: <span class="font-mono font-bold text-accent"><?php echo e($order->surat_jalan_no); ?></span></p>
                <span class="text-[10px] px-2 py-0.5 rounded bg-gray-100 text-gray-400 font-bold uppercase tracking-widest">
                    Role Anda: <?php echo e(auth()->user()->getRoleNames()->implode(', ') ?: 'Tidak Ada Role'); ?>

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
            <button onclick="printBA()" class="bg-white text-gray-700 border border-gray-200 px-4 py-2 rounded-xl font-bold text-sm shadow-sm flex items-center gap-2 hover:bg-gray-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak Berita Acara
            </button>
            <button onclick="printSPB()" class="bg-blue-600 text-white px-4 py-2 rounded-xl font-bold text-sm shadow-sm flex items-center gap-2 hover:bg-blue-700 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Cetak SPB
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
                <h2 class="text-sm font-bold text-accent uppercase tracking-wider mb-4 border-b border-warm/60 pb-2">Informasi Pengiriman</h2>
                <div class="grid grid-cols-2 gap-y-4 text-sm">
                    <div class="text-gray-400">Tanggal</div>
                    <div class="font-bold text-gray-800"><?php echo e($order->tanggal->format('d F Y')); ?></div>
                    <div class="text-gray-400">Lokasi Tujuan</div>
                    <div class="font-bold text-gray-800"><?php echo e($order->lokasi); ?></div>
                    <div class="text-gray-400">Kecamatan</div>
                    <div class="font-bold text-gray-800"><?php echo e($order->pelaksana_kecamatan ?: '-'); ?></div>
                    <div class="text-gray-400">Penerima</div>
                    <div class="font-bold text-gray-800"><?php echo e($order->penerima ?: '-'); ?></div>
                    <div class="text-gray-400">No. Polisi</div>
                    <div class="font-bold text-gray-800 uppercase"><?php echo e($order->no_polisi ?: '-'); ?></div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
                <h2 class="text-sm font-bold text-accent uppercase tracking-wider mb-4 border-b border-warm/60 pb-2">Daftar Material</h2>
                <div class="space-y-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $order->materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex items-center justify-between p-3 bg-base/40 rounded-xl border border-warm/40">
                        <div>
                            <p class="text-sm font-bold text-gray-800"><?php echo e($m->name); ?></p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-widest font-black"><?php echo e($m->category->name); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-black text-gray-900"><?php echo e((float)$m->pivot->requested_volume); ?> <span class="text-xs text-gray-400"><?php echo e($m->unit); ?></span></p>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-center py-6">
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Detail barang tidak ditemukan</p>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Mungkin surat jalan ini dibuat sebelum update sistem.</p>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
                <h2 class="text-sm font-bold text-accent uppercase tracking-wider mb-4 border-b border-warm/60 pb-2">Status & Aksi</h2>
                <div class="mb-6">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->status === 'draft'): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-600">
                            <span class="w-2 h-2 bg-amber-500 rounded-full mr-2"></span>
                            Menunggu Gudang
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-600">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2"></span>
                            Sudah Dikirim
                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['process'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="p-3 bg-red-50 text-red-500 text-xs rounded-lg mb-4 font-bold border border-red-100">
                        <?php echo e($message); ?>

                    </div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->status === 'draft' && (auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang') || auth()->user()->hasRole('superadmin') || auth()->user()->hasRole('sudin'))): ?>
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
                <?php elseif($order->status === 'draft'): ?>
                    <div class="p-4 bg-warm/30 rounded-xl border border-warm/60 text-center">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Menunggu Konfirmasi</p>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Hanya petugas gudang, kepala gudang, superadmin, atau SUDIN yang dapat melakukan konfirmasi pengiriman.</p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .print-target { display: none; }
        
        @media print {
            @page { 
                margin: 0; /* Menghilangkan header/footer bawaan browser (termasuk URL) */
            }
            html, body, .flex, main, .page-content {
                height: auto !important;
                overflow: visible !important;
                display: block !important;
            }
            body * {
                visibility: hidden;
            }
            .print-active, .print-active * {
                visibility: visible;
            }
            .print-active {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                display: block !important;
                padding: 1.5cm !important; /* Memberikan margin konten agar tidak menempel di ujung kertas */
                visibility: visible !important;
            }
        }
    </style>

    
    <div id="print-ba-area" class="print-target bg-white p-6 text-black leading-tight" style="font-family: 'Times New Roman', serif;">
        <img src="<?php echo e(asset('assets/kop.png')); ?>" class="w-full h-auto mb-8">

        <?php
            $carbonDate = $order->tanggal;
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            $dayName = $days[$carbonDate->dayOfWeek];
            $monthName = $months[$carbonDate->month];
            
            $sumberTujuan = $order->lokasi ?: 'Internal';
            
            $title1 = "BERITA ACARA SERAH TERIMA BARANG";
            $title2 = "DISTRIBUSI/PENGELUARAN";
            $labelPihakSatu = 'Pengurus Barang/Pengurus Barang Pembantu';
            $labelPihakDua = 'Pemakai Persediaan';
        ?>

        <div class="text-center mb-6">
            <h1 class="text-lg font-bold underline uppercase leading-tight">
                <?php echo e($title1); ?><br>
                <?php echo e($title2); ?>

            </h1>
            <p class="text-sm font-bold mt-1">Nomor: <?php echo e($order->surat_jalan_no ?: '……………………………'); ?></p>
        </div>

        <div class="text-justify mb-4 text-[13px]">
            <p>Pada Hari ini <span class="font-bold"><?php echo e($dayName); ?></span> Tanggal <span class="font-bold"><?php echo e($carbonDate->day); ?></span> Bulan <span class="font-bold"><?php echo e($monthName); ?></span> Tahun <span class="font-bold"><?php echo e($carbonDate->year); ?></span> </p>
            <p>yang bertanda tangan dibawah ini:</p>
            
            <div class="mt-3 ml-8 space-y-0.5">
                <p>Nama : <span class="font-bold"><?php echo e($order->petugas ?: auth()->user()->name); ?></span></p>
                <p>Jabatan : <span class="font-bold text-[11px]"><?php echo e($labelPihakSatu); ?></span></p>
            </div>

            <p class="mt-3">
                Telah menyerahkan barang persedian yang diterima oleh <span class="font-bold text-sm underline"><?php echo e($sumberTujuan); ?></span> 
                sesuai dengan Berita Acara Pemeriksaan Barang Nomor <span class="font-bold"><?php echo e($order->surat_jalan_no ?: '……'); ?></span> 
                Tanggal <span class="font-bold"><?php echo e($carbonDate->day); ?></span> Bulan <span class="font-bold"><?php echo e($monthName); ?></span> Tahun <span class="font-bold"><?php echo e($carbonDate->year); ?></span>. 
                Sebagaimana daftar terlampir. Daftar barang yang diserahkan sebagai berikut:
            </p>
        </div>

        <table class="w-full border-collapse border border-black text-[12px] mb-6">
            <thead>
                <tr class="bg-gray-50">
                    <th class="border border-black px-2 py-1 text-center w-8">No</th>
                    <th class="border border-black px-3 py-1 text-left">Uraian Nama Barang</th>
                    <th class="border border-black px-3 py-1 text-center w-24">Satuan</th>
                    <th class="border border-black px-3 py-1 text-center w-24">Volume</th>
                    <th class="border border-black px-3 py-1 text-left">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $order->materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="border border-black px-2 py-1.5 text-center"><?php echo e($index + 1); ?></td>
                    <td class="border border-black px-3 py-1.5 font-bold uppercase"><?php echo e($m->name); ?></td>
                    <td class="border border-black px-3 py-1.5 text-center uppercase"><?php echo e($m->unit); ?></td>
                    <td class="border border-black px-3 py-1.5 text-center font-bold text-sm">
                        <?php echo e((float)$m->pivot->requested_volume); ?>

                    </td>
                    <td class="border border-black px-3 py-1.5 italic text-[10px]"><?php echo e($order->keterangan ?: '-'); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>

        <p class="text-[13px] mb-8">Demikian Berita Acara Serah Terima Barang ini dibuat dalam rangkap 2 (dua) untuk digunakan sebagaimana mestinya.</p>

        <div class="grid grid-cols-2 text-center text-[13px]">
            <div>
                <p>Jakarta, <?php echo e($carbonDate->day); ?> <?php echo e($monthName); ?> <?php echo e($carbonDate->year); ?></p>
                <p class="mt-1">Yang menyerahkan Barang,</p>
                <p class="font-bold text-[10px] uppercase max-w-[200px] mx-auto leading-tight mt-1"><?php echo e($labelPihakSatu); ?></p>
                
                <div class="h-20"></div>
                
                <p class="font-bold underline uppercase"><?php echo e($order->petugas ?: auth()->user()->name); ?></p>
            </div>
            <div>
                <p class="invisible">Jakarta, ...</p>
                <p class="mt-1">Yang menerima Barang,</p>
                <p class="font-bold text-[10px] uppercase max-w-[200px] mx-auto leading-tight mt-1"><?php echo e($labelPihakDua); ?></p>
                
                <div class="h-20"></div>
                
                <p class="font-bold underline uppercase"><?php echo e($order->penerima ?: ($order->pemohon ?: $sumberTujuan)); ?></p>
            </div>
        </div>
        <div class="fixed bottom-[1.5cm] left-[1.5cm] text-[10px] text-gray-500"><?php echo e(url()->current()); ?></div>
        <div class="fixed bottom-[1.5cm] right-[1.5cm] text-[10px] text-gray-500">Dicetak pada: <?php echo e(now()->format('d/m/Y H:i')); ?></div>
    </div>

    
    <div id="print-spb-area" class="print-target bg-white p-6 text-black leading-tight" style="font-family: 'Times New Roman', serif;">
        <img src="<?php echo e(asset('assets/kop.png')); ?>" class="w-full h-auto mb-8">

        <div class="text-center mb-6">
            <h1 class="text-lg font-bold underline uppercase leading-tight">
                SURAT PERMINTAAN BARANG (SPB)
            </h1>
            <p class="text-sm font-bold mt-1">Nomor: <?php echo e($order->surat_jalan_no ?: '……………………………'); ?></p>
        </div>

        <table class="w-full border-collapse border border-black text-[12px] mb-8">
            <thead>
                <tr>
                    <th class="border border-black px-2 py-2 text-center w-8">No</th>
                    <th class="border border-black px-3 py-2 text-left">Uraian / Nama Barang</th>
                    <th class="border border-black px-3 py-2 text-center w-24">Jumlah</th>
                    <th class="border border-black px-3 py-2 text-left w-32">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $order->materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="border border-black px-2 py-1.5 text-center"><?php echo e($index + 1); ?></td>
                    <td class="border border-black px-3 py-1.5 font-bold uppercase"><?php echo e($m->name); ?></td>
                    <td class="border border-black px-3 py-1.5 text-center font-bold text-sm">
                        <?php echo e((float)$m->pivot->requested_volume); ?> <?php echo e($m->unit); ?>

                    </td>
                    <td class="border border-black px-3 py-1.5 italic text-[10px]"><?php echo e($order->keterangan ?: '-'); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>

        <div class="grid grid-cols-2 text-center text-[13px] mt-12">
            <div>
                <p class="invisible">Jakarta, ...</p>
                <p class="mt-1">Mengetahui,</p>
                <p class="font-bold text-[11px] uppercase max-w-[200px] mx-auto leading-tight mt-1">Unit / Kabag / Kabid</p>
                <div class="h-24"></div>
                <p class="font-bold underline uppercase">______________________</p>
                <p class="text-[11px] mt-0.5">NIP: ..............................</p>
            </div>
            <div>
                <p>Jakarta, <?php echo e($carbonDate->day); ?> <?php echo e($monthName); ?> <?php echo e($carbonDate->year); ?></p>
                <p class="mt-1">Yang Meminta Barang,</p>
                <p class="font-bold text-[11px] uppercase max-w-[200px] mx-auto leading-tight mt-1">Petugas / Pemohon</p>
                <div class="h-24"></div>
                <p class="font-bold underline uppercase"><?php echo e($order->pemohon ?: '______________________'); ?></p>
                <p class="text-[11px] mt-0.5">NIP: ..............................</p>
            </div>
        </div>
        <div class="fixed bottom-[1.5cm] left-[1.5cm] text-[10px] text-gray-500"><?php echo e(url()->current()); ?></div>
        <div class="fixed bottom-[1.5cm] right-[1.5cm] text-[10px] text-gray-500">Dicetak pada: <?php echo e(now()->format('d/m/Y H:i')); ?></div>
    </div>

    <script>
        function printBA() {
            document.querySelectorAll('.print-target').forEach(el => el.classList.remove('print-active'));
            document.getElementById('print-ba-area').classList.add('print-active');
            window.print();
        }
        function printSPB() {
            document.querySelectorAll('.print-target').forEach(el => el.classList.remove('print-active'));
            document.getElementById('print-spb-area').classList.add('print-active');
            window.print();
        }
    </script>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/surat-jalan/show.blade.php ENDPATH**/ ?>