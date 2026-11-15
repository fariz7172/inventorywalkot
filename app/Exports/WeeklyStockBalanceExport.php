<?php

namespace App\Exports;

use App\Models\Material;
use App\Models\InventoryTransaction;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WeeklyStockBalanceExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $year;
    protected $month;
    protected $week;
    protected $category_id;
    protected $search;

    public function __construct($year, $month, $week, $category_id = null, $search = null)
    {
        $this->year = $year;
        $this->month = $month;
        $this->week = $week;
        $this->category_id = $category_id;
        $this->search = $search;
    }

    /**
     * Menghasilkan 7 hari (Senin s/d Minggu) untuk minggu yang dipilih.
     */
    protected function getDays(): array
    {
        $date = Carbon::create((int)$this->year, (int)$this->month, 1);
        $displayStartDate = $date->copy()->startOfMonth()->addWeeks((int)$this->week - 1)->startOfWeek();

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $displayStartDate->copy()->addDays($i);
        }

        return $days;
    }

    public function collection()
    {
        $date = Carbon::create((int)$this->year, (int)$this->month, 1);
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $displayStartDate = $date->copy()->startOfMonth()->addWeeks((int)$this->week - 1)->startOfWeek();
        $displayEndDate = $displayStartDate->copy()->endOfWeek();

        // Rentang kalkulasi mutasi clamped ke dalam bulan berjalan agar konsisten dengan view
        $calcStartDate = $displayStartDate->copy();
        if ($calcStartDate->lt($monthStart)) {
            $calcStartDate = $monthStart->copy();
        }

        $calcEndDate = $displayEndDate->copy();
        if ($calcEndDate->gt($monthEnd)) {
            $calcEndDate = $monthEnd->copy();
        }

        $materials = Material::with('category')
            ->when($this->category_id, fn($q) => $q->where('category_id', $this->category_id))
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name', 'asc')
            ->get();

        $days = $this->getDays();

        $applyKecamatanFilter = function($q) {
            if (auth()->check() && auth()->user()->kecamatan_id) {
                $userKecId = auth()->user()->kecamatan_id;
                $lokasiKecamatan = \App\Models\Rab::where('kecamatan_id', $userKecId)->pluck('lokasi');
                $q->where(function($subQ) use ($userKecId, $lokasiKecamatan) {
                    $subQ->whereHas('user', function($uq) use ($userKecId) {
                        $uq->where('kecamatan_id', $userKecId);
                    })->orWhereHas('deliveryOrder', function($dq) use ($lokasiKecamatan) {
                        $dq->whereIn('lokasi', $lokasiKecamatan);
                    });
                });
            }
            return $q;
        };

        return $materials->map(function($material) use ($calcStartDate, $calcEndDate, $days, $applyKecamatanFilter) {
            // 1. Saldo Awal: Semua transaksi SEBELUM tanggal awal kalkulasi
            $qOp = InventoryTransaction::where('material_id', $material->id)
                ->where('created_at', '<', $calcStartDate->copy()->startOfDay())
                ->selectRaw('SUM(volume_masuk) as total_in, SUM(volume_keluar) as total_out');
            $applyKecamatanFilter($qOp);
            $openingTrx = $qOp->first();

            $openingBalance = (float)($openingTrx->total_in ?? 0) - (float)($openingTrx->total_out ?? 0);

            // 2. Mutasi Selama Periode: Transaksi ANTARA tanggal awal dan akhir kalkulasi
            $qPeriod = InventoryTransaction::where('material_id', $material->id)
                ->whereBetween('created_at', [$calcStartDate->copy()->startOfDay(), $calcEndDate->copy()->endOfDay()])
                ->selectRaw('SUM(volume_masuk) as total_in, SUM(volume_keluar) as total_out');
            $applyKecamatanFilter($qPeriod);
            $periodTransactions = $qPeriod->first();

            $totalIn = (float)($periodTransactions->total_in ?? 0);
            $totalOut = (float)($periodTransactions->total_out ?? 0);

            // 3. Saldo Akhir
            $finalBalance = $openingBalance + $totalIn - $totalOut;

            // 4. Data Harian untuk Kolom 7 Hari (Senin - Minggu)
            $qDailyIn = InventoryTransaction::where('material_id', $material->id)
                ->whereBetween('created_at', [$calcStartDate->copy()->startOfDay(), $calcEndDate->copy()->endOfDay()])
                ->where('type', 'in')
                ->selectRaw('DATE(created_at) as date, SUM(volume_masuk) as total')
                ->groupBy('date');
            $applyKecamatanFilter($qDailyIn);
            $inTrx = $qDailyIn->get()->pluck('total', 'date');

            $qDailyOut = InventoryTransaction::where('material_id', $material->id)
                ->whereBetween('created_at', [$calcStartDate->copy()->startOfDay(), $calcEndDate->copy()->endOfDay()])
                ->where('type', 'out')
                ->selectRaw('DATE(created_at) as date, SUM(volume_keluar) as total')
                ->groupBy('date');
            $applyKecamatanFilter($qDailyOut);
            $outTrx = $qDailyOut->get()->pluck('total', 'date');

            $dailyIn = [];
            $dailyOut = [];
            foreach ($days as $day) {
                $dateStr = $day->format('Y-m-d');
                if ($day->month != (int)$this->month) {
                    $dailyIn[] = '-';
                    $dailyOut[] = '-';
                } else {
                    $dailyIn[] = isset($inTrx[$dateStr]) ? (float)$inTrx[$dateStr] : 0;
                    $dailyOut[] = isset($outTrx[$dateStr]) ? (float)$outTrx[$dateStr] : 0;
                }
            }

            return (object) [
                'name' => $material->name,
                'category' => $material->category->name ?? '-',
                'unit' => $material->unit,
                'opening_balance' => round($openingBalance, 2),
                'total_in' => round($totalIn, 2),
                'total_out' => round($totalOut, 2),
                'final_balance' => round($finalBalance, 2),
                'daily_in' => $dailyIn,
                'daily_out' => $dailyOut,
            ];
        });
    }

    public function headings(): array
    {
        $headers = ['Material', 'Satuan', 'Saldo Awal'];

        $indoDays = [
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
            'Sunday'    => 'Minggu',
        ];

        $days = $this->getDays();

        // 7 Hari Masuk dengan Tanggal
        foreach ($days as $day) {
            $dayName = $indoDays[$day->format('l')] ?? $day->format('l');
            $headers[] = $dayName . ' (' . $day->format('d/m') . ') (Masuk)';
        }
        $headers[] = 'Total Masuk (+)';

        // 7 Hari Keluar dengan Tanggal
        foreach ($days as $day) {
            $dayName = $indoDays[$day->format('l')] ?? $day->format('l');
            $headers[] = $dayName . ' (' . $day->format('d/m') . ') (Keluar)';
        }
        $headers[] = 'Total Keluar (-)';

        $headers[] = 'Saldo Akhir';

        return [
            ['LAPORAN MUTASI STOK MINGGUAN'],
            ['Periode: Minggu Ke-' . $this->week . ' (' . $this->getPeriodRange() . ')'],
            [''],
            $headers
        ];
    }

    public function map($row): array
    {
        $data = [
            $row->name . ' (' . $row->category . ')',
            $row->unit,
            $row->opening_balance,
        ];

        foreach ($row->daily_in as $val) {
            $data[] = ($val === '-' || $val == 0) ? '-' : $val;
        }
        $data[] = $row->total_in ?: '-';

        foreach ($row->daily_out as $val) {
            $data[] = ($val === '-' || $val == 0) ? '-' : $val;
        }
        $data[] = $row->total_out ?: '-';
        
        $data[] = $row->final_balance;

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = $sheet->getHighestColumn();
        $sheet->mergeCells("A1:{$highestColumn}1");
        $sheet->mergeCells("A2:{$highestColumn}2");
        
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['alignment' => ['horizontal' => 'center']],
            4 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F3F4F6']],
                'borders' => ['allBorders' => ['borderStyle' => 'thin']]
            ],
        ];
    }

    public function title(): string
    {
        return 'Laporan Mingguan';
    }

    protected function getPeriodRange()
    {
        $days = $this->getDays();
        $start = $days[0];
        $end = $days[6];

        return $start->format('d M') . " - " . $end->format('d M Y');
    }
}
