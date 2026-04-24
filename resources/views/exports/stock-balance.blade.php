<table>
    <thead>
        <tr>
            <th colspan="6" style="font-weight: bold; font-size: 14pt; text-align: center;">LAPORAN SALDO & MUTASI STOK</th>
        </tr>
        <tr>
            <th colspan="6" style="text-align: center;">Periode: {{ $periodLabel }}</th>
        </tr>
        <tr>
            <th colspan="6"></th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #f3f4f6; border: 1px solid #000000;">Material</th>
            <th style="font-weight: bold; background-color: #f3f4f6; border: 1px solid #000000; text-align: center;">Satuan</th>
            <th style="font-weight: bold; background-color: #f3f4f6; border: 1px solid #000000; text-align: right;">Saldo Awal</th>
            <th style="font-weight: bold; background-color: #e8f5e9; border: 1px solid #000000; text-align: right;">Masuk (+)</th>
            <th style="font-weight: bold; background-color: #ffebee; border: 1px solid #000000; text-align: right;">Keluar (-)</th>
            <th style="font-weight: bold; background-color: #e3f2fd; border: 1px solid #000000; text-align: right;">Saldo Akhir</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reportData as $row)
        <tr>
            <td style="border: 1px solid #000000;">{{ $row->name }} ({{ $row->category }})</td>
            <td style="border: 1px solid #000000; text-align: center;">{{ $row->unit }}</td>
            <td style="border: 1px solid #000000; text-align: right;">{{ (float)$row->opening_balance }}</td>
            <td style="border: 1px solid #000000; text-align: right;">{{ (float)$row->total_in }}</td>
            <td style="border: 1px solid #000000; text-align: right;">{{ (float)$row->total_out }}</td>
            <td style="border: 1px solid #000000; text-align: right; font-weight: bold;">{{ (float)$row->final_balance }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
