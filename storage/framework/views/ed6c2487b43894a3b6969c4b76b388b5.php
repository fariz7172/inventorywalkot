<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Exports\StockOpnameExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\StockOpnameItem;
use App\Models\StockOpname;
use Carbon\Carbon;

?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Analisa Selisih Stok</h1>
            <p class="text-sm text-gray-500 mt-1">Laporan analitik performa akurasi stok gudang (Fisik vs Sistem).</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex gap-1 no-print">
                <button onclick="printReport()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2.5 rounded-xl font-bold transition-all shadow-sm hover:bg-gray-50 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak PDF
                </button>
                <button wire:click="exportExcel" class="bg-white border border-gray-300 text-gray-700 px-4 py-2.5 rounded-xl font-bold transition-all shadow-sm hover:bg-gray-50 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Excel
                </button>
            </div>
            <div class="flex flex-col gap-3">
                <div class="flex gap-2 justify-end">
                    <div class="w-48">
                        <div class="relative no-print">
                            <select wire:model.live="filterDifference" class="w-full appearance-none bg-white border border-gray-300 text-gray-700 py-2.5 px-4 pr-8 rounded-xl focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent shadow-sm transition-all cursor-pointer font-medium text-sm">
                                <option value="all">Semua Data</option>
                                <option value="has_diff">Hanya Ada Selisih</option>
                                <option value="plus">Hanya Selisih Lebih (+)</option>
                                <option value="minus">Hanya Selisih Kurang (-)</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>
                    <div class="w-48">
                        <div class="relative no-print">
                            <select wire:model.live="filterPeriod" class="w-full appearance-none bg-white border border-gray-300 text-gray-700 py-2.5 px-4 pr-8 rounded-xl focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent shadow-sm transition-all cursor-pointer font-medium text-sm">
                                <option value="all">Semua Waktu</option>
                                <option value="this_week">Minggu Ini</option>
                                <option value="this_month">Bulan Ini</option>
                                <option value="this_year">Tahun Ini</option>
                                <option value="custom">Kustom Tanggal</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($filterPeriod === 'custom'): ?>
                <div class="flex items-center gap-2 justify-end no-print bg-white p-2 border border-gray-200 rounded-xl shadow-sm">
                    <input type="date" wire:model="startDate" class="bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-accent focus:border-accent block px-3 py-1.5">
                    <span class="text-gray-400 font-bold text-xs uppercase">s/d</span>
                    <input type="date" wire:model="endDate" class="bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-accent focus:border-accent block px-3 py-1.5">
                    <button wire:click="applyCustomDate" class="bg-accent hover:bg-accent-light text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">Terapkan</button>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 no-print">
        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100 flex flex-col justify-center">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Gelar Opname</p>
            <div class="flex items-end gap-2">
                <span class="text-3xl font-black text-gray-800"><?php echo e($statTotalOpname); ?></span>
                <span class="text-xs font-bold text-gray-400 mb-1">Kali</span>
            </div>
        </div>
        <div class="bg-red-50/50 rounded-2xl p-5 shadow-card border border-red-100 flex flex-col justify-center relative overflow-hidden group hover:bg-red-50 transition-colors">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-red-100/50 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
            <p class="text-[11px] font-black text-red-500 uppercase tracking-widest mb-1 relative z-10">Barang Hilang / Minus</p>
            <div class="flex items-end gap-2 relative z-10">
                <span class="text-3xl font-black text-red-600"><?php echo e($statMinus); ?></span>
                <span class="text-xs font-bold text-red-400 mb-1">Item</span>
            </div>
        </div>
        <div class="bg-emerald-50/50 rounded-2xl p-5 shadow-card border border-emerald-100 flex flex-col justify-center relative overflow-hidden group hover:bg-emerald-50 transition-colors">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-emerald-100/50 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
            <p class="text-[11px] font-black text-emerald-600 uppercase tracking-widest mb-1 relative z-10">Kelebihan / Plus</p>
            <div class="flex items-end gap-2 relative z-10">
                <span class="text-3xl font-black text-emerald-700"><?php echo e($statPlus); ?></span>
                <span class="text-xs font-bold text-emerald-500 mb-1">Item</span>
            </div>
        </div>
        <div class="bg-blue-50/50 rounded-2xl p-5 shadow-card border border-blue-100 flex flex-col justify-center relative overflow-hidden group hover:bg-blue-50 transition-colors">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-100/50 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
            <p class="text-[11px] font-black text-blue-600 uppercase tracking-widest mb-1 relative z-10">Stok Akurat (Balance)</p>
            <div class="flex items-end gap-2 relative z-10">
                <span class="text-3xl font-black text-blue-700"><?php echo e($statBalance); ?></span>
                <span class="text-xs font-bold text-blue-500 mb-1">Item</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-card overflow-hidden ring-1 ring-accent/5 print-container">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-warm/30 text-gray-600 font-semibold border-b border-warm/60">
                    <tr>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Material / Barang</th>
                        <th class="px-6 py-4 text-center">Stok Sistem</th>
                        <th class="px-6 py-4 text-center">Stok Fisik</th>
                        <th class="px-6 py-4 text-center">Selisih</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4">Status & Pelaksana</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm/30">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-base/50 transition-colors <?php echo e($item->difference != 0 ? 'bg-orange-50/20' : ''); ?>">
                        <td class="px-6 py-4 font-medium text-gray-900"><?php echo e(\Carbon\Carbon::parse($item->opname->opname_date)->format('d M Y')); ?></td>
                        <td class="px-6 py-4 text-gray-800 font-bold"><?php echo e($item->material->name); ?> <br><span class="text-xs font-normal text-gray-500">Satuan: <?php echo e($item->material->unit); ?></span></td>
                        <td class="px-6 py-4 text-center text-gray-600"><?php echo e((float)$item->system_volume); ?></td>
                        <td class="px-6 py-4 text-center text-gray-900 font-bold"><?php echo e((float)$item->physical_volume); ?></td>
                        <td class="px-6 py-4 text-center font-bold <?php echo e($item->difference > 0 ? 'text-emerald-600' : ($item->difference < 0 ? 'text-red-600' : 'text-gray-400')); ?>">
                            <?php echo e($item->difference > 0 ? '+'.(float)$item->difference : ($item->difference == 0 ? '-' : (float)$item->difference)); ?>

                        </td>
                        <td class="px-6 py-4 text-gray-600 italic">
                            <?php echo e($item->notes ?? '-'); ?>

                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->opname->status === 'approved'): ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded w-max">
                                        Disetujui
                                    </span>
                                <?php elseif($item->opname->status === 'rejected'): ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-red-700 bg-red-100 px-2 py-0.5 rounded w-max">
                                        Ditolak
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded w-max">
                                        Pending
                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <span class="text-[10px] text-gray-500">Oleh: <?php echo e($item->opname->user->name); ?></span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p>Belum ada rekapan opname di periode ini.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <div id="print-area" class="hidden print-target bg-white text-black text-xs" style="font-family: 'Times New Roman', serif;">
        <img src="<?php echo e(asset('assets/kop.png')); ?>" class="w-full h-auto mb-6">
        
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold uppercase leading-tight">(BERITA ACARA STOCK OPNAME)</h1>
            <p class="text-md font-bold mt-1">Periode: <?php echo e($this->periodLabel); ?></p>
        </div>

        <table class="w-full border-collapse border border-black text-[9px]">
            <thead>
                <tr class="bg-gray-100 font-bold text-center">
                    <th class="border border-black px-2 py-2">TANGGAL</th>
                    <th class="border border-black px-2 py-2 text-left">MATERIAL / BARANG</th>
                    <th class="border border-black px-2 py-2">STOK SISTEM</th>
                    <th class="border border-black px-2 py-2">STOK FISIK</th>
                    <th class="border border-black px-2 py-2">SELISIH</th>
                    <th class="border border-black px-2 py-2 text-left">KETERANGAN</th>
                    <th class="border border-black px-2 py-2 text-left">PELAKSANA</th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="border border-black px-2 py-1 text-center"><?php echo e(\Carbon\Carbon::parse($item->opname->opname_date)->format('d/m/Y')); ?></td>
                    <td class="border border-black px-2 py-1 font-bold uppercase"><?php echo e($item->material->name); ?> (<?php echo e($item->material->unit); ?>)</td>
                    <td class="border border-black px-2 py-1 text-center"><?php echo e((float)$item->system_volume); ?></td>
                    <td class="border border-black px-2 py-1 text-center font-bold"><?php echo e((float)$item->physical_volume); ?></td>
                    <td class="border border-black px-2 py-1 text-center font-bold">
                        <?php echo e($item->difference > 0 ? '+'.(float)$item->difference : ($item->difference == 0 ? '-' : (float)$item->difference)); ?>

                    </td>
                    <td class="border border-black px-2 py-1 text-[8px]"><?php echo e($item->notes ?? '-'); ?></td>
                    <td class="border border-black px-2 py-1 text-[8px] uppercase"><?php echo e($item->opname->user->name); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>

        <div class="mt-8 flex justify-end pr-8">
            <div class="text-center w-64">
                <p class="text-xs">Jakarta, <?php echo e(Carbon::now()->translatedFormat('d F Y')); ?></p>
                <p class="text-xs font-bold mt-1">Petugas Gudang / Admin,</p>
                <div class="h-20"></div>
                <p class="text-xs font-bold underline uppercase"><?php echo e(auth()->user()->name); ?></p>
            </div>
        </div>
    </div>

    <script>
        function printReport() {
            const el = document.getElementById('print-area');
            if (!el) return;

            const originalTitle = document.title;
            document.title = "LAPORAN_STOCK_OPNAME_<?php echo e(now()->format('d_m_Y')); ?>";

            const printClone = el.cloneNode(true);
            printClone.id = 'temp-print-area';
            printClone.classList.remove('hidden');
            printClone.classList.add('print-active');
            document.body.appendChild(printClone);

            window.print();
            
            document.body.removeChild(printClone);
            document.title = originalTitle;
        }
    </script>

    <style>
        .print-target { display: none; }
        
        @media print {
            @page {
                size: landscape;
                margin: 1cm;
            }
            
            body > *:not(.print-active) {
                display: none !important;
            }

            .print-active {
                display: block !important;
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                padding: 0 !important;
                background: white !important;
            }
            
            table { border-collapse: collapse !important; }
            th, td { border: 1px solid black !important; }
        }
    </style>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/laporan/rekap-opname.blade.php ENDPATH**/ ?>