<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RabSelisihExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $data, $month, $year, $monthName;

    public function __construct(array $data, $month, $year, $monthName)
    {
        $this->data = $data;
        $this->month = $month;
        $this->year = $year;
        $this->monthName = $monthName;
    }

    public function view(): View
    {
        return view('exports.rab-selisih-excel', [
            'data' => $this->data,
            'month' => $this->month,
            'year' => $this->year,
            'monthName' => $this->monthName,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
            3 => ['font' => ['italic' => true, 'size' => 9]],
            5 => ['font' => ['bold' => true]],
        ];
    }
}
