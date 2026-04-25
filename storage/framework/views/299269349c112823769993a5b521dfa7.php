<table>
    <thead>
        <tr>
            <th colspan="7" style="font-weight: bold; text-align: center;">LAPORAN REKAPITULASI INVENTORY</th>
        </tr>
        <tr>
            <th colspan="7" style="text-align: center;">Periode: <?php echo e(strtoupper($period)); ?> (Dicetak pada: <?php echo e(now()->format('d/m/Y H:i')); ?>)</th>
        </tr>
        <tr></tr>
    </thead>
    <tbody>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $grouped; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $materialId => $transactions): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php 
                $material = $transactions->first()->material;
                $totalMasuk = $transactions->sum('volume_masuk');
                $totalKeluar = $transactions->sum('volume_keluar');
                $saldoAkhir = $transactions->last()->balance_after;
            ?>
            <tr>
                <td colspan="7" style="background-color: #f3f4f6; font-weight: bold;">JENIS MATERIAL: <?php echo e(strtoupper($material->name)); ?></td>
            </tr>
            <tr style="background-color: #e5e7eb; font-weight: bold;">
                <th style="border: 1px solid #000000;">Tanggal</th>
                <th style="border: 1px solid #000000;">No. Referensi</th>
                <th style="border: 1px solid #000000;">Volume Masuk</th>
                <th style="border: 1px solid #000000;">Volume Keluar</th>
                <th style="border: 1px solid #000000;">Satuan</th>
                <th style="border: 1px solid #000000;">No. POL</th>
                <th style="border: 1px solid #000000;">Lokasi / Keterangan</th>
            </tr>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td style="border: 1px solid #000000;"><?php echo e($trx->created_at->format('d/m/Y H:i')); ?></td>
                <td style="border: 1px solid #000000;"><?php echo e($trx->reference_number ?: '-'); ?></td>
                <td style="border: 1px solid #000000; text-align: center;"><?php echo e($trx->volume_masuk > 0 ? (float)$trx->volume_masuk : '0'); ?></td>
                <td style="border: 1px solid #000000; text-align: center;"><?php echo e($trx->volume_keluar > 0 ? (float)$trx->volume_keluar : '0'); ?></td>
                <td style="border: 1px solid #000000; text-align: center;"><?php echo e($material->unit); ?></td>
                <td style="border: 1px solid #000000;"><?php echo e($trx->deliveryOrder->no_polisi ?? '-'); ?></td>
                <td style="border: 1px solid #000000;"><?php echo e($trx->deliveryOrder->lokasi ?? ($trx->description ?: 'Restock')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <tr style="font-weight: bold;">
                <td colspan="2" style="border: 1px solid #000000; text-align: right; background-color: #f9fafb;">TOTAL MUTASI:</td>
                <td style="border: 1px solid #000000; text-align: center; background-color: #ecfdf5;"><?php echo e((float)$totalMasuk); ?></td>
                <td style="border: 1px solid #000000; text-align: center; background-color: #fef2f2;"><?php echo e((float)$totalKeluar); ?></td>
                <td colspan="2" style="border: 1px solid #000000; text-align: right; background-color: #eff6ff;">SISA (SALDO):</td>
                <td style="border: 1px solid #000000; text-align: center; background-color: #dbeafe; font-weight: black;"><?php echo e((float)$saldoAkhir); ?> <?php echo e($material->unit); ?></td>
            </tr>
            <tr></tr>
            <tr></tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </tbody>
</table>
<?php /**PATH D:\program file\Project Kantor\Inventory\resources\views/exports/inventory-report.blade.php ENDPATH**/ ?>