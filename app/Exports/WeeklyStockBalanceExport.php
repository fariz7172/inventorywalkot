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

    public function collection()
    {
        $date = Carbon::create((int)$this->year, (int)$this->month, 1);
        $startDate = $date->copy()->startOfMonth()->addWeeks((int)$this->week - 1)->startOfWeek();
        $endDate = $startDate->copy()->endOfWeek();
        
        if ($startDate->month != (int)$this->month) $startDate = $date->copy()->startOfMonth();
        if ($endDate->month != (int)$this->month) $endDate = $date->copy()->endOfMonth();

        $materials = Material::with('category')
            ->when($this->category_id, fn($q) => $q->where('category_id', $this->category_id))
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->get();

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $startDate->copy()->addDays($i);
        }

        return $materials->map(function($material) use ($startDate, $endDate, $days) {
            $mutationAfter = InventoryTransaction::where('material_id', $material->id)
                ->where('created_at', '>', $endDate)
                ->selectRaw('SUM(volume_masuk) as in_sum, SUM(volume_keluar) as out_sum')
                ->first();
            
            $netAfter = (float)($mutationAfter->in_sum ?? 0) - (float)($mutationAfter->out_sum ?? 0);
            $finalBalance = (float)$material->current_volume - $netAfter;

            $periodTransactions = InventoryTransaction::where('material_id', $material->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('SUM(volume_masuk) as total_in, SUM(volume_keluar) as total_out')
                ->first();

            $totalIn = (float)($periodTransactions->total_in ?? 0);
            $totalOut = (float)($periodTransactions->total_out ?? 0);
            $openingBalance = $finalBalance - ($totalIn - $totalOut);

            $dailyIn = [];
            $dailyOut = [];
            
            $inTrx = InventoryTransaction::where('material_id', $material->id)
                ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                ->where('type', 'in')
                ->selectRaw('DATE(created_at) as date, SUM(volume_masuk) as total')
                ->groupBy('date')->get()->pluck('total', 'date');
            
            $outTrx = InventoryTransaction::where('material_id', $material->id)
                ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                ->where('type', 'out')
                ->selectRaw('DATE(created_at) as date, SUM(volume_keluar) as total')
                ->groupBy('date')->get()->pluck('total', 'date');

            foreach ($days as $day) {
                $dateStr = $day->format('Y-m-d');
                $dailyIn[] = (float)($inTrx[$dateStr] ?? 0);
                $dailyOut[] = (float)($outTrx[$dateStr] ?? 0);
            }

            return (object) [
                'name' => $material->name,
                'category' => $material->category->name ?? '-',
                'unit' => $material->unit,
                'opening_balance' => $openingBalance,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'final_balance' => $finalBalance,
                'daily_in' => $dailyIn,
                'daily_out' => $dailyOut,
            ];
        });
    }

    public function headings(): array
    {
        $headers = ['Material', 'Satuan', 'Saldo Awal'];
        $daysLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        
        foreach ($daysLabels as $day) $headers[] = $day . ' (Masuk)';
        $headers[] = 'Total Masuk (+)';
        
        foreach ($daysLabels as $day) $headers[] = $day . ' (Keluar)';
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

        foreach ($row->daily_in as $val) $data[] = $val ?: '-';
        $data[] = $row->total_in;

        foreach ($row->daily_out as $val) $data[] = $val ?: '-';
        $data[] = $row->total_out;
        
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
        $date = Carbon::create((int)$this->year, (int)$this->month, 1);
        $start = $date->copy()->startOfMonth()->addWeeks((int)$this->week - 1)->startOfWeek();
        $end = $start->copy()->endOfWeek();
        if ($start->month != (int)$this->month) $start = $date->copy()->startOfMonth();
        if ($end->month != (int)$this->month) $end = $date->copy()->endOfMonth();
        
        return $start->format('d M') . " - " . $end->format('d M Y');
    }
}
