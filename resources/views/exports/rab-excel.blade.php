<table>
    <thead>
        <tr>
            <th colspan="{{ 5 + $daysInMonth }}" style="font-weight: bold; font-size: 14px;">LAPORAN MATRIKS RAB MATERIAL</th>
        </tr>
        <tr>
            <th colspan="{{ 5 + $daysInMonth }}" style="font-weight: bold;">LOKASI: {{ strtoupper($rab->lokasi) }}</th>
        </tr>
        <tr>
            <th colspan="{{ 5 + $daysInMonth }}" style="font-weight: bold;">PERIODE: {{ strtoupper(date('F', mktime(0, 0, 0, $month, 10))) }} {{ $year }}</th>
        </tr>
        <tr style="background-color: #f3f4f6; font-weight: bold;">
            <th style="border: 1px solid #000; vertical-align: middle; text-align: center;">NO</th>
            <th style="border: 1px solid #000; vertical-align: middle;">MATERIAL</th>
            <th style="border: 1px solid #000; vertical-align: middle; text-align: center;">SATUAN</th>
            <th style="border: 1px solid #000; vertical-align: middle; text-align: center;">STOCK MATERIAL RAB</th>
            @for($i = 1; $i <= $daysInMonth; $i++)
                <th style="border: 1px solid #000; text-align: center;">{{ $i }}</th>
            @endfor
            <th style="border: 1px solid #000; vertical-align: middle; text-align: center;">TOTAL PENGAMBILAN</th>
            <th style="border: 1px solid #000; vertical-align: middle; text-align: center;">SISA PENGAMBILAN</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach($rows as $row)
        <tr>
            <td style="border: 1px solid #000; text-align: center;">{{ $no++ }}</td>
            <td style="border: 1px solid #000; font-weight: bold;">{{ $row['material'] }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $row['unit'] }}</td>
            <td style="border: 1px solid #000; text-align: right; background-color: #f8fafc;">{{ number_format($row['target'], 2, ',', '.') }}</td>
            
            @for($i = 1; $i <= $daysInMonth; $i++)
                <td style="border: 1px solid #000; text-align: right;">{{ $row['daily'][$i] > 0 ? number_format($row['daily'][$i], 2, ',', '.') : '' }}</td>
            @endfor

            <td style="border: 1px solid #000; text-align: right; font-weight: bold;">{{ number_format($row['total'], 2, ',', '.') }}</td>
            
            @if($row['sisa'] < 0)
                <td style="border: 1px solid #000; text-align: right; font-weight: bold; background-color: #fee2e2; color: #991b1b;">
                    {{ number_format($row['sisa'], 2, ',', '.') }}
                </td>
            @else
                <td style="border: 1px solid #000; text-align: right; font-weight: bold;">
                    {{ number_format($row['sisa'], 2, ',', '.') }}
                </td>
            @endif
        </tr>
        @endforeach
    </tbody>
</table>
