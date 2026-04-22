<?php

namespace App\Exports;

use App\Models\StockOpnameItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockOpnameExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filterPeriod;
    protected $filterDifference;

    public function __construct($filterPeriod, $filterDifference)
    {
        $this->filterPeriod = $filterPeriod;
        $this->filterDifference = $filterDifference;
    }

    public function collection()
    {
        $query = StockOpnameItem::with(['opname.user', 'material'])
            ->whereHas('opname', function($q) {
                if ($this->filterPeriod === 'this_week') {
                    $q->whereBetween('opname_date', [now()->startOfWeek()->format('Y-m-d'), now()->endOfWeek()->format('Y-m-d')]);
                } elseif ($this->filterPeriod === 'this_month') {
                    $q->whereMonth('opname_date', now()->month)
                      ->whereYear('opname_date', now()->year);
                } elseif ($this->filterPeriod === 'this_year') {
                    $q->whereYear('opname_date', now()->year);
                }
            });

        if ($this->filterDifference === 'has_diff') {
            $query->where('stock_opname_items.difference', '!=', 0);
        } elseif ($this->filterDifference === 'plus') {
            $query->where('stock_opname_items.difference', '>', 0);
        } elseif ($this->filterDifference === 'minus') {
            $query->where('stock_opname_items.difference', '<', 0);
        }

        return $query->join('stock_opnames', 'stock_opname_items.stock_opname_id', '=', 'stock_opnames.id')
            ->join('materials', 'stock_opname_items.material_id', '=', 'materials.id')
            ->select('stock_opname_items.*')
            ->orderBy('materials.name', 'asc')
            ->orderBy('stock_opnames.opname_date', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Material',
            'Satuan',
            'Stok Sistem',
            'Stok Fisik',
            'Selisih',
            'Keterangan',
            'Status',
            'Pelaksana'
        ];
    }

    public function map($item): array
    {
        return [
            $item->opname->opname_date->format('d/m/Y'),
            $item->material->name,
            $item->material->unit,
            (float)$item->system_volume,
            (float)$item->physical_volume,
            (float)$item->difference,
            $item->notes ?? '-',
            ucfirst($item->opname->status),
            $item->opname->user->name
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
