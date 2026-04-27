<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Exports\StockOpnameExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\StockOpnameItem;
use Carbon\Carbon;

?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Rekap Stock Opname</h1>
            <p class="text-sm text-gray-500 mt-1">Daftar keseluruhan riwayat item barang yang di-opname (Sistem vs Fisik).</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex gap-1 no-print">
                <button onclick="window.print()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2.5 rounded-xl font-bold transition-all shadow-sm hover:bg-gray-50 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak PDF (Landscape)
                </button>
                <button wire:click="exportExcel" class="bg-white border border-gray-300 text-gray-700 px-4 py-2.5 rounded-xl font-bold transition-all shadow-sm hover:bg-gray-50 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Excel
                </button>
            </div>
            <div class="flex gap-2">
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
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>
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

    <style>
        @media print {
            @page { size: landscape; margin: 1cm; }
            .no-print, nav, aside, header { display: none !important; }
            body { background: white !important; padding: 0 !important; margin: 0 !important; }
            .print-container { 
                box-shadow: none !important; 
                border: 1px solid #e2e8f0 !important; 
                width: 100% !important;
                position: absolute;
                left: 0;
                top: 0;
            }
            .bg-warm\/30 { background-color: #f8fafc !important; }
            table { width: 100% !important; border-collapse: collapse !important; }
            th, td { border: 1px solid #e2e8f0 !important; padding: 10px !important; font-size: 10pt !important; }
            h1 { font-size: 18pt !important; margin-bottom: 5pt !important; }
            p { font-size: 10pt !important; margin-bottom: 20pt !important; }
        }
    </style>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/laporan/rekap-opname.blade.php ENDPATH**/ ?>