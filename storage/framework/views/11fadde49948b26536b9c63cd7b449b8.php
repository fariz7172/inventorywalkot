<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Riwayat - <?php echo e($material->name); ?></title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 19cm;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
            position: relative;
        }
        .header img {
            max-width: 100%;
            height: auto;
        }
        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        .subtitle {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 2px 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 10pt;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .footer-sign {
            width: 100%;
            margin-top: 40px;
        }
        .footer-sign td {
            width: 33%;
            text-align: center;
            vertical-align: top;
        }
        .sign-space {
            height: 80px;
        }
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #2F2FE4;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            font-family: sans-serif;
            font-size: 14px;
        }
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <a href="javascript:window.print()" class="no-print">CETAK SEKARANG</a>

    <div class="container">
        <div class="header">
            <img src="<?php echo e(asset('assets/kop.png')); ?>" alt="KOP">
        </div>

        <div class="title">KARTU PERSEDIAAN</div>
        <?php
            $startDisplay = $startDate ? date('d F Y', strtotime($startDate)) : 'Awal';
            $endDisplay = $endDate ? date('d F Y', strtotime($endDate)) : 'Saat Ini';
        ?>
        <div class="subtitle">Periode: <?php echo e($startDisplay); ?> s/d <?php echo e($endDisplay); ?></div>

        <table class="info-table">
            <tr>
                <td width="120">Nama Barang</td>
                <td width="10">:</td>
                <td class="font-bold"><?php echo e($material->name); ?></td>
            </tr>
            <tr>
                <td>Satuan</td>
                <td>:</td>
                <td><?php echo e($material->unit); ?></td>
            </tr>
            <tr>
                <td>Kategori</td>
                <td>:</td>
                <td><?php echo e($material->category->name ?? '-'); ?></td>
            </tr>
            <tr>
                <td>Saldo Awal Bulan</td>
                <td>:</td>
                <td class="font-bold"><?php echo e(number_format($openingBalance, 0, ',', '.')); ?> <?php echo e($material->unit); ?></td>
            </tr>
        </table>

        <table class="data-table">
            <thead>
                <tr>
                    <th width="30">No</th>
                    <th width="90">Tanggal</th>
                    <th width="120">No. Referensi</th>
                    <th>Keterangan / Lokasi</th>
                    <th width="80">Masuk (+)</th>
                    <th width="80">Keluar (-)</th>
                    <th width="90">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td class="text-center"><?php echo e($startDate ? date('d/m/Y', strtotime($startDate)) : '-'); ?></td>
                    <td class="text-center" colspan="2"><i>Saldo terakhir akhir bulan lalu</i></td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right font-bold"><?php echo e(number_format($openingBalance, 0, ',', '.')); ?></td>
                </tr>
                <?php $currentBalance = $openingBalance; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $trx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php 
                        $currentBalance += ($trx->volume_masuk - $trx->volume_keluar);
                    ?>
                    <tr>
                        <td class="text-center"><?php echo e($index + 2); ?></td>
                        <td class="text-center"><?php echo e($trx->created_at->format('d/m/Y')); ?></td>
                        <td class="text-center"><?php echo e($trx->reference_number ?: '-'); ?></td>
                        <td><?php echo e($trx->deliveryOrder->lokasi ?? ($trx->note ?: '-')); ?></td>
                        <td class="text-right"><?php echo e($trx->volume_masuk > 0 ? number_format($trx->volume_masuk, 0, ',', '.') : '-'); ?></td>
                        <td class="text-right"><?php echo e($trx->volume_keluar > 0 ? number_format($trx->volume_keluar, 0, ',', '.') : '-'); ?></td>
                        <td class="text-right font-bold"><?php echo e(number_format($currentBalance, 0, ',', '.')); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>

        <div style="margin-top: 20px; font-size: 10pt;">
            Demikian Kartu Persediaan ini dibuat untuk dipergunakan sebagaimana mestinya.
        </div>

        <table class="footer-sign">
            <tr>
                <td>
                    Dibuat Oleh,<br>
                    <strong>Admin Gudang</strong>
                    <div class="sign-space"></div>
                    ( ................................ )
                </td>
                <td>
                    Diperiksa Oleh,<br>
                    <strong>Staff Kantor</strong>
                    <div class="sign-space"></div>
                    ( ................................ )
                </td>
                <td>
                    Mengetahui,<br>
                    <strong>Pimpinan</strong>
                    <div class="sign-space"></div>
                    ( ................................ )
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
<?php /**PATH D:\program file\Project Kantor\Inventory\resources\views/laporan/print-riwayat.blade.php ENDPATH**/ ?>