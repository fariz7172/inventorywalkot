<?php

use App\Models\Rab;
use App\Models\DeliveryOrder;
use function Livewire\Volt\{state, computed, layout, with};

layout('layouts.admin');

state([
    'selectedRabId' => '',
    'selectedMonth' => date('n'),
    'selectedYear' => date('Y'),
]);

$rabs = computed(function() {
    $q = Rab::orderBy('lokasi', 'asc');
    if (auth()->check() && auth()->user()->kecamatan_id) {
        $q->where('kecamatan_id', auth()->user()->kecamatan_id);
    }
    return $q->get();
});

with(fn() => [
    'months' => [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ]
]);

$years = computed(function() {
    $currentYear = date('Y');
    return range($currentYear - 2, $currentYear + 1);
});

$reportData = computed(function() {
    if (!$this->selectedRabId) return null;
    
    $rab = Rab::with('materials')->find($this->selectedRabId);
    if (!$rab) return null;
    
    // Get delivery orders for this lokasi, month, and year. We exclude cancelled if you have such status, usually draft and shipped are what exists.
    $dos = DeliveryOrder::where('lokasi', $rab->lokasi)
        ->whereMonth('tanggal', $this->selectedMonth)
        ->whereYear('tanggal', $this->selectedYear)
        ->with('materials')
        ->get();

    $currentDate = \Carbon\Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1);
    $prevDos = DeliveryOrder::where('lokasi', $rab->lokasi)
        ->whereDate('tanggal', '<', $currentDate->format('Y-m-d'))
        ->with('materials')
        ->get();
        
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $this->selectedMonth, $this->selectedYear);
    
    $data = [];
    foreach ($rab->materials as $material) {
        $data[$material->id] = [
            'material' => $material->name,
            'unit' => $material->unit,
            'target' => (float)$material->pivot->target_volume,
            'lalu' => 0,
            'daily' => array_fill(1, $daysInMonth, 0),
            'total' => 0,
            'sisa' => 0
        ];
    }

    foreach ($prevDos as $do) {
        foreach ($do->materials as $m) {
            if (!isset($data[$m->id])) {
                $data[$m->id] = [
                    'material' => $m->name,
                    'unit' => $m->unit,
                    'target' => 0,
                    'lalu' => 0,
                    'daily' => array_fill(1, $daysInMonth, 0),
                    'total' => 0,
                    'sisa' => 0
                ];
            }
            $data[$m->id]['lalu'] += (float)$m->pivot->requested_volume;
        }
    }
    
    foreach ($dos as $do) {
        $day = (int)date('j', strtotime($do->tanggal));
        foreach ($do->materials as $m) {
            if (!isset($data[$m->id])) {
                $data[$m->id] = [
                    'material' => $m->name,
                    'unit' => $m->unit,
                    'target' => 0,
                    'lalu' => 0,
                    'daily' => array_fill(1, $daysInMonth, 0),
                    'total' => 0,
                    'sisa' => 0
                ];
            }
            $vol = (float)$m->pivot->requested_volume;
            $data[$m->id]['daily'][$day] += $vol;
            $data[$m->id]['total'] += $vol;
        }
    }
    
    foreach ($data as $id => &$row) {
        $row['target'] = $row['target'] - $row['lalu'];
        $row['sisa'] = $row['target'] - $row['total'];
    }
    
    return [
        'rab' => $rab,
        'rows' => $data,
        'daysInMonth' => $daysInMonth
    ];
});

$exportExcel = function() {
    $report = $this->reportData;
    if (!$report) {
        session()->flash('error', 'Tidak ada data untuk diekspor.');
        return;
    }

    $fileName = 'Laporan-RAB-' . str_replace(' ', '-', $report['rab']->lokasi) . '-' . $this->selectedMonth . '-' . $this->selectedYear . '.xlsx';

    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\RabExport($report['rab'], $report['rows'], $this->selectedMonth, $this->selectedYear, $report['daysInMonth']),
        $fileName
    );
};

?>

<div class="max-w-[100vw] overflow-hidden flex flex-col h-full">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan RAB</h1>
            <p class="text-sm text-gray-500">Rekap pengambilan material harian berbanding Stok RAB.</p>
        </div>
        
        <div class="flex items-center gap-3 bg-white p-2 rounded-2xl shadow-sm border border-gray-100">
            <select wire:model.live="selectedRabId" class="bg-gray-50 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 outline-none border-none focus:ring-2 focus:ring-accent/20">
                <option value="">-- Pilih Lokasi RAB --</option>
                @foreach($this->rabs as $rab)
                    <option value="{{ $rab->id }}">{{ $rab->lokasi }}</option>
                @endforeach
            </select>
            
            <select wire:model.live="selectedMonth" class="bg-gray-50 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 outline-none border-none focus:ring-2 focus:ring-accent/20">
                @foreach($months as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>
            
            <select wire:model.live="selectedYear" class="bg-gray-50 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 outline-none border-none focus:ring-2 focus:ring-accent/20">
                @foreach($this->years as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if(!$this->selectedRabId)
    <div class="bg-white rounded-3xl shadow-card ring-1 ring-accent/5 p-12 text-center flex flex-col items-center justify-center min-h-[400px]">
        <div class="w-16 h-16 bg-accent/10 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Pilih Lokasi RAB</h3>
        <p class="text-sm text-gray-500 mt-2 max-w-sm">Silakan pilih lokasi RAB, bulan, dan tahun pada filter di atas untuk melihat laporan matriks pengambilan barang.</p>
    </div>
    @elseif($this->reportData && empty($this->reportData['rows']))
    <div class="bg-white rounded-3xl shadow-card ring-1 ring-accent/5 p-12 text-center flex flex-col items-center justify-center min-h-[400px]">
        <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Material Belum Diatur</h3>
        <p class="text-sm text-gray-500 mt-2 max-w-sm">Anda belum menambahkan pengaturan <strong>Stok Material RAB</strong> untuk lokasi ini. Silakan kelola di menu Data RAB terlebih dahulu.</p>
        <a href="/dashboard/rab" wire:navigate class="mt-4 text-xs font-bold bg-accent text-white px-4 py-2 rounded-xl hover:bg-accent-dark transition-colors">Kelola Stok RAB</a>
    </div>
    @else
    <div class="bg-white rounded-3xl shadow-card ring-1 ring-accent/5 flex-1 overflow-hidden flex flex-col">
        <div class="p-6 border-b border-warm/60 flex items-center justify-between bg-gray-50/50">
            <div>
                <h2 class="font-black text-gray-800 text-lg uppercase tracking-wider">LOKASI: {{ $this->reportData['rab']->lokasi }}</h2>
                <p class="text-xs font-bold text-gray-500 mt-1">PERIODE: {{ strtoupper($months[$selectedMonth]) }} {{ $selectedYear }}</p>
            </div>
            <div class="flex gap-2">
                <button wire:click="exportExcel" class="text-xs font-bold text-white bg-green-600 border border-green-700 px-4 py-2 rounded-xl hover:bg-green-700 transition-colors flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </button>
                <button onclick="window.print()" class="text-xs font-bold text-gray-600 bg-white border border-gray-200 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak Laporan
                </button>
            </div>
        </div>

        <div class="overflow-x-auto w-full">
            <style>
                @media print {
                    body * { visibility: hidden; }
                    .print-area, .print-area * { visibility: visible; }
                    .print-area { position: absolute; left: 0; top: 0; width: 100%; }
                    @page { size: landscape; margin: 1.5cm; }
                    .print-header { display: flex !important; }
                    table th, table td { color: black !important; border-color: #000 !important; }
                }
                .print-header { display: none; }
            </style>
            <div class="print-area inline-block min-w-full align-middle font-serif">
                
                <div class="print-header items-center border-b-[3px] border-black pb-2 mb-6 text-center">
                    <div class="w-[100px] pr-4">
                        <img src="{{ asset('assets/logo.png') }}" onerror="this.style.display='none'" class="w-full" alt="Logo">
                    </div>
                    <div class="flex-1 text-center text-black">
                        <h1 class="text-[11pt] font-bold leading-tight uppercase text-center">PEMERINTAH PROVINSI DAERAH KHUSUS IBUKOTA JAKARTA</h1>
                        <h2 class="text-[13pt] font-bold leading-tight uppercase text-center">DINAS SUMBER DAYA AIR</h2>
                        <h3 class="text-[11pt] font-bold leading-tight uppercase text-center">SUKU DINAS SUMBER DAYA AIR KOTA ADMINISTRASI JAKARTA UTARA</h3>
                    </div>
                </div>

                <div class="text-center mb-6 print-header flex-col">
                    <h1 class="text-[14pt] font-black underline tracking-widest uppercase text-black">LAPORAN MATRIKS RAB MATERIAL</h1>
                    <p class="font-bold text-black mt-1 text-[11pt]">LOKASI: {{ $this->reportData['rab']->lokasi }}</p>
                    <p class="text-black text-[10pt] mt-0.5">PERIODE: {{ strtoupper($months[$selectedMonth]) }} {{ $selectedYear }}</p>
                </div>

                <table class="min-w-full border-collapse border border-gray-300 text-[10px]">
                    <thead>
                        <tr class="bg-gray-100">
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center w-8">NO</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-left min-w-[150px]">MATERIAL</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center">SATUAN</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center">STOCK MATERIAL RAB</th>
                            <th colspan="{{ $this->reportData['daysInMonth'] }}" class="border border-gray-300 px-1 py-1 text-center bg-gray-200">TANGGAL ({{ strtoupper($months[$selectedMonth]) }})</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center min-w-[100px]">TOTAL PENGAMBILAN</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center min-w-[100px]">SISA PENGAMBILAN</th>
                        </tr>
                        <tr class="bg-gray-50">
                            @for($i = 1; $i <= $this->reportData['daysInMonth']; $i++)
                            <th class="border border-gray-300 px-1 py-1 text-center w-6">{{ $i }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @foreach($this->reportData['rows'] as $row)
                        <tr class="hover:bg-warm/30 transition-colors">
                            <td class="border border-gray-300 px-2 py-1 text-center">{{ $no++ }}</td>
                            <td class="border border-gray-300 px-2 py-1 font-bold">{{ $row['material'] }}</td>
                            <td class="border border-gray-300 px-2 py-1 text-center text-gray-500">{{ $row['unit'] }}</td>
                            <td class="border border-gray-300 px-2 py-1 text-right font-bold text-accent bg-accent/5">{{ number_format($row['target'], 2, ',', '.') }}</td>

                            @for($i = 1; $i <= $this->reportData['daysInMonth']; $i++)
                                <td class="border border-gray-300 px-1 py-1 text-center text-gray-600">{{ $row['daily'][$i] > 0 ? number_format($row['daily'][$i], 2, ',', '.') : '' }}</td>
                            @endfor

                            <td class="border border-gray-300 px-2 py-1 text-right font-bold">{{ number_format($row['total'], 2, ',', '.') }}</td>
                            
                            @if($row['sisa'] < 0)
                                <td class="border border-gray-300 px-2 py-1 text-right font-bold text-red-600 bg-red-50/50">
                                    {{ number_format($row['sisa'], 2, ',', '.') }}
                                </td>
                            @else
                                <td class="border border-gray-300 px-2 py-1 text-right font-bold">
                                    {{ number_format($row['sisa'], 2, ',', '.') }}
                                </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
