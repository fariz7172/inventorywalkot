<?php

use App\Models\InventoryTransaction;
use App\Models\Material;

?>

<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Inventory</h1>
            <p class="text-sm text-gray-500 mt-0.5">Pantau mutasi barang masuk, keluar, dan sisa stok.</p>
        </div>
        <div class="flex gap-2">
            <select wire:model.live="material_id" class="bg-white rounded-xl px-4 py-2 text-sm font-bold border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
                <option value="">Semua Material</option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->allMaterials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <option value="<?php echo e($m->id); ?>"><?php echo e($m->name); ?></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </select>
            <select wire:model.live="period" class="bg-white rounded-xl px-4 py-2 text-sm font-bold border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
                <option value="weekly">Minggu Ini</option>
                <option value="monthly">Bulan Ini</option>
                <option value="yearly">Tahun Ini</option>
                <option value="all">Semua Waktu</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] shadow-card ring-1 ring-accent/5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[11px]">
                <thead>
                    <tr class="bg-warm/40 border-b border-warm/60">
                        <th class="text-left px-4 py-4 font-bold text-gray-500 uppercase">Tanggal</th>
                        <th class="text-left px-4 py-4 font-bold text-gray-500 uppercase">Jenis Material</th>
                        <th class="text-center px-4 py-4 font-bold text-gray-500 uppercase bg-emerald-50/50">Volume Masuk</th>
                        <th class="text-center px-4 py-4 font-bold text-gray-500 uppercase bg-red-50/50">Volume Keluar</th>
                        <th class="text-center px-4 py-4 font-bold text-gray-900 uppercase bg-blue-50/50">Sisa (Saldo)</th>
                        <th class="text-center px-4 py-4 font-bold text-gray-500 uppercase">Satuan</th>
                        <th class="text-left px-4 py-4 font-bold text-gray-500 uppercase">No POL</th>
                        <th class="text-left px-4 py-4 font-bold text-gray-500 uppercase">Lokasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm/40">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->reportData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <tr class="hover:bg-base/60 transition-colors">
                        <td class="px-4 py-3.5 text-gray-600"><?php echo e($trx->created_at->format('d/m/Y H:i')); ?></td>
                        <td class="px-4 py-3.5 font-bold text-gray-800"><?php echo e($trx->material->name); ?></td>
                        <td class="px-4 py-3.5 text-center text-emerald-600 font-bold bg-emerald-50/20">
                            <?php echo e($trx->volume_masuk > 0 ? '+ ' . (float)$trx->volume_masuk : '-'); ?>

                        </td>
                        <td class="px-4 py-3.5 text-center text-red-600 font-bold bg-red-50/20">
                            <?php echo e($trx->volume_keluar > 0 ? '- ' . (float)$trx->volume_keluar : '-'); ?>

                        </td>
                        <td class="px-4 py-3.5 text-center font-black text-blue-700 bg-blue-50/20">
                            <?php echo e((float)$trx->balance_after); ?>

                        </td>
                        <td class="px-4 py-3.5 text-center text-gray-500 font-medium"><?php echo e($trx->material->unit); ?></td>
                        <td class="px-4 py-3.5 font-mono font-bold uppercase text-gray-700">
                            <?php echo e($trx->deliveryOrder->no_polisi ?? '-'); ?>

                        </td>
                        <td class="px-4 py-3.5 text-gray-700 font-medium">
                            <?php echo e($trx->deliveryOrder->lokasi ?? ($trx->description ?: 'Restock')); ?>

                        </td>
                    </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/laporan/index.blade.php ENDPATH**/ ?>