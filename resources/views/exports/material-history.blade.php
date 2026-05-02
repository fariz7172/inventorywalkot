<table>
    <thead>
        <tr>
            <th colspan="6" style="font-weight: bold; font-size: 14px;">RIWAYAT TRANSAKSI: {{ strtoupper($material->name) }}</th>
        </tr>
        <tr>
            <th colspan="6">Periode: {{ date('d/m/Y', strtotime($startDate)) }} - {{ date('d/m/Y', strtotime($endDate)) }}</th>
        </tr>
        <tr>
            <th colspan="6">Saldo Awal: {{ number_format($openingBalance, 0, ',', '.') }} {{ $material->unit }}</th>
        </tr>
        <tr></tr>
        <tr style="background-color: #f3f4f6; font-weight: bold;">
            <th style="border: 1px solid #000;">Tanggal</th>
            <th style="border: 1px solid #000;">No. Referensi / SJ</th>
            <th style="border: 1px solid #000;">Keterangan / Lokasi</th>
            <th style="border: 1px solid #000;">Masuk (+)</th>
            <th style="border: 1px solid #000;">Keluar (-)</th>
            <th style="border: 1px solid #000;">Saldo Sisa</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="border: 1px solid #000; text-align: center;">{{ date('d/m/Y', strtotime($startDate)) }}</td>
            <td style="border: 1px solid #000; font-style: italic;" colspan="2">SALDO AWAL</td>
            <td style="border: 1px solid #000;"></td>
            <td style="border: 1px solid #000;"></td>
            <td style="border: 1px solid #000; font-weight: bold; text-align: right;">{{ $openingBalance }}</td>
        </tr>
        @php $currentBalance = $openingBalance; @endphp
        @foreach($transactions as $trx)
            @php 
                $currentBalance += ($trx->volume_masuk - $trx->volume_keluar);
            @endphp
            <tr>
                <td style="border: 1px solid #000; text-align: center;">{{ $trx->created_at->format('d/m/Y') }}</td>
                <td style="border: 1px solid #000;">{{ $trx->reference_number ?: '-' }}</td>
                <td style="border: 1px solid #000;">{{ $trx->deliveryOrder->lokasi ?? ($trx->note ?: '-') }}</td>
                <td style="border: 1px solid #000; text-align: right;">{{ $trx->volume_masuk > 0 ? $trx->volume_masuk : '' }}</td>
                <td style="border: 1px solid #000; text-align: right;">{{ $trx->volume_keluar > 0 ? $trx->volume_keluar : '' }}</td>
                <td style="border: 1px solid #000; text-align: right; font-weight: bold;">{{ $currentBalance }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
