<table>
    @foreach($groups as $key => $items)
        @php
            $first = $items->first();
        @endphp
        {{-- Group Header --}}
        <thead>
            <tr>
                <th style="background-color: #4F46E5; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">Tanggal</th>
                <th style="background-color: #4F46E5; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">Jam</th>
                <th style="background-color: #4F46E5; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">Jenis</th>
                <th style="background-color: #4F46E5; color: #ffffff; font-weight: bold; border: 1px solid #000000; text-align: center;">No. Referensi</th>
            </tr>
            <tr>
                <td style="border: 1px solid #000000; text-align: center;">{{ $first->created_at->format('d/m/Y') }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $first->created_at->format('H:i') }}</td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold; color: {{ $first->type === 'in' ? '#059669' : '#DC2626' }};">
                    {{ $first->type === 'in' ? 'MASUK' : 'KELUAR' }}
                </td>
                <td style="border: 1px solid #000000; font-weight: bold;">{{ $first->reference_number ?: '-' }}</td>
            </tr>
            {{-- Item Header --}}
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
            @foreach($items as $item)
                <tr>
                    <td style="border: 1px solid #000000;">{{ $item->material->name }}</td>
                    <td style="border: 1px solid #000000; text-align: right; font-weight: bold;">{{ (float)($item->type === 'in' ? $item->volume_masuk : $item->volume_keluar) }}</td>
                    <td style="border: 1px solid #000000; text-align: center;">{{ $item->material->unit }}</td>
                    <td style="border: 1px solid #000000;">{{ $item->type === 'in' ? ($item->supplier ?: 'Restock Internal') : ($item->deliveryOrder->lokasi ?? 'Pengeluaran') }}</td>
                    <td style="border: 1px solid #000000;">{{ $item->user->name ?? 'System' }}</td>
                    <td style="border: 1px solid #000000;">{{ $item->note ?: '-' }}</td>
                </tr>
            @endforeach
            {{-- Spacer --}}
            <tr><td colspan="6"></td></tr>
        </tbody>
    @endforeach
</table>
