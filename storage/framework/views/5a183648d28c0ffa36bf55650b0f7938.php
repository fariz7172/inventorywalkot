<?php $__env->startSection('title', 'Dashboard Inventory'); ?>

<?php $__env->startSection('content'); ?>

<?php
    // Fecth real data
    $totalMaterials = \App\Models\Material::count();
    $lowStockCount = \App\Models\Material::where('current_volume', '<=', 5)->count();
    $inToday = \App\Models\InventoryTransaction::where('type', 'in')->whereDate('created_at', today())->count();
    $outToday = \App\Models\InventoryTransaction::where('type', 'out')->whereDate('created_at', today())->count();
    
    $recentSJ = \App\Models\DeliveryOrder::latest()->take(5)->get();
    $recentActivities = \App\Models\InventoryTransaction::with(['material', 'user'])->latest()->take(5)->get();

    $stats = [
        [
            'label' => 'Total Material',
            'value' => $totalMaterials,
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'color' => 'bg-accent/10 text-accent',
            'ring' => 'ring-accent/20'
        ],
        [
            'label' => 'Stok Menipis (<= 5)',
            'value' => $lowStockCount,
            'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            'color' => 'bg-red-100 text-red-600',
            'ring' => 'ring-red-200'
        ],
        [
            'label' => 'Barang Masuk Hari Ini',
            'value' => $inToday,
            'icon' => 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4',
            'color' => 'bg-emerald-100 text-emerald-600',
            'ring' => 'ring-emerald-200'
        ],
        [
            'label' => 'Barang Keluar Hari Ini',
            'value' => $outToday,
            'icon' => 'M17 16V4m0 0l4 4m-4-4l-4 4M7 4v12m0 0l-4-4m4 4l4-4',
            'color' => 'bg-amber-100 text-amber-600',
            'ring' => 'ring-amber-200'
        ],
    ];
?>


<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Halo, <?php echo e(auth()->user()->name); ?> 👋</h1>
    <p class="text-sm text-gray-500 mt-0.5"><?php echo e(now()->translatedFormat('l, d F Y')); ?> — Ringkasan operasional gudang Anda.</p>
</div>


<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="bg-white rounded-2xl p-4 lg:p-5 shadow-card ring-1 <?php echo e($s['ring']); ?>">
        <div class="flex items-start justify-between mb-4">
            <div class="w-11 h-11 rounded-xl <?php echo e($s['color']); ?> flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="<?php echo e($s['icon']); ?>"/>
                </svg>
            </div>
        </div>
        <p class="text-2xl font-black text-gray-900"><?php echo e($s['value']); ?></p>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1"><?php echo e($s['label']); ?></p>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-[2rem] shadow-card ring-1 ring-accent/10 overflow-hidden">
            <div class="flex items-center justify-between px-8 py-6 border-b border-warm/60">
                <div>
                    <h2 class="font-bold text-gray-800">Surat Jalan Terbaru</h2>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-tighter">Monitoring pengiriman barang</p>
                </div>
                <a href="/dashboard/surat-jalan" class="text-xs font-bold text-accent hover:underline">Lihat Semua →</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-base/50">
                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">No. SJ</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Tujuan</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Tanggal</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-center">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-sm">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentSJ; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sj): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-base/30 transition-colors group">
                            <td class="px-8 py-4 font-black text-accent"><?php echo e($sj->surat_jalan_no); ?></td>
                            <td class="px-6 py-4 font-bold text-gray-700"><?php echo e($sj->lokasi); ?></td>
                            <td class="px-6 py-4 text-xs text-gray-500 font-medium"><?php echo e($sj->tanggal->format('d/m/Y')); ?></td>
                            <td class="px-6 py-4 text-center">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sj->status === 'draft'): ?>
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-600 text-[10px] font-black uppercase">Draft</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest">Shipped</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button onclick="Livewire.dispatch('show-sj-detail', [<?php echo e($sj->id); ?>])" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-accent/5 hover:bg-accent text-accent hover:text-white rounded-lg text-[10px] font-black uppercase transition-all shadow-sm group-hover:shadow-md">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Detail
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="px-8 py-12 text-center text-gray-400 italic text-sm">Belum ada pengiriman barang.</td>
                        </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <div class="bg-white rounded-[2rem] shadow-card ring-1 ring-accent/10 p-6">
        <h2 class="font-bold text-gray-800 mb-1">Aktivitas Gudang</h2>
        <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-6">Log transaksi terbaru</p>

        <div class="space-y-6">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentActivities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $act): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="flex gap-4 relative group">
                <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm',
                    'bg-emerald-50 text-emerald-600' => $act->type === 'in',
                    'bg-red-50 text-red-500' => $act->type === 'out'
                ]); ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($act->type === 'in'): ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        <?php else: ?>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-gray-800 truncate"><?php echo e($act->material->name); ?></p>
                    <p class="text-[11px] text-gray-500 font-medium">
                        <?php echo e($act->type === 'in' ? 'Masuk' : 'Keluar'); ?>: 
                        <span class="font-black <?php echo e($act->type === 'in' ? 'text-emerald-600' : 'text-red-500'); ?>">
                            <?php echo e((float)($act->type === 'in' ? $act->volume_masuk : $act->volume_keluar)); ?> <?php echo e($act->material->unit); ?>

                        </span>
                    </p>
                    <p class="text-[9px] text-gray-400 mt-0.5 uppercase font-bold tracking-tighter"><?php echo e($act->created_at->diffForHumans()); ?></p>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-center py-12 text-gray-400 italic text-sm">
                Belum ada aktivitas hari ini.
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="mt-8 pt-4 border-t border-gray-50">
            <a href="/dashboard/laporan/barang-masuk" class="block text-center text-[10px] font-black text-accent uppercase tracking-widest hover:underline">
                Lihat Semua Laporan →
            </a>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>