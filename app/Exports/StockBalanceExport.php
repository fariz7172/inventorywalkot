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

class StockBalanceExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $year;
    protected $month;
    protected $week;
    protected $category_id;
    protected $search;

    public function __construct($year = null, $month = null, $week = null, $category_id = null, $search = null)
    {
        $this->year = $year ?: date('Y');
        $this->month = $month ?: date('m');
        $this->week = $week;
        $this->category_id = $category_id;
        $this->search = $search;
    }

    public function collection()
    {
        $date = Carbon::create((int)$this->year, (int)$this->month, 1);
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        if ($this->week) {
            $startDate = $date->copy()->startOfMonth()->addWeeks((int)$this->week - 1)->startOfWeek();
            $endDate = $startDate->copy()->endOfWeek();
            if ($startDate->month != (int)$this->month) $startDate = $date->copy()->startOfMonth();
            if ($endDate->month != (int)$this->month) $endDate = $date->copy()->endOfMonth();
        }

        $materials = Material::with('category')
            ->when($this->category_id, fn($q) => $q->where('category_id', $this->category_id))
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->get();

        return $materials->map(function($material) use ($startDate, $endDate) {
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

            return (object) [
                'name' => $material->name,
                'category' => $material->category->name ?? '-',
                'unit' => $material->unit,
                'opening_balance' => $openingBalance,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'final_balance' => $finalBalance,
            ];
        });
    }

    public function headings(): array
    {
        return [
            ['LAPORAN SALDO & MUTASI STOK'],
            ['Periode: ' . $this->getPeriodLabel()],
            [''],
            ['Material', 'Satuan', 'Saldo Awal', 'Masuk (+)', 'Keluar (-)', 'Saldo Akhir']
        ];
    }

    public function map($row): array
    {
        return [
            $row->name . ' (' . $row->category . ')',
            $row->unit,
            $row->opening_balance,
            $row->total_in,
            $row->total_out,
            $row->final_balance,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        
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
        return 'Laporan Saldo Stok';
    }

    protected function getPeriodLabel()
    {
        $date = Carbon::create((int)$this->year, (int)$this->month, 1);
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        if ($this->week) {
            $start = $date->copy()->startOfMonth()->addWeeks((int)$this->week - 1)->startOfWeek();
            $end = $start->copy()->endOfWeek();
            if ($start->month != (int)$this->month) $start = $date->copy()->startOfMonth();
            if ($end->month != (int)$this->month) $end = $date->copy()->endOfMonth();
            
            return "Minggu Ke-" . $this->week . " (" . $start->format('d M') . " - " . $end->format('d M Y') . ")";
        }

        return $start->translatedFormat('F Y');
    }
}
