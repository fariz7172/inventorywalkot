<?php

use App\Models\InventoryTransaction;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

?>

<div class="max-w-7xl mx-auto">
    <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rekapitulasi Transaksi Barang</h1>
            <p class="text-sm text-gray-500 text-pretty max-w-xl">Daftar lengkap riwayat pergerakan material (Masuk & Keluar) yang tercatat di sistem inventory.</p>
        </div>
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($search || $startDate || $endDate): ?>
        <div class="flex flex-wrap gap-2 items-center">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($search): ?>
            <div class="px-4 py-2 bg-accent/10 border border-accent/20 rounded-xl flex items-center gap-3">
                <span class="text-[10px] font-black text-accent uppercase tracking-widest">Pencarian:</span>
                <span class="text-xs font-bold text-gray-700">"<?php echo e($search); ?>"</span>
                <button wire:click="$set('search', '')" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($startDate || $endDate): ?>
            <div class="px-4 py-2 bg-blue-50 border border-blue-100 rounded-xl flex items-center gap-3">
                <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest">Periode:</span>
                <span class="text-xs font-bold text-gray-700"><?php echo e($startDate ?: '...'); ?> s/d <?php echo e($endDate ?: '...'); ?></span>
                <button wire:click="$set('startDate', ''); $set('endDate', '')" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1 bg-white p-1.5 rounded-2xl ring-1 ring-accent/5 shadow-sm">
                <button wire:click="$set('filterType', 'all')" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
                    'bg-accent text-white shadow-lg shadow-accent/20' => $filterType === 'all',
                    'text-gray-400 hover:text-accent hover:bg-accent/5' => $filterType !== 'all'
                ]); ?>">Semua</button>
                <button wire:click="$set('filterType', 'in')" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
                    'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20' => $filterType === 'in',
                    'text-gray-400 hover:text-emerald-500 hover:bg-emerald-500/5' => $filterType !== 'in'
                ]); ?>">Masuk</button>
                <button wire:click="$set('filterType', 'out')" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
                    'bg-red-500 text-white shadow-lg shadow-red-500/20' => $filterType === 'out',
                    'text-gray-400 hover:text-red-500 hover:bg-red-500/5' => $filterType !== 'out'
                ]); ?>">Keluar</button>
            </div>
            
            <div class="flex items-center gap-2 bg-white rounded-2xl px-4 py-2 ring-1 ring-accent/5 shadow-sm">
                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Periode:</label>
                <input type="date" wire:model.live="startDate" class="text-xs text-gray-600 outline-none w-28 bg-transparent">
                <span class="text-gray-300">/</span>
                <input type="date" wire:model.live="endDate" class="text-xs text-gray-600 outline-none w-28 bg-transparent">
            </div>
            <button wire:click="exportExcel" class="bg-emerald-500 text-white px-5 py-2.5 rounded-2xl text-xs font-black uppercase tracking-widest flex items-center gap-2 hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Excel
            </button>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-sm ring-1 ring-accent/5 overflow-hidden">
        <div class="p-8 border-b border-gray-50 flex flex-col md:flex-row justify-between gap-4 bg-white">
            <div></div>

            <div class="flex items-center gap-6">
                <div class="flex items-center gap-4 text-[10px] font-black uppercase tracking-tighter text-gray-400">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Barang Masuk
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        Barang Keluar
                    </div>
                </div>

                <div class="h-8 w-px bg-gray-100"></div>

                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-gray-400 uppercase">Baris:</label>
                    <select wire:model.live="perPage" class="bg-base border-none rounded-xl text-sm font-bold text-gray-700 focus:ring-2 focus:ring-accent/20 outline-none px-4 py-2">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-base/50">
                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Tanggal</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Jenis / Ref</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Ringkasan</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Sumber / Tujuan</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-center">Bukti</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Petugas</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr wire:click="openDetail('<?php echo e($t->reference_number); ?>', '<?php echo e($t->delivery_order_id); ?>', '<?php echo e($t->type); ?>', '<?php echo e($t->date); ?>', <?php echo e($t->user_id); ?>)" class="hover:bg-base/30 transition-colors group cursor-pointer">
                        <td class="px-8 py-5">
                            <span class="text-xs font-bold text-gray-700 block"><?php echo e(\Carbon\Carbon::parse($t->latest_created_at)->format('d/m/Y')); ?></span>
                            <span class="text-[10px] text-gray-400"><?php echo e(\Carbon\Carbon::parse($t->latest_created_at)->format('H:i')); ?> WIB</span>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex flex-col">
                                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                    'text-[10px] font-black uppercase tracking-widest mb-1',
                                    'text-emerald-600' => $t->type === 'in',
                                    'text-red-500' => $t->type === 'out'
                                ]); ?>">
                                    <?php echo e($t->type === 'in' ? 'Masuk' : 'Keluar'); ?>

                                </span>
                                <span class="text-sm font-black text-gray-800"><?php echo e($t->reference_number ?: ($t->deliveryOrder->surat_jalan_no ?? '-')); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-800"><?php echo e($t->total_items); ?> Item Barang</span>
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-tighter italic">Klik untuk detail</span>
                            </div>
                        </td>
                        <td class="px-6 py-5">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($t->type === 'in'): ?>
                                <span class="text-xs text-gray-600 font-medium">Dari: <span class="font-bold"><?php echo e($t->latest_supplier ?: 'Restock Internal'); ?></span></span>
                            <?php else: ?>
                                <span class="text-xs text-gray-600 font-medium">Tujuan: <span class="font-bold text-red-500"><?php echo e($t->deliveryOrder->lokasi ?? 'Pengeluaran Barang'); ?></span></span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-6 py-5 text-center">
                                <?php
                                $thumbImages = [];
                                if ($t->latest_image) $thumbImages = array_merge($thumbImages, explode(',', $t->latest_image));
                                if (isset($t->deliveryOrder->nota_dinas_photo) && $t->deliveryOrder->nota_dinas_photo) {
                                    $thumbImages = array_merge($thumbImages, explode(',', $t->deliveryOrder->nota_dinas_photo));
                                }
                                if (isset($t->deliveryOrder->progress_photo) && $t->deliveryOrder->progress_photo) {
                                    $thumbImages = array_merge($thumbImages, explode(',', $t->deliveryOrder->progress_photo));
                                }
                                $firstImg = !empty($thumbImages) ? $thumbImages[0] : null;
                            ?>
                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($firstImg): ?>
                                <?php
                                    $thumbUrls = array_map(function($img) { return Storage::url(trim($img)); }, $thumbImages);
                                ?>
                                <div class="flex justify-center relative">
                                    <img src="<?php echo e(Storage::url(trim($firstImg))); ?>" 
                                         @click.stop="$dispatch('open-lightbox', { images: <?php echo e(Js::from($thumbUrls)); ?>, index: 0 })"
                                         class="w-10 h-10 rounded-lg object-cover ring-2 ring-white shadow-sm cursor-pointer hover:opacity-90 transition-opacity">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($thumbImages) > 1): ?>
                                    <div class="absolute -top-2 -right-2 bg-accent text-white text-[9px] font-black px-1.5 py-0.5 rounded-full ring-2 ring-white shadow-sm pointer-events-none">
                                        +<?php echo e(count($thumbImages) - 1); ?>

                                    </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-gray-300 italic uppercase">No Photo</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-6 py-5">
                            <span class="text-xs font-bold text-gray-700"><?php echo e($t->user->name ?? 'System'); ?></span>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click.stop="openDetail('<?php echo e($t->reference_number); ?>', '<?php echo e($t->delivery_order_id); ?>', '<?php echo e($t->type); ?>', '<?php echo e($t->date); ?>', <?php echo e($t->user_id); ?>)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-accent/5 hover:bg-accent text-accent hover:text-white rounded-lg text-[9px] font-black uppercase transition-all shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Detail
                                </button>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasRole('superadmin') || auth()->user()->hasRole('sudin')): ?>
                                <button wire:click.stop="editGroup('<?php echo e($t->reference_number); ?>', '<?php echo e($t->delivery_order_id); ?>', '<?php echo e($t->type); ?>', '<?php echo e($t->date); ?>', <?php echo e($t->user_id); ?>)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-500 text-blue-500 hover:text-white rounded-lg text-[9px] font-black uppercase transition-all shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </td>

                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="px-8 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="text-sm font-bold text-gray-400">Tidak ada riwayat transaksi yang ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="px-8 py-6 bg-base/20 border-t border-gray-50">
            <?php echo e($transactions->links()); ?>

        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showDetailModal && $selectedGroup): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showDetailModal', false)"></div>
        <div class="relative bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up overflow-hidden">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Detail <?php echo e($selectedGroup['type'] === 'in' ? 'Surat Masuk' : 'Surat Keluar'); ?></h2>
                    <p class="text-xs text-gray-500 mt-1 font-mono">Ref: <?php echo e($selectedGroup['reference'] ?: '-'); ?></p>
                </div>
                <button wire:click="$set('showDetailModal', false)" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-8">
                <div class="bg-base/50 p-4 rounded-2xl">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Sumber / Tujuan</p>
                    <p class="text-sm font-bold text-gray-700"><?php echo e($selectedGroup['type'] === 'in' ? ($selectedGroup['supplier'] ?: 'Restock Internal') : ($selectedGroup['lokasi'] ?: 'Internal')); ?></p>
                </div>
                <div class="bg-base/50 p-4 rounded-2xl">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Petugas / Waktu</p>
                    <p class="text-sm font-bold text-gray-700"><?php echo e($selectedGroup['user']); ?> - <?php echo e(\Carbon\Carbon::parse($selectedGroup['date'])->format('d M Y')); ?></p>
                </div>
            </div>

            <div class="max-h-[40vh] overflow-y-auto mb-8 pr-2">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                            <th class="py-3 text-left">Nama Barang</th>
                            <th class="py-3 text-right">Volume</th>
                            <th class="py-3 text-left pl-4">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $selectedGroup['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="py-4">
                                <p class="font-bold text-gray-800"><?php echo e($item->material->name); ?></p>
                                <p class="text-[10px] text-gray-400 uppercase"><?php echo e($item->material->category->name ?? '-'); ?></p>
                            </td>
                            <td class="py-4 text-right">
                                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                    'font-black',
                                    'text-emerald-600' => $selectedGroup['type'] === 'in',
                                    'text-red-500' => $selectedGroup['type'] === 'out'
                                ]); ?>">
                                    <?php echo e($selectedGroup['type'] === 'in' ? '+' : '-'); ?><?php echo e((float)($selectedGroup['type'] === 'in' ? $item->volume_masuk : $item->volume_keluar)); ?>

                                </span>
                                <span class="text-[10px] font-bold text-gray-400 uppercase ml-1"><?php echo e($item->material->unit); ?></span>
                            </td>
                            <td class="py-4 pl-4">
                                <p class="text-xs text-gray-500 italic"><?php echo e($item->note ?: '-'); ?></p>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php
                $allImages = [];
                if ($selectedGroup['image']) {
                    $allImages = array_merge($allImages, explode(',', $selectedGroup['image']));
                }
                if (isset($selectedGroup['nota_dinas_photo']) && $selectedGroup['nota_dinas_photo']) {
                    $allImages = array_merge($allImages, explode(',', $selectedGroup['nota_dinas_photo']));
                }
                if (isset($selectedGroup['progress_photo']) && $selectedGroup['progress_photo']) {
                    $allImages = array_merge($allImages, explode(',', $selectedGroup['progress_photo']));
                }
            ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($allImages)): ?>
            <div class="mb-8">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Foto Bukti Fisik / Nota Dinas</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php
                        $modalThumbUrls = array_map(function($img) { return Storage::url(trim($img)); }, $allImages);
                    ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $allImages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <img src="<?php echo e(Storage::url(trim($img))); ?>" 
                             @click.stop="$dispatch('open-lightbox', { images: <?php echo e(Js::from($modalThumbUrls)); ?>, index: <?php echo e($index); ?> })"
                             class="w-full h-48 object-cover rounded-3xl ring-4 ring-base shadow-inner cursor-pointer hover:opacity-90 transition-opacity">
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="pt-4 flex gap-3 flex-wrap">
                <button onclick="printBeritaAcara()" class="flex-1 bg-emerald-500 text-white py-3.5 rounded-2xl font-black text-[10px] sm:text-xs uppercase tracking-widest shadow-xl shadow-emerald-500/20 hover:bg-emerald-600 transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak BA
                </button>
                <button onclick="printSPB()" class="flex-1 bg-blue-500 text-white py-3.5 rounded-2xl font-black text-[10px] sm:text-xs uppercase tracking-widest shadow-xl shadow-blue-500/20 hover:bg-blue-600 transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Cetak SPB
                </button>
                <button wire:click="$set('showDetailModal', false)" class="flex-1 bg-gray-900 text-white py-3.5 rounded-2xl font-black text-[10px] sm:text-xs uppercase tracking-widest shadow-xl shadow-gray-900/20 hover:bg-gray-800 transition-all">Tutup</button>
            </div>
        </div>
    </div>

    <div id="print-ba-area" class="hidden print-target bg-white p-6 text-black leading-tight" style="font-family: 'Times New Roman', serif;">
        
        <img src="<?php echo e(asset('assets/kop.png')); ?>" class="w-full h-auto mb-8">

        <?php
            $isOut = ($selectedGroup['type'] === 'out');
            $carbonDate = \Carbon\Carbon::parse($selectedGroup['date']);
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            $dayName = $days[$carbonDate->dayOfWeek];
            $monthName = $months[$carbonDate->month];
            
            $sumberTujuan = $selectedGroup['type'] === 'in' ? ($selectedGroup['supplier'] ?: 'Restock Internal') : ($selectedGroup['pemohon'] ?: ($selectedGroup['lokasi'] ?: 'Internal'));
            
            // Labels
            if ($isOut) {
                $title1 = "BERITA ACARA SERAH TERIMA BARANG";
                $title2 = "DISTRIBUSI/PENGELUARAN";
                $labelPihakSatu = 'Pengurus Barang/Pengurus Barang Pembantu';
                $labelPihakDua = 'Pemakai Persediaan';
            } else {
                $title1 = "BERITA ACARA SERAH TERIMA BARANG";
                $title2 = "PENGADAAN/PEROLEHAN";
                $labelPihakSatu = 'PPHP/PPK/PPTK/Penyediaan Barang/Pihak Ketiga/Setaranya';
                $labelPihakDua = 'Pengguna Barang/Pengurus Barang Pembantu/Pengurus Barang UPB';
            }
        ?>

        <div class="text-center mb-6">
            <h1 class="text-lg font-bold underline uppercase leading-tight">
                <?php echo e($title1); ?><br>
                <?php echo e($title2); ?>

            </h1>
            <p class="text-sm font-bold mt-1">Nomor: <?php echo e($selectedGroup['reference'] ?: '……………………………'); ?></p>
        </div>

        <div class="text-justify mb-4 text-[13px]">
            <p>Pada Hari ini <span class="font-bold"><?php echo e($dayName); ?></span> Tanggal <span class="font-bold"><?php echo e($carbonDate->day); ?></span> Bulan <span class="font-bold"><?php echo e($monthName); ?></span> Tahun <span class="font-bold"><?php echo e($carbonDate->year); ?></span> </p>
            <p>yang bertanda tangan dibawah ini:</p>
            
            <div class="mt-3 ml-8 space-y-0.5">
                <p>Nama : <span class="font-bold">M. Suherman Eka Putra</span></p>
                <p>Jabatan : <span class="font-bold text-[11px]"><?php echo e($isOut ? $labelPihakSatu : $labelPihakDua); ?></span></p>
            </div>

            <p class="mt-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isOut): ?>
                    Telah menyerahkan barang persedian yang diterima oleh <span class="font-bold text-sm underline"><?php echo e($sumberTujuan); ?></span> 
                <?php else: ?>
                    Telah menerima barang persedian yang diserahkan oleh PPHP/PPK/PPTK/Penyedia Barang/Pihak Ketiga <span class="font-bold text-sm underline"><?php echo e($sumberTujuan); ?></span> 
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                sesuai dengan Berita Acara Pemeriksaan Barang Nomor <span class="font-bold"><?php echo e($selectedGroup['reference'] ?: '……'); ?></span> 
                Tanggal <span class="font-bold"><?php echo e($carbonDate->day); ?></span> Bulan <span class="font-bold"><?php echo e($monthName); ?></span> Tahun <span class="font-bold"><?php echo e($carbonDate->year); ?></span>. 
                Sebagaimana daftar terlampir. Daftar barang yang <?php echo e($isOut ? 'diserahkan' : 'diterima'); ?> sebagai berikut:
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
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $selectedGroup['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="border border-black px-2 py-1.5 text-center"><?php echo e($index + 1); ?></td>
                    <td class="border border-black px-3 py-1.5 font-bold uppercase"><?php echo e($item->material->name); ?></td>
                    <td class="border border-black px-3 py-1.5 text-center uppercase"><?php echo e($item->material->unit); ?></td>
                    <td class="border border-black px-3 py-1.5 text-center font-bold text-sm">
                        <?php echo e((float)($isOut ? $item->volume_keluar : $item->volume_masuk)); ?>

                    </td>
                    <td class="border border-black px-3 py-1.5 italic text-[10px]"><?php echo e($item->note ?: '-'); ?></td>
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
                
                <p class="font-bold underline uppercase">M. Suherman Eka Putra</p>
            </div>
            <div>
                <p class="invisible">Jakarta, ...</p>
                <p class="mt-1">Yang menerima Barang,</p>
                <p class="font-bold text-[10px] uppercase max-w-[200px] mx-auto leading-tight mt-1"><?php echo e($labelPihakDua); ?> /Penguna Barang</p>
                
                <div class="h-20"></div>
                
                <p class="font-bold underline uppercase"><?php echo e($isOut ? $sumberTujuan : $selectedGroup['user']); ?></p>
            </div>
        </div>
    </div>

    <div id="print-spb-area" class="hidden print-target bg-white p-6 text-black leading-tight" style="font-family: 'Times New Roman', serif;">
        <img src="<?php echo e(asset('assets/kop.png')); ?>" class="w-full h-auto mb-8">

        <div class="text-center mb-6">
            <h1 class="text-lg font-bold underline uppercase leading-tight">
                SURAT PERMINTAAN BARANG (SPB)
            </h1>
            <p class="text-sm font-bold mt-1">Nomor: <?php echo e($selectedGroup['reference'] ?: '……………………………'); ?></p>
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
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $selectedGroup['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td class="border border-black px-2 py-1.5 text-center"><?php echo e($index + 1); ?></td>
                    <td class="border border-black px-3 py-1.5 font-bold uppercase"><?php echo e($item->material->name); ?></td>
                    <td class="border border-black px-3 py-1.5 text-center font-bold text-sm">
                        <?php echo e((float)($selectedGroup['type'] === 'out' ? $item->volume_keluar : $item->volume_masuk)); ?> <?php echo e($item->material->unit); ?>

                    </td>
                    <td class="border border-black px-3 py-1.5 italic text-[10px]"><?php echo e($item->note ?: '-'); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>

        <div class="grid grid-cols-2 text-center text-[13px] mt-12">
            <div>
               <br>
                <p class="mt-1">Mengetahui,</p>
                <p class="font-bold text-[11px] uppercase max-w-[200px] mx-auto leading-tight mt-1">
                   Kepala Gudang
                </p>
                
                <div class="h-24"></div>
                
                <p class="font-bold  uppercase">SANJAYA</p>
               
            </div>
            <div>
                <p>Jakarta, <?php echo e($carbonDate->day ?? \Carbon\Carbon::now()->day); ?> <?php echo e($monthName ?? \Carbon\Carbon::now()->translatedFormat('F')); ?> <?php echo e($carbonDate->year ?? \Carbon\Carbon::now()->year); ?></p>
                <p class="mt-1">Yang Meminta Barang,</p>
                <p class="font-bold text-[11px] uppercase max-w-[200px] mx-auto leading-tight mt-1">
                    Penerima Barang
                </p>
                
                <div class="h-24"></div>
                
                <p class="font-bold  uppercase"><?php echo e($selectedGroup['type'] === 'out' ? ($selectedGroup['pemohon'] ?: '______________________') : ($selectedGroup['user'] ?? '______________________')); ?></p>
                
            </div>
        </div>
    </div>

    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div x-data="{ 
            open: false, 
            images: [], 
            currentIndex: 0,
            get currentSrc() { return this.images[this.currentIndex] || ''; },
            next() { if (this.currentIndex < this.images.length - 1) this.currentIndex++; else this.currentIndex = 0; },
            prev() { if (this.currentIndex > 0) this.currentIndex--; else this.currentIndex = this.images.length - 1; }
         }" 
         @open-lightbox.window="
            if (typeof $event.detail === 'string') {
                images = [$event.detail];
                currentIndex = 0;
            } else {
                images = $event.detail.images || [];
                currentIndex = $event.detail.index || 0;
            }
            open = true;
         "
         x-show="open" 
         @keydown.escape.window="open = false"
         @keydown.arrow-right.window="if(open) next()"
         @keydown.arrow-left.window="if(open) prev()"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
         style="display: none;">
        
        <button @click="open = false" class="absolute top-6 right-6 text-white/50 hover:text-white p-2 transition-colors z-50">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <template x-if="images.length > 1">
            <button @click.stop="prev()" class="absolute left-4 sm:left-8 text-white/50 hover:text-white p-2 transition-colors z-50">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
        </template>

        <img :src="currentSrc" @click.away="open = false" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl object-contain relative z-40">

        <template x-if="images.length > 1">
            <button @click.stop="next()" class="absolute right-4 sm:right-8 text-white/50 hover:text-white p-2 transition-colors z-50">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </template>

        <template x-if="images.length > 1">
            <div class="absolute bottom-6 left-1/2 -translate-x-1/2 bg-black/50 px-4 py-2 rounded-full text-white text-xs font-bold tracking-widest z-50">
                <span x-text="currentIndex + 1"></span> / <span x-text="images.length"></span>
            </div>
        </template>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showEditModal): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" wire:click="$set('showEditModal', false)"></div>
        <div class="relative bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up overflow-hidden">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Edit Data Transaksi</h2>
                    <p class="text-xs text-gray-500 mt-1 font-mono">Ref: <?php echo e($editingMeta['reference'] ?: '-'); ?> (<?php echo e($editingMeta['type'] === 'in' ? 'Masuk' : 'Keluar'); ?>)</p>
                </div>
                <button wire:click="$set('showEditModal', false)" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-base/50 p-6 rounded-3xl border border-gray-100">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 block">No. Surat Jalan / Ref</label>
                    <input type="text" wire:model="editingMeta.reference" 
                        class="w-full bg-white border-none rounded-2xl px-4 py-3 text-sm font-bold text-gray-700 ring-1 ring-gray-100 focus:ring-2 focus:ring-accent/20 outline-none">
                </div>
                <div class="bg-base/50 p-6 rounded-3xl border border-gray-100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($editingMeta['type'] === 'in'): ?>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 block">Supplier / Sumber</label>
                        <input type="text" wire:model="editingMeta.supplier" 
                            class="w-full bg-white border-none rounded-2xl px-4 py-3 text-sm font-bold text-gray-700 ring-1 ring-gray-100 focus:ring-2 focus:ring-accent/20 outline-none">
                    <?php else: ?>
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 block">Lokasi / Tujuan</label>
                        <input type="text" wire:model="editingMeta.lokasi" 
                            class="w-full bg-white border-none rounded-2xl px-4 py-3 text-sm font-bold text-gray-700 ring-1 ring-gray-100 focus:ring-2 focus:ring-accent/20 outline-none">
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <div class="max-h-[40vh] overflow-y-auto mb-8 pr-2">

                <div class="space-y-6">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $editingItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="bg-base/30 p-6 rounded-3xl border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-black text-gray-900 uppercase tracking-widest"><?php echo e($item['material_name']); ?></span>
                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                'px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest',
                                'bg-emerald-100 text-emerald-600' => $item['type'] === 'in',
                                'bg-red-100 text-red-600' => $item['type'] === 'out'
                            ]); ?>">
                                <?php echo e($item['type'] === 'in' ? 'Masuk' : 'Keluar'); ?>

                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 block">Volume / Jumlah</label>
                                <div class="relative">
                                    <input type="number" step="any" wire:model="editingItems.<?php echo e($index); ?>.volume" 
                                        class="w-full bg-white border-none rounded-2xl px-4 py-3 text-sm font-bold text-gray-700 ring-1 ring-gray-100 focus:ring-2 focus:ring-accent/20 outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 block">Keterangan / Catatan</label>
                                <input type="text" wire:model="editingItems.<?php echo e($index); ?>.note" 
                                    class="w-full bg-white border-none rounded-2xl px-4 py-3 text-sm font-bold text-gray-700 ring-1 ring-gray-100 focus:ring-2 focus:ring-accent/20 outline-none"
                                    placeholder="Opsional...">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <div class="flex gap-3">
                <button wire:click="saveEdit" wire:loading.attr="disabled" class="flex-1 bg-accent text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-accent/20 hover:bg-accent/90 transition-all flex items-center justify-center gap-2">
                    <span wire:loading.remove>Simpan Perubahan</span>
                    <span wire:loading>Menyimpan...</span>
                </button>
                <button wire:click="$set('showEditModal', false)" class="px-8 bg-gray-100 text-gray-500 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 transition-all">Batal</button>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


    <style>
        .print-target { display: none; }
        
        @media print {
            @page { margin: 1cm; }
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
                padding: 0 !important;
            }
        }
    </style>

    <script>
        function printBeritaAcara() {
            const originalTitle = document.title;
            const ref = "<?php echo e($selectedGroup['reference'] ?? ''); ?>";
            document.title = " " + ref;
            
            document.querySelectorAll('.print-target').forEach(el => el.classList.remove('print-active'));
            document.getElementById('print-ba-area').classList.add('print-active');
            
            window.print();
            
            document.getElementById('print-ba-area').classList.remove('print-active');
            document.title = originalTitle;
        }

        function printSPB() {
            const originalTitle = document.title;
            const ref = "<?php echo e($selectedGroup['reference'] ?? ''); ?>";
            document.title = " " + ref;
            
            document.querySelectorAll('.print-target').forEach(el => el.classList.remove('print-active'));
            document.getElementById('print-spb-area').classList.add('print-active');
            
            window.print();
            
            document.getElementById('print-spb-area').classList.remove('print-active');
            document.title = originalTitle;
        }
    </script>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/laporan/barang-masuk.blade.php ENDPATH**/ ?>