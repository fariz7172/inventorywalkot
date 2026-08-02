<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Exports\StockOpnameExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\StockOpnameItem;
use App\Models\StockOpname;
use Carbon\Carbon;

new #[Layout('layouts.admin')] class extends Component {
    public $filterPeriod = 'this_month';
    public $filterDifference = 'all';
    public $startDate = '';
    public $endDate = '';
    public $items = [];

    // Stats variables
    public $statTotalOpname = 0;
    public $statMinus = 0;
    public $statPlus = 0;
    public $statBalance = 0;

    public function mount() {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->loadData();
    }

    public function updatedFilterPeriod() {
        if ($this->filterPeriod !== 'custom') {
            $this->loadData();
        } elseif ($this->startDate && $this->endDate) {
            $this->loadData();
        }
    }

    public function updatedFilterDifference() {
        $this->loadData();
    }

    public function applyCustomDate() {
        if ($this->startDate && $this->endDate) {
            $this->loadData();
        }
    }

    public function loadData() {
        // Query for Table
        $query = StockOpnameItem::with(['opname.user', 'opname.approver', 'material'])
            ->whereHas('opname', function($q) {
                if ($this->filterPeriod === 'this_week') {
                    $q->whereBetween('opname_date', [now()->startOfWeek()->format('Y-m-d'), now()->endOfWeek()->format('Y-m-d')]);
                } elseif ($this->filterPeriod === 'this_month') {
                    $q->whereMonth('opname_date', now()->month)
                      ->whereYear('opname_date', now()->year);
                } elseif ($this->filterPeriod === 'this_year') {
                    $q->whereYear('opname_date', now()->year);
                } elseif ($this->filterPeriod === 'custom' && $this->startDate && $this->endDate) {
                    $q->whereBetween('opname_date', [$this->startDate, $this->endDate]);
                }
            });

        if ($this->filterDifference === 'has_diff') {
            $query->where('stock_opname_items.difference', '!=', 0);
        } elseif ($this->filterDifference === 'plus') {
            $query->where('stock_opname_items.difference', '>', 0);
        } elseif ($this->filterDifference === 'minus') {
            $query->where('stock_opname_items.difference', '<', 0);
        }

        $query->join('stock_opnames', 'stock_opname_items.stock_opname_id', '=', 'stock_opnames.id')
            ->join('materials', 'stock_opname_items.material_id', '=', 'materials.id')
            ->select('stock_opname_items.*')
            ->orderBy('materials.name', 'asc')
            ->orderBy('stock_opnames.opname_date', 'desc');

        $this->items = $query->get();

        // Query for Stats (Unfiltered by difference, only by period)
        $statsQuery = clone $query;
        $statsQuery = StockOpnameItem::whereHas('opname', function($q) {
            if ($this->filterPeriod === 'this_week') {
                $q->whereBetween('opname_date', [now()->startOfWeek()->format('Y-m-d'), now()->endOfWeek()->format('Y-m-d')]);
            } elseif ($this->filterPeriod === 'this_month') {
                $q->whereMonth('opname_date', now()->month)
                  ->whereYear('opname_date', now()->year);
            } elseif ($this->filterPeriod === 'this_year') {
                $q->whereYear('opname_date', now()->year);
            } elseif ($this->filterPeriod === 'custom' && $this->startDate && $this->endDate) {
                $q->whereBetween('opname_date', [$this->startDate, $this->endDate]);
            }
        });

        $allStatsItems = $statsQuery->get();
        $this->statMinus = $allStatsItems->where('difference', '<', 0)->count();
        $this->statPlus = $allStatsItems->where('difference', '>', 0)->count();
        $this->statBalance = $allStatsItems->where('difference', '==', 0)->count();

        $this->statTotalOpname = StockOpname::when($this->filterPeriod !== 'all', function($q) {
            if ($this->filterPeriod === 'this_week') {
                $q->whereBetween('opname_date', [now()->startOfWeek()->format('Y-m-d'), now()->endOfWeek()->format('Y-m-d')]);
            } elseif ($this->filterPeriod === 'this_month') {
                $q->whereMonth('opname_date', now()->month)->whereYear('opname_date', now()->year);
            } elseif ($this->filterPeriod === 'this_year') {
                $q->whereYear('opname_date', now()->year);
            } elseif ($this->filterPeriod === 'custom' && $this->startDate && $this->endDate) {
                $q->whereBetween('opname_date', [$this->startDate, $this->endDate]);
            }
        })->count();
    }

    public function exportExcel() {
        $export = new StockOpnameExport($this->filterPeriod, $this->filterDifference);
        return Excel::download($export, 'analisa-selisih-stok-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function getPeriodLabelProperty()
    {
        if ($this->filterPeriod === 'this_week') return 'Minggu Ini (' . now()->startOfWeek()->format('d M') . ' - ' . now()->endOfWeek()->format('d M Y') . ')';
        if ($this->filterPeriod === 'this_month') return now()->translatedFormat('F Y');
        if ($this->filterPeriod === 'this_year') return 'Tahun ' . now()->year;
        if ($this->filterPeriod === 'custom' && $this->startDate && $this->endDate) {
            return Carbon::parse($this->startDate)->format('d M Y') . ' - ' . Carbon::parse($this->endDate)->format('d M Y');
        }
        return 'Semua Waktu';
    }
};

?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Analisa Selisih Stok</h1>
            <p class="text-sm text-gray-500 mt-1">Laporan analitik performa akurasi stok gudang (Fisik vs Sistem).</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex gap-1 no-print">
                <button onclick="printReport()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2.5 rounded-xl font-bold transition-all shadow-sm hover:bg-gray-50 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak PDF
                </button>
                <button wire:click="exportExcel" class="bg-white border border-gray-300 text-gray-700 px-4 py-2.5 rounded-xl font-bold transition-all shadow-sm hover:bg-gray-50 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download Excel
                </button>
            </div>
            <div class="flex flex-col gap-3">
                <div class="flex gap-2 justify-end">
                    <div class="w-48">
                        <div class="relative no-print">
                            <select wire:model.live="filterDifference" class="w-full appearance-none bg-white border border-gray-300 text-gray-700 py-2.5 px-4 pr-8 rounded-xl focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent shadow-sm transition-all cursor-pointer font-medium text-sm">
                                <option value="all">Semua Data</option>
                                <option value="has_diff">Hanya Ada Selisih</option>
                                <option value="plus">Hanya Selisih Lebih (+)</option>
                                <option value="minus">Hanya Selisih Kurang (-)</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>
                    <div class="w-48">
                        <div class="relative no-print">
                            <select wire:model.live="filterPeriod" class="w-full appearance-none bg-white border border-gray-300 text-gray-700 py-2.5 px-4 pr-8 rounded-xl focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent shadow-sm transition-all cursor-pointer font-medium text-sm">
                                <option value="all">Semua Waktu</option>
                                <option value="this_week">Minggu Ini</option>
                                <option value="this_month">Bulan Ini</option>
                                <option value="this_year">Tahun Ini</option>
                                <option value="custom">Kustom Tanggal</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                @if($filterPeriod === 'custom')
                <div class="flex items-center gap-2 justify-end no-print bg-white p-2 border border-gray-200 rounded-xl shadow-sm">
                    <input type="date" wire:model="startDate" class="bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-accent focus:border-accent block px-3 py-1.5">
                    <span class="text-gray-400 font-bold text-xs uppercase">s/d</span>
                    <input type="date" wire:model="endDate" class="bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-accent focus:border-accent block px-3 py-1.5">
                    <button wire:click="applyCustomDate" class="bg-accent hover:bg-accent-light text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">Terapkan</button>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 no-print">
        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100 flex flex-col justify-center">
            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Gelar Opname</p>
            <div class="flex items-end gap-2">
                <span class="text-3xl font-black text-gray-800">{{ $statTotalOpname }}</span>
                <span class="text-xs font-bold text-gray-400 mb-1">Kali</span>
            </div>
        </div>
        <div class="bg-red-50/50 rounded-2xl p-5 shadow-card border border-red-100 flex flex-col justify-center relative overflow-hidden group hover:bg-red-50 transition-colors">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-red-100/50 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
            <p class="text-[11px] font-black text-red-500 uppercase tracking-widest mb-1 relative z-10">Barang Hilang / Minus</p>
            <div class="flex items-end gap-2 relative z-10">
                <span class="text-3xl font-black text-red-600">{{ $statMinus }}</span>
                <span class="text-xs font-bold text-red-400 mb-1">Item</span>
            </div>
        </div>
        <div class="bg-emerald-50/50 rounded-2xl p-5 shadow-card border border-emerald-100 flex flex-col justify-center relative overflow-hidden group hover:bg-emerald-50 transition-colors">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-emerald-100/50 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
            <p class="text-[11px] font-black text-emerald-600 uppercase tracking-widest mb-1 relative z-10">Kelebihan / Plus</p>
            <div class="flex items-end gap-2 relative z-10">
                <span class="text-3xl font-black text-emerald-700">{{ $statPlus }}</span>
                <span class="text-xs font-bold text-emerald-500 mb-1">Item</span>
            </div>
        </div>
        <div class="bg-blue-50/50 rounded-2xl p-5 shadow-card border border-blue-100 flex flex-col justify-center relative overflow-hidden group hover:bg-blue-50 transition-colors">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-100/50 rounded-full group-hover:scale-150 transition-transform duration-500"></div>
            <p class="text-[11px] font-black text-blue-600 uppercase tracking-widest mb-1 relative z-10">Stok Akurat (Balance)</p>
            <div class="flex items-end gap-2 relative z-10">
                <span class="text-3xl font-black text-blue-700">{{ $statBalance }}</span>
                <span class="text-xs font-bold text-blue-500 mb-1">Item</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-card overflow-hidden ring-1 ring-accent/5 print-container">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-warm/30 text-gray-600 font-semibold border-b border-warm/60">
                    <tr>
                        <th class="px-6 py-4">Tanggal</th>
                        <th class="px-6 py-4">Material / Barang</th>
                        <th class="px-6 py-4 text-center">Stok Sistem</th>
                        <th class="px-6 py-4 text-center">Stok Fisik</th>
                        <th class="px-6 py-4 text-center">Selisih</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4">Status & Pelaksana</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm/30">
                    @forelse($items as $item)
                    <tr class="hover:bg-base/50 transition-colors {{ $item->difference != 0 ? 'bg-orange-50/20' : '' }}">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ \Carbon\Carbon::parse($item->opname->opname_date)->format('d M Y') }}</td>
                        <td class="px-6 py-4 text-gray-800 font-bold">{{ $item->material->name }} <br><span class="text-xs font-normal text-gray-500">Satuan: {{ $item->material->unit }}</span></td>
                        <td class="px-6 py-4 text-center text-gray-600">{{ (float)$item->system_volume }}</td>
                        <td class="px-6 py-4 text-center text-gray-900 font-bold">{{ (float)$item->physical_volume }}</td>
                        <td class="px-6 py-4 text-center font-bold {{ $item->difference > 0 ? 'text-emerald-600' : ($item->difference < 0 ? 'text-red-600' : 'text-gray-400') }}">
                            {{ $item->difference > 0 ? '+'.(float)$item->difference : ($item->difference == 0 ? '-' : (float)$item->difference) }}
                        </td>
                        <td class="px-6 py-4 text-gray-600 italic">
                            {{ $item->notes ?? '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                @if($item->opname->status === 'approved')
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded w-max">
                                        Disetujui
                                    </span>
                                @elseif($item->opname->status === 'rejected')
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-red-700 bg-red-100 px-2 py-0.5 rounded w-max">
                                        Ditolak
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded w-max">
                                        Pending
                                    </span>
                                @endif
                                <span class="text-[10px] text-gray-500">Oleh: {{ $item->opname->user->name }}</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p>Belum ada rekapan opname di periode ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Print Area --}}
    <div id="print-area" class="hidden print-target bg-white text-black text-xs" style="font-family: 'Times New Roman', serif;">
        <img src="{{ asset('assets/kop.png') }}" class="w-full h-auto mb-6">
        
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold uppercase leading-tight">(LAMPIRAN BERITA ACARA STOCK OPNAME)</h1>
            <p class="text-md font-bold mt-1">Periode: {{ $this->periodLabel }}</p>
            <p class="text-md font-bold mt-1">Nomer : ......................</p>
        </div>

        <table class="w-full border-collapse border border-black text-[9px]">
            <thead>
                <tr class="bg-gray-100 font-bold text-center">
                    <th class="border border-black px-2 py-2">TANGGAL</th>
                    <th class="border border-black px-2 py-2 text-left">MATERIAL / BARANG</th>
                    <th class="border border-black px-2 py-2">STOK SISTEM</th>
                    <th class="border border-black px-2 py-2">STOK FISIK</th>
                    <th class="border border-black px-2 py-2">SELISIH</th>
                    <th class="border border-black px-2 py-2 text-left">KETERANGAN</th>
                    <th class="border border-black px-2 py-2 text-left">PELAKSANA</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td class="border border-black px-2 py-1 text-center">{{ \Carbon\Carbon::parse($item->opname->opname_date)->format('d/m/Y') }}</td>
                    <td class="border border-black px-2 py-1 font-bold uppercase">{{ $item->material->name }} ({{ $item->material->unit }})</td>
                    <td class="border border-black px-2 py-1 text-center">{{ (float)$item->system_volume }}</td>
                    <td class="border border-black px-2 py-1 text-center font-bold">{{ (float)$item->physical_volume }}</td>
                    <td class="border border-black px-2 py-1 text-center font-bold">
                        {{ $item->difference > 0 ? '+'.(float)$item->difference : ($item->difference == 0 ? '-' : (float)$item->difference) }}
                    </td>
                    <td class="border border-black px-2 py-1 text-[8px]">{{ $item->notes ?? '-' }}</td>
                    <td class="border border-black px-2 py-1 text-[8px] uppercase">{{ $item->opname->user->name }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-8">
            <table class="signature-table" style="width: 100%; border: none; text-align: center;">
                <tr>
                    <td style="border: none; width: 50%; vertical-align: top;">
                        <p style="margin: 0; visibility: hidden;">Jakarta, ...</p>
                        <p style="margin: 4px 0; font-weight: bold;">Pengurus Gudang,</p>
                        <div style="height: 60px;"></div>
                        <p style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">M. Suherman Eka Putra</p>
                    </td>
                    <td style="border: none; width: 50%; vertical-align: top;">
                        <p style="margin: 0;">Jakarta, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                        <p style="margin: 4px 0; font-weight: bold;">Kepala Gudang,</p>
                        <div style="height: 60px;"></div>
                        <p style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">Sanjaya</p>
                    </td>
                </tr>
            </table>

            <div style="text-align: center; margin-top: 15px;">
                <p style="margin: 0; font-weight: bold;">Mengetahui</p>
                <p style="margin: 4px 0; font-weight: bold;">Kasubag TU</p>
                <div style="height: 60px;"></div>
                <p style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;">Deny Tri Hendrato</p>
            </div>
        </div>
    </div>

    <script>
        function printReport() {
            const el = document.getElementById('print-area');
            if (!el) return;

            const originalTitle = document.title;
            document.title = "LAPORAN_STOCK_OPNAME_{{ now()->format('d_m_Y') }}";

            const printClone = el.cloneNode(true);
            printClone.id = 'temp-print-area';
            printClone.classList.remove('hidden');
            printClone.classList.add('print-active');
            document.body.appendChild(printClone);

            window.print();
            
            document.body.removeChild(printClone);
            document.title = originalTitle;
        }
    </script>

    <style>
        .print-target { display: none; }
        
        @media print {
            @page {
                size: landscape;
                margin: 1cm;
            }
            
            body > *:not(.print-active) {
                display: none !important;
            }

            .print-active {
                display: block !important;
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                padding: 0 !important;
                background: white !important;
            }
            
            table { border-collapse: collapse !important; }
            th, td { border: 1px solid black !important; }
            .signature-table, .signature-table th, .signature-table td { border: none !important; }
        }
    </style>
</div>
