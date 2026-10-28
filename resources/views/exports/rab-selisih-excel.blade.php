<table>
    <thead>
        <tr>
            <th colspan="11" style="font-size: 14pt; font-weight: bold; text-align: center;">LAPORAN REKAPITULASI SELISIH MATERIAL RAB (NILAI MINUS / OVER-BUDGET)</th>
        </tr>
        <tr>
            <th colspan="11" style="font-size: 11pt; font-weight: bold; text-align: center;">PERIODE: {{ strtoupper($monthName) }} {{ $year }}</th>
        </tr>
        <tr>
            <th colspan="11" style="font-size: 9pt; font-style: italic; text-align: center;">Diekspor pada: {{ date('d-m-Y H:i:s') }} | Akun: {{ auth()->user()->name ?? 'Admin' }}</th>
        </tr>
        <tr>
            <th colspan="11"></th>
        </tr>
        <tr style="background-color: #f3f4f6; font-weight: bold; text-align: center;">
            <th style="border: 1px solid #000000; text-align: center; width: 50px;">NO</th>
            <th style="border: 1px solid #000000; text-align: left; width: 350px;">LOKASI PEKERJAAN / RAB</th>
            <th style="border: 1px solid #000000; text-align: center; width: 140px;">KECAMATAN</th>
            <th style="border: 1px solid #000000; text-align: left; width: 220px;">NAMA MATERIAL</th>
            <th style="border: 1px solid #000000; text-align: center; width: 80px;">SATUAN</th>
            <th style="border: 1px solid #000000; text-align: right; width: 130px;">TARGET RAB AWAL</th>
            <th style="border: 1px solid #000000; text-align: right; width: 140px;">PENGELUARAN LALU</th>
            <th style="border: 1px solid #000000; text-align: right; width: 150px; background-color: #fee2e2;">STOCK MATERIAL RAB</th>
            <th style="border: 1px solid #000000; text-align: right; width: 140px;">PENGELUARAN BLN INI</th>
            <th style="border: 1px solid #000000; text-align: right; width: 140px; background-color: #fee2e2;">SISA PENGAMBILAN</th>
            <th style="border: 1px solid #000000; text-align: center; width: 180px;">KETERANGAN</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @forelse($data as $row)
        <tr>
            <td style="border: 1px solid #d1d5db; text-align: center;">{{ $no++ }}</td>
            <td style="border: 1px solid #d1d5db; text-align: left;">{{ $row['lokasi'] }}</td>
            <td style="border: 1px solid #d1d5db; text-align: center;">{{ $row['kecamatan'] }}</td>
            <td style="border: 1px solid #d1d5db; text-align: left; font-weight: bold;">{{ $row['material_name'] }}</td>
            <td style="border: 1px solid #d1d5db; text-align: center;">{{ $row['unit'] }}</td>
            <td style="border: 1px solid #d1d5db; text-align: right;">{{ number_format($row['target_awal'], 2, ',', '.') }}</td>
            <td style="border: 1px solid #d1d5db; text-align: right;">{{ number_format($row['lalu'], 2, ',', '.') }}</td>
            <td style="border: 1px solid #d1d5db; text-align: right; font-weight: bold; color: {{ $row['stock_rab'] < 0 ? '#dc2626' : '#000000' }}; background-color: {{ $row['stock_rab'] < 0 ? '#fef2f2' : '#ffffff' }};">
                {{ number_format($row['stock_rab'], 2, ',', '.') }}
            </td>
            <td style="border: 1px solid #d1d5db; text-align: right;">{{ number_format($row['bulan_ini'], 2, ',', '.') }}</td>
            <td style="border: 1px solid #d1d5db; text-align: right; font-weight: bold; color: {{ $row['sisa'] < 0 ? '#dc2626' : '#000000' }}; background-color: {{ $row['sisa'] < 0 ? '#fef2f2' : '#ffffff' }};">
                {{ number_format($row['sisa'], 2, ',', '.') }}
            </td>
            <td style="border: 1px solid #d1d5db; text-align: center;">
                @if($row['target_awal'] == 0)
                    Material Non-RAB
                @else
                    Melebihi Kuota RAB
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="11" style="text-align: center; padding: 20px;">Tidak ada data selisih material pada periode ini.</td>
        </tr>
        @endforelse
    </tbody>
</table>
