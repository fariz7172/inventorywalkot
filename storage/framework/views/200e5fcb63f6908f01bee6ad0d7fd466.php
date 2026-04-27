<table>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $first = $items->first();
        ?>
        
        <thead>
            <tr>
                <th style="background-color: #4F46E5; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">Tanggal</th>
                <th style="background-color: #4F46E5; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">Jam</th>
                <th style="background-color: #4F46E5; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">Jenis</th>
                <th style="background-color: #4F46E5; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">No. Referensi</th>
            </tr>
            <tr>
                <td style="border: 1px solid #000000; text-align: center;"><?php echo e($first->created_at->format('d/m/Y')); ?></td>
                <td style="border: 1px solid #000000; text-align: center;"><?php echo e($first->created_at->format('H:i')); ?></td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold; color: <?php echo e($first->type === 'in' ? '#059669' : '#DC2626'); ?>;">
                    <?php echo e($first->type === 'in' ? 'MASUK' : 'KELUAR'); ?>

                </td>
                <td style="border: 1px solid #000000; font-weight: bold;"><?php echo e($first->reference_number ?: '-'); ?></td>
            </tr>
            
            <tr>
                <th style="background-color: #4338CA; color: #ffffff; font-weight: bold; border: 1px solid #000000;">Material</th>
                <th style="background-color: #4338CA; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">Volume</th>
                <th style="background-color: #4338CA; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">Satuan</th>
                <th style="background-color: #4338CA; color: #ffffff; font-weight: bold; border: 1px solid #000000;">Sumber / Tujuan</th>
                <th style="background-color: #4338CA; color: #ffffff; font-weight: bold; border: 1px solid #000000;">Petugas</th>
                <th style="background-color: #4338CA; color: #ffffff; font-weight: bold; border: 1px solid #000000;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td style="border: 1px solid #000000;"><?php echo e($item->material->name); ?></td>
                    <td style="border: 1px solid #000000; text-align: right; font-weight: bold;"><?php echo e((float)($item->type === 'in' ? $item->volume_masuk : $item->volume_keluar)); ?></td>
                    <td style="border: 1px solid #000000; text-align: center;"><?php echo e($item->material->unit); ?></td>
                    <td style="border: 1px solid #000000;"><?php echo e($item->type === 'in' ? ($item->supplier ?: 'Restock Internal') : ($item->deliveryOrder->lokasi ?? 'Pengeluaran')); ?></td>
                    <td style="border: 1px solid #000000;"><?php echo e($item->user->name ?? 'System'); ?></td>
                    <td style="border: 1px solid #000000;"><?php echo e($item->note ?: '-'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            
            <tr><td colspan="6"></td></tr>
        </tbody>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</table>
<?php /**PATH D:\program file\Project Kantor\Inventory\resources\views/exports/transaction-history.blade.php ENDPATH**/ ?>