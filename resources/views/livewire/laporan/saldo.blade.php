<?php

use App\Models\Material;
use App\Models\InventoryTransaction;
use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $year;
    public $month;
    public $week;
    public $category_id = '';
    public $search = '';

    public function mount()
    {
        $this->year = date('Y');
        $this->month = date('m');
    }

    public function rendering($view)
    {
        $view->layout('layouts.admin');
    }

    #[\Livewire\Attributes\On('global-search')]
    public function handleGlobalSearch($search)
    {
        $this->search = $search;
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function exportExcel()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StockBalanceExport($this->year, $this->month, $this->category_id, $this->search),
            'laporan-saldo-bulanan-' . $this->year . '-' . $this->month . '.xlsx'
        );
    }

    public function exportWeekly()
    {
        if (!$this->week)
            return;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\WeeklyStockBalanceExport($this->year, $this->month, $this->week, $this->category_id, $this->search),
            'laporan-saldo-mingguan-ke' . $this->week . '-' . $this->year . '-' . $this->month . '.xlsx'
        );
    }

    public function resetFilters()
    {
        $this->year = date('Y');
        $this->month = date('m');
        $this->week = '';
        $this->category_id = '';
        $this->search = '';
        $this->resetPage();
    }

    public function with()
    {
        // Define Start and End Date based on filters
        $date = Carbon::create((int) $this->year, (int) $this->month, 1);
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        if ($this->week) {
            $startDate = $date->copy()->startOfMonth()->addWeeks((int) $this->week - 1)->startOfWeek();
            $endDate = $startDate->copy()->endOfWeek();
            if ($startDate->month != (int) $this->month)
                $startDate = $date->copy()->startOfMonth();
            if ($endDate->month != (int) $this->month)
                $endDate = $date->copy()->endOfMonth();
        }

        $materialsQuery = Material::with('category')
            ->when($this->category_id, fn($q) => $q->where('category_id', $this->category_id))
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name', 'asc');

        $paginatedMaterials = $materialsQuery->paginate(100); // Increased to 100
        $now = Carbon::now();

        $days = [];
        if ($this->week) {
            for ($i = 0; $i < 7; $i++) {
                $days[] = $startDate->copy()->addDays($i);
            }
        }

        $items = collect($paginatedMaterials->items())->map(function ($material) use ($startDate, $endDate, $now, $days) {
            // Mutation after the end of period until NOW
            $mutationAfter = InventoryTransaction::where('material_id', $material->id)
                ->where('created_at', '>', $endDate)
                ->selectRaw('SUM(volume_masuk) as in_sum, SUM(volume_keluar) as out_sum')
                ->first();

            $netAfter = (float) ($mutationAfter->in_sum ?? 0) - (float) ($mutationAfter->out_sum ?? 0);
            $finalBalance = (float) $material->current_volume - $netAfter;

            // Mutations DURING period
            $periodTransactions = InventoryTransaction::where('material_id', $material->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('SUM(volume_masuk) as total_in, SUM(volume_keluar) as total_out')
                ->first();

            $totalIn = (float) ($periodTransactions->total_in ?? 0);
            $totalOut = (float) ($periodTransactions->total_out ?? 0);
            $openingBalance = $finalBalance - ($totalIn - $totalOut);

            // Daily Incoming if Week is filtered
            $dailyIn = [];
            $dailyOut = [];
            if ($this->week) {
                // Incoming
                $dailyInTrx = InventoryTransaction::where('material_id', $material->id)
                    ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                    ->where('type', 'in')
                    ->selectRaw('DATE(created_at) as date, SUM(volume_masuk) as daily_total')
                    ->groupBy('date')
                    ->get()
                    ->pluck('daily_total', 'date');

                // Outgoing
                $dailyOutTrx = InventoryTransaction::where('material_id', $material->id)
                    ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                    ->where('type', 'out')
                    ->selectRaw('DATE(created_at) as date, SUM(volume_keluar) as daily_total')
                    ->groupBy('date')
                    ->get()
                    ->pluck('daily_total', 'date');

                foreach ($days as $day) {
                    $dateStr = $day->format('Y-m-d');
                    $dailyIn[] = round((float) ($dailyInTrx[$dateStr] ?? 0), 2);
                    $dailyOut[] = round((float) ($dailyOutTrx[$dateStr] ?? 0), 2);
                }
            }

            return (object) [
                'id' => $material->id,
                'name' => $material->name,
                'category' => $material->category->name ?? '-',
                'unit' => $material->unit,
                'opening_balance' => round($openingBalance, 2),
                'total_in' => round($totalIn, 2),
                'total_out' => round($totalOut, 2),
                'final_balance' => round($finalBalance, 2),
                'daily_in' => $dailyIn,
                'daily_out' => $dailyOut
            ];
        });

        return [
            'items' => $items,
            'pagination' => $paginatedMaterials,
            'categories' => Category::all(),
            'periodLabel' => $this->getPeriodLabel($startDate, $endDate),
            'startDateParam' => $startDate->format('Y-m-d'),
            'endDateParam' => $endDate->format('Y-m-d'),
        ];
    }

    protected function getPeriodLabel($start, $end)
    {
        if ($this->week) {
            return "Minggu Ke-" . $this->week . " (" . $start->format('d M') . " - " . $end->format('d M Y') . ")";
        }
        return $start->translatedFormat('F Y');
    }
};
?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Saldo & Mutasi</h1>
            <p class="text-sm text-gray-500 mt-1">Rekapitulasi saldo awal, barang masuk, keluar, dan saldo akhir per
                material.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button wire:click="exportExcel"
                class="bg-blue-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-500/20 flex items-center gap-2 hover:bg-blue-600 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Bulanan
            </button>

            <button onclick="printReport()"
                class="bg-gray-800 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-gray-800/20 flex items-center gap-2 hover:bg-gray-900 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak (Print)
            </button>

            @if($week)
                <button wire:click="exportWeekly"
                    class="bg-emerald-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-emerald-500/20 flex items-center gap-2 hover:bg-emerald-600 transition-all animate-fade-in">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Mingguan
                </button>
            @endif
            <button wire:click="resetFilters"
                class="text-[10px] font-bold text-red-500 hover:text-red-600 uppercase tracking-widest px-2 transition-colors border border-red-200 rounded-lg py-2 bg-red-50/50">Reset
                Filter</button>

            <div class="flex items-center gap-2 bg-white rounded-xl px-3 py-1.5 ring-1 ring-accent/5 shadow-sm">
                <select wire:model.live="year" class="text-xs font-bold text-gray-700 outline-none bg-transparent">
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
                <span class="text-gray-300">|</span>
                <select wire:model.live="month" class="text-xs font-bold text-gray-700 outline-none bg-transparent">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ sprintf('%02d', $m) }}">{{ Carbon::create(2024, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
                <span class="text-gray-300">|</span>
                <select wire:model.live="week" class="text-xs font-bold text-gray-700 outline-none bg-transparent">
                    <option value="">Semua Minggu</option>
                    <option value="1">Minggu 1</option>
                    <option value="2">Minggu 2</option>
                    <option value="3">Minggu 3</option>
                    <option value="4">Minggu 4</option>
                    <option value="5">Minggu 5</option>
                </select>
            </div>

            <select wire:model.live="category_id"
                class="bg-white rounded-xl px-4 py-2.5 text-xs font-bold text-gray-700 ring-1 ring-accent/5 shadow-sm outline-none">
                <option value="">Semua Kategori</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-card ring-1 ring-accent/5 overflow-hidden">
        <div class="p-8 border-b border-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center text-accent">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <span class="text-[10px] font-black text-accent uppercase tracking-widest block">Periode
                        Laporan</span>
                    <span class="text-sm font-bold text-gray-800">{{ $periodLabel }}</span>
                </div>
            </div>

            @if($week)
            <div class="flex items-center gap-2 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5H11V7H13V5ZM13 9H11V11H13V9ZM13 13H11V15H13V13ZM13 17H11V19H13V17ZM17 5H15V7H17V5ZM17 9H15V11H17V9ZM17 13H15V15H17V13ZM17 17H15V19H17V17ZM21 5H19V7H21V5ZM21 9H19V11H21V9ZM21 13H19V15H21V13ZM21 17H19V19H21V17ZM9 5H7V7H9V5ZM9 9H7V11H9V9ZM9 13H7V15H9V13ZM9 17H7V19H9V17ZM5 5H3V7H5V5ZM5 9H3V11H5V9ZM5 13H3V15H5V13ZM5 17H3V19H5V17Z" />
                </svg>
                Geser tabel ke kanan untuk rincian harian
            </div>
            @endif
        </div>

        @if($week)
        <!-- Top Scrollbar for Long Tables -->
        <div id="top-scroll-container" class="overflow-x-auto overflow-y-hidden border-b border-gray-50 bg-gray-50/30 sticky top-0 z-30" style="height: 12px;">
            <div id="top-scroll-content" style="height: 12px;"></div>
        </div>
        @endif

        <div id="table-scroll-container" class="overflow-x-auto">
            <table id="report-table" class="w-full text-left">
                <thead>
                    <tr class="bg-base/50">
                        <th
                            class="sticky left-0 z-20 bg-gray-50/95 backdrop-blur px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 min-w-[300px]">
                            Material
                        </th>
                        <th
                            class="sticky left-[300px] z-20 bg-gray-50/95 backdrop-blur px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-center min-w-[100px]">
                            Satuan</th>
                        <th
                            class="sticky left-[400px] z-20 bg-gray-50/95 backdrop-blur px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right min-w-[150px]">
                            Saldo Awal</th>

                        @if($week)
                            @php
                                $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                            @endphp
                            @foreach($days as $day)
                                <th
                                    class="px-4 py-4 text-[10px] font-black uppercase tracking-widest text-emerald-500 text-center min-w-[80px] bg-emerald-50/20">
                                    {{ $day }}
                                </th>
                            @endforeach
                        @endif

                        <th
                            class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-emerald-600 text-right bg-emerald-50/30 min-w-[120px]">
                            Total Masuk (+)</th>

                        @if($week)
                            @foreach($days as $day)
                                <th
                                    class="px-4 py-4 text-[10px] font-black uppercase tracking-widest text-red-500 text-center min-w-[80px] bg-red-50/20">
                                    {{ $day }}
                                </th>
                            @endforeach
                        @endif

                        <th
                            class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-red-600 text-right bg-red-50/30 min-w-[120px]">
                            Total Keluar (-)</th>
                        <th
                            class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-900 text-right min-w-[150px]">
                            Saldo Akhir</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($items as $item)
                        <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors group">
                            <td class="sticky left-0 z-10 bg-white group-hover:bg-gray-50 transition-colors px-8 py-4">
                                <span class="text-xs font-bold text-gray-800">{{ $item->name }}</span>
                                <span
                                    class="block text-[9px] text-gray-400 uppercase tracking-tighter">{{ $item->category }}</span>
                            </td>
                            <td
                                class="sticky left-[300px] z-10 bg-white group-hover:bg-gray-50 transition-colors px-6 py-4 text-center">
                                <span class="text-[10px] font-bold text-gray-500 uppercase">{{ $item->unit }}</span>
                            </td>
                            <td
                                class="sticky left-[400px] z-10 bg-white group-hover:bg-gray-50 transition-colors px-6 py-4 text-right">
                                <span
                                    class="text-xs font-black text-gray-700">{{ number_format($item->opening_balance, 0, ',', '.') }}</span>
                            </td>

                            @if($week)
                                @foreach($item->daily_in as $val)
                                    <td class="px-4 py-4 text-center bg-emerald-50/5">
                                        <span class="text-[11px] {{ $val > 0 ? 'font-bold text-emerald-600' : 'text-gray-400' }}">
                                            {{ $val > 0 ? number_format($val, 0, ',', '.') : '0' }}
                                        </span>
                                    </td>
                                @endforeach
                            @endif

                            <td class="px-6 py-4 text-right bg-emerald-50/20">
                                <span
                                    class="text-xs font-black text-emerald-600">{{ number_format($item->total_in, 0, ',', '.') }}</span>
                            </td>

                            @if($week)
                                @foreach($item->daily_out as $val)
                                    <td class="px-4 py-4 text-center bg-red-50/5">
                                        <span class="text-[11px] {{ $val > 0 ? 'font-bold text-red-600' : 'text-gray-400' }}">
                                            {{ $val > 0 ? number_format($val, 0, ',', '.') : '0' }}
                                        </span>
                                    </td>
                                @endforeach
                            @endif

                            <td class="px-6 py-4 text-right bg-red-50/20">
                                <span
                                    class="text-xs font-black text-red-600">{{ number_format($item->total_out, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <span
                                    class="text-xs font-black text-gray-900">{{ number_format($item->final_balance, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    wire:click="$dispatch('show-riwayat-saldo', [{{ $item->id }}, '{{ $startDateParam }}', '{{ $endDateParam }}'])"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-accent/5 hover:bg-accent text-accent hover:text-white rounded-lg text-[9px] font-black uppercase transition-all shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Riwayat
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-8 py-12 text-center text-gray-400 italic">Data tidak ditemukan untuk
                                periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-8 py-6 bg-base/20 border-t border-gray-50">
            {{ $pagination->links() }}
        </div>
    </div>

    <script>
        function printReport() {
            const originalTitle = document.title;
            document.title = "Laporan Saldo & Mutasi - {{ $periodLabel }}";
            
            document.getElementById('print-area').classList.add('print-active');
            window.print();
            document.getElementById('print-area').classList.remove('print-active');
            
            document.title = originalTitle;
        }

        document.addEventListener('livewire:initialized', () => {
            const syncScroll = () => {
                const topScroll = document.getElementById('top-scroll-container');
                const topContent = document.getElementById('top-scroll-content');
                const tableScroll = document.getElementById('table-scroll-container');
                const table = document.getElementById('report-table');

                if (topScroll && tableScroll && table && topContent) {
                    // Set top content width to match table width
                    topContent.style.width = table.scrollWidth + 'px';

                    // Sync Top to Bottom
                    topScroll.onscroll = function() {
                        tableScroll.scrollLeft = topScroll.scrollLeft;
                    };

                    // Sync Bottom to Top
                    tableScroll.onscroll = function() {
                        topScroll.scrollLeft = tableScroll.scrollLeft;
                    };
                }
            };

            // Run on init
            syncScroll();

            // Re-run after Livewire updates
            Livewire.hook('morph.updated', (el, component) => {
                syncScroll();
            });
        });
    </script>

    <div id="print-area" class="hidden print-target bg-white text-black text-sm" style="font-family: 'Times New Roman', serif;">
        <img src="{{ asset('assets/kop.png') }}" class="w-full h-auto mb-6">
        
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold uppercase leading-tight">LAPORAN SALDO & MUTASI BARANG</h1>
            <p class="text-md font-bold mt-1">Periode: {{ $periodLabel }}</p>
        </div>

        <table class="w-full border-collapse border border-black text-[9px]">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-black px-1 py-1 text-center w-6" rowspan="{{ $week ? '2' : '1' }}">No</th>
                    <th class="border border-black px-2 py-1 text-left" rowspan="{{ $week ? '2' : '1' }}">Nama Material</th>
                    <th class="border border-black px-1 py-1 text-center" rowspan="{{ $week ? '2' : '1' }}">Satuan</th>
                    <th class="border border-black px-1 py-1 text-right" rowspan="{{ $week ? '2' : '1' }}">Saldo Awal</th>

                    @if($week)
                        @php $daysPrint = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']; @endphp
                        <th class="border border-black px-1 py-1 text-center" colspan="7">Masuk Harian</th>
                    @endif
                    <th class="border border-black px-1 py-1 text-right" rowspan="{{ $week ? '2' : '1' }}">Total Masuk</th>

                    @if($week)
                        <th class="border border-black px-1 py-1 text-center" colspan="7">Keluar Harian</th>
                    @endif
                    <th class="border border-black px-1 py-1 text-right" rowspan="{{ $week ? '2' : '1' }}">Total Keluar</th>
                    
                    <th class="border border-black px-1 py-1 text-right" rowspan="{{ $week ? '2' : '1' }}">Saldo Akhir</th>
                </tr>
                @if($week)
                <tr class="bg-gray-50">
                    @foreach($daysPrint as $day) <th class="border border-black px-1 py-1 text-center text-[7px]">{{ substr($day, 0, 3) }}</th> @endforeach
                    @foreach($daysPrint as $day) <th class="border border-black px-1 py-1 text-center text-[7px]">{{ substr($day, 0, 3) }}</th> @endforeach
                </tr>
                @endif
            </thead>
            <tbody>
                @forelse($items as $index => $item)
                <tr>
                    <td class="border border-black px-1 py-1 text-center">{{ $index + 1 }}</td>
                    <td class="border border-black px-2 py-1 font-bold uppercase">{{ $item->name }}</td>
                    <td class="border border-black px-1 py-1 text-center uppercase">{{ $item->unit }}</td>
                    <td class="border border-black px-1 py-1 text-right">{{ number_format($item->opening_balance, 0, ',', '.') }}</td>

                    @if($week)
                        @foreach($item->daily_in as $val)
                            <td class="border border-black px-1 py-1 text-center">{{ $val > 0 ? number_format($val, 0, ',', '.') : '-' }}</td>
                        @endforeach
                    @endif
                    <td class="border border-black px-1 py-1 text-right font-bold">{{ number_format($item->total_in, 0, ',', '.') }}</td>

                    @if($week)
                        @foreach($item->daily_out as $val)
                            <td class="border border-black px-1 py-1 text-center">{{ $val > 0 ? number_format($val, 0, ',', '.') : '-' }}</td>
                        @endforeach
                    @endif
                    <td class="border border-black px-1 py-1 text-right font-bold">{{ number_format($item->total_out, 0, ',', '.') }}</td>
                    
                    <td class="border border-black px-1 py-1 text-right font-black">{{ number_format($item->final_balance, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $week ? '21' : '7' }}" class="border border-black px-2 py-4 text-center italic">Tidak ada data</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="mt-8 text-right pr-12 text-[12px]">
            <p>Jakarta, {{ \Carbon\Carbon::now()->format('d F Y') }}</p>
            <p class="mt-1">Petugas / Admin,</p>
            <div class="h-16"></div>
            <p class="font-bold underline uppercase">{{ auth()->user()->name }}</p>
        </div>
    </div>

    <style>
        .print-target { display: none; }
        
        @media print {
            @page { size: landscape; margin: 1cm; }
            body * {
                visibility: hidden;
            }
            .print-active, .print-active * {
                visibility: visible;
            }
            .print-active {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                display: block !important;
                padding: 0 !important;
            }
        }
    </style>
</div>