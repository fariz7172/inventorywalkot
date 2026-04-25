<?php

use App\Models\Material;
use App\Models\InventoryTransaction;
use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Saldo & Mutasi</h1>
            <p class="text-sm text-gray-500 mt-1">Rekapitulasi saldo awal, barang masuk, keluar, dan saldo akhir per
                material.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button wire:click="exportExcel" class="bg-emerald-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-emerald-500/20 flex items-center gap-2 hover:bg-emerald-600 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Excel
            </button>
            <button wire:click="resetFilters" class="text-[10px] font-bold text-red-500 hover:text-red-600 uppercase tracking-widest px-2 transition-colors border border-red-200 rounded-lg py-2 bg-red-50/50">Reset Filter</button>
            
            <div class="flex items-center gap-2 bg-white rounded-xl px-3 py-1.5 ring-1 ring-accent/5 shadow-sm">
                <select wire:model.live="year" class="text-xs font-bold text-gray-700 outline-none bg-transparent">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($y = date('Y'); $y >= 2024; $y--): ?>
                        <option value="<?php echo e($y); ?>"><?php echo e($y); ?></option>
                    <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
                <span class="text-gray-300">|</span>
                <select wire:model.live="month" class="text-xs font-bold text-gray-700 outline-none bg-transparent">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = range(1, 12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e(sprintf('%02d', $m)); ?>"><?php echo e(Carbon::create(2024, $m, 1)->translatedFormat('F')); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
                <span class="text-gray-300">|</span>
                <select wire:model.live="week" class="text-xs font-bold text-gray-700 outline-none bg-transparent">
                    <option value="">Semua Minggu</option>
                    <option value="1">Minggu 1</option>
                    <option value="2">Minggu 2</option>
                    <option value="3">Minggu 3</option>
                    <option value="4">Minggu 4</option>
                    <option value="5">Minggu 5</option>
                </select>
            </div>

            <select wire:model.live="category_id"
                class="bg-white rounded-xl px-4 py-2.5 text-xs font-bold text-gray-700 ring-1 ring-accent/5 shadow-sm outline-none">
                <option value="">Semua Kategori</option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($c->id); ?>"><?php echo e($c->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-card ring-1 ring-accent/5 overflow-hidden">
        <div class="p-8 border-b border-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center text-accent">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <span class="text-[10px] font-black text-accent uppercase tracking-widest block">Periode
                        Laporan</span>
                    <span class="text-sm font-bold text-gray-800"><?php echo e($periodLabel); ?></span>
                </div>
            </div>

            <div class="relative w-full max-w-xs">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama barang..."
                    class="w-full bg-base rounded-xl pl-10 pr-4 py-2.5 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-base/50">
                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Material
                        </th>
                        <th
                            class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-center">
                            Satuan</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">
                            Saldo Awal Awal Bulan</th>
                        <th
                            class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-emerald-600 text-right bg-emerald-50/30">
                            Masuk (+)</th>
                        <th
                            class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-red-500 text-right bg-red-50/30">
                            Keluar (-)</th>
                        <th
                            class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-accent text-right bg-accent/5">
                            Saldo Akhir</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-base/20 transition-colors">
                            <td class="px-8 py-4">
                                <span class="text-sm font-bold text-gray-800 block leading-tight"><?php echo e($row->name); ?></span>
                                <span
                                    class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter"><?php echo e($row->category); ?></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-xs font-bold text-gray-500 uppercase"><?php echo e($row->unit); ?></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-bold text-gray-600"><?php echo e((float) $row->opening_balance); ?></span>
                            </td>
                            <td class="px-6 py-4 text-right bg-emerald-50/10">
                                <span
                                    class="text-sm font-bold text-emerald-600"><?php echo e($row->total_in > 0 ? '+' . (float) $row->total_in : '0'); ?></span>
                            </td>
                            <td class="px-6 py-4 text-right bg-red-50/10">
                                <span
                                    class="text-sm font-bold text-red-500"><?php echo e($row->total_out > 0 ? '-' . (float) $row->total_out : '0'); ?></span>
                            </td>
                            <td class="px-8 py-4 text-right bg-accent/5">
                                <span class="text-lg font-black text-accent"><?php echo e((float)$row->final_balance); ?></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button 
                                    wire:click="$dispatch('show-riwayat-saldo', [<?php echo e($row->id); ?>, '<?php echo e($startDateParam); ?>', '<?php echo e($endDateParam); ?>'])"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-accent/5 hover:bg-accent text-accent hover:text-white rounded-lg text-[9px] font-black uppercase transition-all shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Riwayat
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-8 py-12 text-center text-gray-400 italic">Data tidak ditemukan untuk
                                periode ini.</td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="px-8 py-6 bg-base/20 border-t border-gray-50">
            <?php echo e($pagination->links()); ?>

        </div>
    </div>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/laporan/saldo.blade.php ENDPATH**/ ?>