<table>
    <thead>
        <tr>
            <th colspan="9" style="font-weight: bold; text-align: center;">LAPORAN REKAPITULASI INVENTORY</th>
        </tr>
        <tr>
            <th colspan="9" style="text-align: center;">
                Periode:
                @if($startDate && $endDate)
                    {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                @elseif($startDate)
                    Mulai {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
                @elseif($endDate)
                    s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                @else
                    {{ strtoupper($period) }}
                @endif
                (Dicetak pada: {{ now()->format('d/m/Y H:i') }})
            </th>
        </tr>
        <tr></tr>
    </thead>
    <tbody>
        @foreach($grouped as $materialId => $transactions)
            @php
                $material = $transactions->first()->material;

                // Saldo Awal = semua transaksi material ini SEBELUM startDate user.
                // Jika tidak ada startDate, pakai tanggal transaksi pertama yang terfilter.
                if ($startDate) {
                    $cutoffDate = \Carbon\Carbon::parse($startDate)->startOfDay();
                } else {
                    $cutoffDate = \Carbon\Carbon::parse($transactions->min('created_at'));
                }

                $openingResult = \App\Models\InventoryTransaction::where('material_id', $materialId)
                    ->where('created_at', '<', $cutoffDate)
                    ->selectRaw('COALESCE(SUM(volume_masuk), 0) - COALESCE(SUM(volume_keluar), 0) as balance')
                    ->first();
                $openingBalance = (float) ($openingResult->balance ?? 0);

                $totalMasuk  = $transactions->sum('volume_masuk');
                $totalKeluar = $transactions->sum('volume_keluar');

                // Saldo Akhir = Saldo Awal + Net Mutasi Periode (tidak pakai balance_after yang stale)
                $saldoAkhir = $openingBalance + (float) $totalMasuk - (float) $totalKeluar;

                // Running balance dimulai dari saldo awal
                $running = $openingBalance;
            @endphp
            <tr>
                <td colspan="9" style="background-color: #f3f4f6; font-weight: bold;">JENIS MATERIAL: {{ strtoupper($material->name) }}</td>
            </tr>
            <tr style="background-color: #e5e7eb; font-weight: bold;">
                <th style="border: 1px solid #000000;">Tanggal</th>
                <th style="border: 1px solid #000000;">No. Referensi</th>
                <th style="border: 1px solid #000000;">Volume Masuk</th>
                <th style="border: 1px solid #000000;">Volume Keluar</th>
                <th style="border: 1px solid #000000;">Saldo</th>
                <th style="border: 1px solid #000000;">Satuan</th>
                <th style="border: 1px solid #000000;">No. POL</th>
                <th style="border: 1px solid #000000;">Lokasi / Keterangan</th>
                <th style="border: 1px solid #000000;">Keterangan</th>
            </tr>
            {{-- Baris Saldo Awal --}}
            <tr style="font-style: italic; color: #6b7280; background-color: #f8fafc;">
                <td style="border: 1px solid #000000;">-</td>
                <td style="border: 1px solid #000000;">INITIAL</td>
                <td style="border: 1px solid #000000; text-align: center;">-</td>
                <td style="border: 1px solid #000000; text-align: center;">-</td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold;">{{ (int) $openingBalance }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $material->unit }}</td>
                <td style="border: 1px solid #000000;">-</td>
                <td style="border: 1px solid #000000;">-</td>
                <td style="border: 1px solid #000000;">SALDO AWAL PERIODE</td>
            </tr>
            @foreach($transactions as $trx)
                @php
                    // Running balance dihitung ulang per baris — bukan dari balance_after yang stale di DB
                    $running += (float) $trx->volume_masuk - (float) $trx->volume_keluar;
                @endphp
                <tr>
                    <td style="border: 1px solid #000000;">{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                    <td style="border: 1px solid #000000;">{{ $trx->reference_number ?: '-' }}</td>
                    <td style="border: 1px solid #000000; text-align: center; color: #065f46; font-weight: bold;">
                        {{ $trx->volume_masuk > 0 ? (int) $trx->volume_masuk : '-' }}
                    </td>
                    <td style="border: 1px solid #000000; text-align: center; color: #991b1b; font-weight: bold;">
                        {{ $trx->volume_keluar > 0 ? (int) $trx->volume_keluar : '-' }}
                    </td>
                    <td style="border: 1px solid #000000; text-align: center; font-weight: bold;">
                        {{ (int) $running }}
                    </td>
                    <td style="border: 1px solid #000000; text-align: center;">{{ $material->unit }}</td>
                    <td style="border: 1px solid #000000;">{{ $trx->deliveryOrder->no_polisi ?? '-' }}</td>
                    <td style="border: 1px solid #000000;">{{ $trx->deliveryOrder->lokasi ?? ($trx->supplier ?: 'Restock') }}</td>
                    <td style="border: 1px solid #000000;">{{ $trx->note ?: '-' }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #f9fafb;">
                <td colspan="2" style="border: 1px solid #000000; text-align: right;">TOTAL MUTASI:</td>
                <td style="border: 1px solid #000000; text-align: center; background-color: #ecfdf5; color: #065f46;">
                    {{ (int) $totalMasuk }}
                </td>
                <td style="border: 1px solid #000000; text-align: center; background-color: #fef2f2; color: #991b1b;">
                    {{ (int) $totalKeluar }}
                </td>
                <td colspan="3" style="border: 1px solid #000000; text-align: right; background-color: #eff6ff;">SISA (SALDO):</td>
                <td colspan="2" style="border: 1px solid #000000; text-align: center; background-color: #dbeafe; font-weight: bold;">
                    {{ (int) $saldoAkhir }} {{ $material->unit }}
                </td>
            </tr>
            <tr></tr>
            <tr></tr>
        @endforeach
    </tbody>
</table>
