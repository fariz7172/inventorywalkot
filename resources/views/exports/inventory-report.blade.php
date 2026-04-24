<table>
    <thead>
        <tr>
            <th colspan="7" style="font-weight: bold; text-align: center;">LAPORAN REKAPITULASI INVENTORY</th>
        </tr>
        <tr>
            <th colspan="7" style="text-align: center;">Periode: {{ strtoupper($period) }} (Dicetak pada: {{ now()->format('d/m/Y H:i') }})</th>
        </tr>
        <tr></tr>
    </thead>
    <tbody>
        @foreach($grouped as $materialId => $transactions)
            @php 
                $material = $transactions->first()->material;
                $totalMasuk = $transactions->sum('volume_masuk');
                $totalKeluar = $transactions->sum('volume_keluar');
                $saldoAkhir = $transactions->last()->balance_after;
            @endphp
            <tr>
                <td colspan="7" style="background-color: #f3f4f6; font-weight: bold;">JENIS MATERIAL: {{ strtoupper($material->name) }}</td>
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
            @foreach($transactions as $trx)
            <tr>
                <td style="border: 1px solid #000000;">{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                <td style="border: 1px solid #000000;">{{ $trx->reference_number ?: '-' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $trx->volume_masuk > 0 ? (float)$trx->volume_masuk : '0' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $trx->volume_keluar > 0 ? (float)$trx->volume_keluar : '0' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $material->unit }}</td>
                <td style="border: 1px solid #000000;">{{ $trx->deliveryOrder->no_polisi ?? '-' }}</td>
                <td style="border: 1px solid #000000;">{{ $trx->deliveryOrder->lokasi ?? ($trx->description ?: 'Restock') }}</td>
            </tr>
            @endforeach
            <tr style="font-weight: bold;">
                <td colspan="2" style="border: 1px solid #000000; text-align: right; background-color: #f9fafb;">TOTAL MUTASI:</td>
                <td style="border: 1px solid #000000; text-align: center; background-color: #ecfdf5;">{{ (float)$totalMasuk }}</td>
                <td style="border: 1px solid #000000; text-align: center; background-color: #fef2f2;">{{ (float)$totalKeluar }}</td>
                <td colspan="2" style="border: 1px solid #000000; text-align: right; background-color: #eff6ff;">SISA (SALDO):</td>
                <td style="border: 1px solid #000000; text-align: center; background-color: #dbeafe; font-weight: black;">{{ (float)$saldoAkhir }} {{ $material->unit }}</td>
            </tr>
            <tr></tr>
            <tr></tr>
        @endforeach
    </tbody>
</table>
