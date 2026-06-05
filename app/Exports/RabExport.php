<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RabExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $rab, $rows, $month, $year, $daysInMonth;

    public function __construct($rab, $rows, $month, $year, $daysInMonth)
    {
        $this->rab = $rab;
        $this->rows = $rows;
        $this->month = $month;
        $this->year = $year;
        $this->daysInMonth = $daysInMonth;
    }

    public function view(): View
    {
        return view('exports.rab-excel', [
            'rab' => $this->rab,
            'rows' => $this->rows,
            'month' => $this->month,
            'year' => $this->year,
            'daysInMonth' => $this->daysInMonth,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            // The header row
            4 => ['font' => ['bold' => true]],
        ];
    }
}
