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
            <button wire:click="exportExcel"
                class="bg-blue-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-500/20 flex items-center gap-2 hover:bg-blue-600 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Bulanan
            </button>

            <button onclick="printReport()"
                class="bg-gray-800 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-gray-800/20 flex items-center gap-2 hover:bg-gray-900 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak (Print)
            </button>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                <button wire:click="exportWeekly"
                    class="bg-emerald-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-emerald-500/20 flex items-center gap-2 hover:bg-emerald-600 transition-all animate-fade-in">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Mingguan
                </button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <button wire:click="resetFilters"
                class="text-[10px] font-bold text-red-500 hover:text-red-600 uppercase tracking-widest px-2 transition-colors border border-red-200 rounded-lg py-2 bg-red-50/50">Reset
                Filter</button>

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

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                <div class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 5H11V7H13V5ZM13 9H11V11H13V9ZM13 13H11V15H13V13ZM13 17H11V19H13V17ZM17 5H15V7H17V5ZM17 9H15V11H17V9ZM17 13H15V15H17V13ZM17 17H15V19H17V17ZM21 5H19V7H21V5ZM21 9H19V11H21V9ZM21 13H19V15H21V13ZM21 17H19V19H21V17ZM9 5H7V7H9V5ZM9 9H7V11H9V9ZM9 13H7V15H9V13ZM9 17H7V19H9V17ZM5 5H3V7H5V5ZM5 9H3V11H5V9ZM5 13H3V15H5V13ZM5 17H3V19H5V17Z" />
                    </svg>
                    Geser tabel ke kanan untuk rincian harian
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
            <!-- Top Scrollbar for Long Tables -->
            <div id="top-scroll-container"
                class="overflow-x-auto overflow-y-hidden border-b border-gray-50 bg-gray-50/30 sticky top-0 z-30"
                style="height: 12px;">
                <div id="top-scroll-content" style="height: 12px;"></div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div id="table-scroll-container" class="overflow-x-auto">
            <table id="report-table" class="w-full text-left">
                <thead>
                    <tr class="bg-base/50">
                        <th
                            class="sticky left-0 z-20 bg-gray-50/95 backdrop-blur px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 min-w-[300px]">
                            Material
                        </th>
                        <th
                            class="sticky left-[300px] z-20 bg-gray-50/95 backdrop-blur px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-center min-w-[100px]">
                            Satuan</th>
                        <th
                            class="sticky left-[400px] z-20 bg-gray-50/95 backdrop-blur px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right min-w-[150px]">
                            Saldo Awal</th>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                            <?php
                                $indoDays = [
                                    'Monday' => 'Senin',
                                    'Tuesday' => 'Selasa',
                                    'Wednesday' => 'Rabu',
                                    'Thursday' => 'Kamis',
                                    'Friday' => 'Jumat',
                                    'Saturday' => 'Sabtu',
                                    'Sunday' => 'Minggu'
                                ];
                            ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <th
                                    class="px-4 py-4 text-[10px] font-black uppercase tracking-widest text-emerald-500 text-center min-w-[100px] bg-emerald-50/20">
                                    <?php echo e($indoDays[$day->format('l')]); ?><br>
                                    <span class="text-[9px] text-emerald-400 font-bold">(<?php echo e($day->format('d/m')); ?>)</span>
                                </th>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                            <th
                                class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-emerald-600 text-right bg-emerald-50/30 min-w-[120px]">
                                Jumlah Masuk</th>
                            <th
                                class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-blue-600 text-right bg-blue-50/30 min-w-[120px]">
                                Jumlah Stok</th>
                        <?php else: ?>
                            <th
                                class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-emerald-600 text-right bg-emerald-50/30 min-w-[120px]">
                                Total Masuk (+)</th>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <th
                                    class="px-4 py-4 text-[10px] font-black uppercase tracking-widest text-red-500 text-center min-w-[100px] bg-red-50/20">
                                    <?php echo e($indoDays[$day->format('l')]); ?><br>
                                    <span class="text-[9px] text-red-400 font-bold">(<?php echo e($day->format('d/m')); ?>)</span>
                                </th>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <th
                            class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-red-600 text-right bg-red-50/30 min-w-[120px]">
                            <?php echo e($week ? 'Jumlah Keluar' : 'Total Keluar (-)'); ?>

                        </th>
                        <th
                            class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-900 text-right min-w-[150px]">
                            <?php echo e($week ? 'Stock Sisa' : 'Saldo Akhir'); ?>

                        </th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors group">
                            <td class="sticky left-0 z-10 bg-white group-hover:bg-gray-50 transition-colors px-8 py-4">
                                <span class="text-xs font-bold text-gray-800"><?php echo e($item->name); ?></span>
                                <span
                                    class="block text-[9px] text-gray-400 uppercase tracking-tighter"><?php echo e($item->category); ?></span>
                            </td>
                            <td
                                class="sticky left-[300px] z-10 bg-white group-hover:bg-gray-50 transition-colors px-6 py-4 text-center">
                                <span class="text-[10px] font-bold text-gray-500 uppercase"><?php echo e($item->unit); ?></span>
                            </td>
                            <td
                                class="sticky left-[400px] z-10 bg-white group-hover:bg-gray-50 transition-colors px-6 py-4 text-right">
                                <span
                                    class="text-xs font-black text-gray-700"><?php echo e(number_format($item->opening_balance, 0, ',', '.')); ?></span>
                            </td>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $item->daily_in; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <td class="px-4 py-4 text-center <?php echo e($val === null ? 'bg-gray-100/50' : 'bg-emerald-50/5'); ?>">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($val === null): ?>
                                            <span class="text-[11px] text-gray-300 font-bold">-</span>
                                        <?php else: ?>
                                            <span class="text-[11px] <?php echo e($val > 0 ? 'font-bold text-emerald-600' : 'text-gray-400'); ?>">
                                                <?php echo e($val > 0 ? number_format($val, 0, ',', '.') : '0'); ?>

                                            </span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                                <td class="px-6 py-4 text-right bg-emerald-50/20">
                                    <span
                                        class="text-xs font-black text-emerald-600"><?php echo e(number_format($item->total_in, 0, ',', '.')); ?></span>
                                </td>
                                <td class="px-6 py-4 text-right bg-blue-50/20">
                                    <span
                                        class="text-xs font-black text-blue-600"><?php echo e(number_format($item->jumlah_stok, 0, ',', '.')); ?></span>
                                </td>
                            <?php else: ?>
                                <td class="px-6 py-4 text-right bg-emerald-50/20">
                                    <span
                                        class="text-xs font-black text-emerald-600"><?php echo e(number_format($item->total_in, 0, ',', '.')); ?></span>
                                </td>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $item->daily_out; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <td class="px-4 py-4 text-center <?php echo e($val === null ? 'bg-gray-100/50' : 'bg-red-50/5'); ?>">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($val === null): ?>
                                            <span class="text-[11px] text-gray-300 font-bold">-</span>
                                        <?php else: ?>
                                            <span class="text-[11px] <?php echo e($val > 0 ? 'font-bold text-red-600' : 'text-gray-400'); ?>">
                                                <?php echo e($val > 0 ? number_format($val, 0, ',', '.') : '0'); ?>

                                            </span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <td class="px-6 py-4 text-right bg-red-50/20">
                                <span
                                    class="text-xs font-black text-red-600"><?php echo e(number_format($item->total_out, 0, ',', '.')); ?></span>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <span
                                    class="text-xs font-black text-gray-900"><?php echo e(number_format($item->final_balance, 0, ',', '.')); ?></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    wire:click="$dispatch('show-riwayat-saldo', [<?php echo e($item->id); ?>, '<?php echo e($startDateParam); ?>', '<?php echo e($endDateParam); ?>'])"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-accent/5 hover:bg-accent text-accent hover:text-white rounded-lg text-[9px] font-black uppercase transition-all shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
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

    <script>
        function printReport() {
            const originalTitle = document.title;
            document.title = "Laporan Saldo & Mutasi - <?php echo e($periodLabel); ?>";

            document.getElementById('print-area').classList.add('print-active');
            window.print();
            document.getElementById('print-area').classList.remove('print-active');

            document.title = originalTitle;
        }

        document.addEventListener('livewire:initialized', () => {
            const syncScroll = () => {
                const topScroll = document.getElementById('top-scroll-container');
                const topContent = document.getElementById('top-scroll-content');
                const tableScroll = document.getElementById('table-scroll-container');
                const table = document.getElementById('report-table');

                if (topScroll && tableScroll && table && topContent) {
                    // Set top content width to match table width
                    topContent.style.width = table.scrollWidth + 'px';

                    // Sync Top to Bottom
                    topScroll.onscroll = function () {
                        tableScroll.scrollLeft = topScroll.scrollLeft;
                    };

                    // Sync Bottom to Top
                    tableScroll.onscroll = function () {
                        topScroll.scrollLeft = tableScroll.scrollLeft;
                    };
                }
            };

            // Run on init
            syncScroll();

            // Re-run after Livewire updates
            Livewire.hook('morph.updated', (el, component) => {
                syncScroll();
            });
        });
    </script>

    <div id="print-area" class="hidden print-target bg-white text-black text-sm"
        style="font-family: 'Times New Roman', serif;">
        <img src="<?php echo e(asset('assets/kop.png')); ?>" class="w-full h-auto mb-6">

        <div class="text-center mb-6">
            <h1 class="text-xl font-bold uppercase leading-tight">LAPORAN MUTASI BARANG PERSEDIAAN</h1>
            <p class="text-md font-bold mt-1">Periode: <?php echo e($periodLabel); ?></p>
        </div>

        <table class="w-full border-collapse border border-black text-[9px]">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-black px-1 py-1 text-center w-6" rowspan="<?php echo e($week ? '2' : '1'); ?>">No</th>
                    <th class="border border-black px-2 py-1 text-left" rowspan="<?php echo e($week ? '2' : '1'); ?>">Nama Material
                    </th>
                    <th class="border border-black px-1 py-1 text-center" rowspan="<?php echo e($week ? '2' : '1'); ?>">Satuan</th>
                    <th class="border border-black px-1 py-1 text-right" rowspan="<?php echo e($week ? '2' : '1'); ?>">Saldo Awal
                    </th>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                        <th class="border border-black px-1 py-1 text-center" colspan="7">Masuk Harian</th>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <th class="border border-black px-1 py-1 text-right" rowspan="<?php echo e($week ? '2' : '1'); ?>">
                        <?php echo e($week ? 'Jumlah Masuk' : 'Total Masuk'); ?>

                    </th>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                        <th class="border border-black px-1 py-1 text-right" rowspan="2">Jumlah Stok</th>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                        <th class="border border-black px-1 py-1 text-center" colspan="7">Keluar Harian</th>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <th class="border border-black px-1 py-1 text-right" rowspan="<?php echo e($week ? '2' : '1'); ?>">
                        <?php echo e($week ? 'Jumlah Keluar' : 'Total Keluar'); ?>

                    </th>

                    <th class="border border-black px-1 py-1 text-right font-black" rowspan="<?php echo e($week ? '2' : '1'); ?>">
                        <?php echo e($week ? 'Stock Sisa' : 'Saldo Akhir'); ?>

                    </th>
                </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                    <?php
                        $indoDaysShort = [
                            'Monday' => 'Sen',
                            'Tuesday' => 'Sel',
                            'Wednesday' => 'Rab',
                            'Thursday' => 'Kam',
                            'Friday' => 'Jum',
                            'Saturday' => 'Sab',
                            'Sunday' => 'Min'
                        ];
                    ?>
                    <tr class="bg-gray-50">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th class="border border-black px-1 py-0.5 text-center text-[8px]">
                                <?php echo e($indoDaysShort[$day->format('l')]); ?><br><?php echo e($day->format('d/m')); ?>

                            </th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $days; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th class="border border-black px-1 py-0.5 text-center text-[8px]">
                                <?php echo e($indoDaysShort[$day->format('l')]); ?><br><?php echo e($day->format('d/m')); ?>

                            </th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="border border-black px-1 py-1 text-center"><?php echo e($index + 1); ?></td>
                        <td class="border border-black px-2 py-1 font-bold uppercase"><?php echo e($item->name); ?></td>
                        <td class="border border-black px-1 py-1 text-center uppercase"><?php echo e($item->unit); ?></td>
                        <td class="border border-black px-1 py-1 text-right">
                            <?php echo e(number_format($item->opening_balance, 0, ',', '.')); ?>

                        </td>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $item->daily_in; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td class="border border-black px-1 py-1 text-center <?php echo e($val === null ? 'bg-gray-100' : ''); ?>">
                                    <?php echo e($val === null ? '-' : ($val > 0 ? number_format($val, 0, ',', '.') : '-')); ?>

                                </td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <td class="border border-black px-1 py-1 text-right font-bold">
                            <?php echo e(number_format($item->total_in, 0, ',', '.')); ?>

                        </td>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                            <td class="border border-black px-1 py-1 text-right font-bold bg-gray-50">
                                <?php echo e(number_format($item->jumlah_stok, 0, ',', '.')); ?>

                            </td>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($week): ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $item->daily_out; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td class="border border-black px-1 py-1 text-center <?php echo e($val === null ? 'bg-gray-100' : ''); ?>">
                                    <?php echo e($val === null ? '-' : ($val > 0 ? number_format($val, 0, ',', '.') : '-')); ?>

                                </td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <td class="border border-black px-1 py-1 text-right font-bold">
                            <?php echo e(number_format($item->total_out, 0, ',', '.')); ?>

                        </td>

                        <td class="border border-black px-1 py-1 text-right font-black bg-gray-100">
                            <?php echo e(number_format($item->final_balance, 0, ',', '.')); ?>

                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e($week ? '21' : '7'); ?>" class="border border-black px-2 py-4 text-center italic">
                            Tidak ada data</td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>

        <div class="mt-8 text-right pr-12 text-[12px]">
            <p>Jakarta, <?php echo e(\Carbon\Carbon::now()->format('d F Y')); ?></p>
            <p class="mt-1">Petugas / Admin,</p>
            <div class="h-16"></div>
            <p class="font-bold underline uppercase"><?php echo e(auth()->user()->name); ?></p>
        </div>
    </div>

    <style>
        .print-target {
            display: none;
        }

        @media print {
            @page {
                size: landscape;
                margin: 1cm;
            }

            body * {
                visibility: hidden;
            }

            .print-active,
            .print-active * {
                visibility: visible;
            }

            .print-active {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                display: block !important;
                padding: 0 !important;
            }
        }
    </style>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/laporan/saldo.blade.php ENDPATH**/ ?>