<?php

namespace App\Exports;

use App\Models\InventoryTransaction;
use App\Models\Material;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaterialHistoryExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $materialId, $startDate, $endDate;

    public function __construct($materialId, $startDate, $endDate)
    {
        $this->materialId = $materialId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        $material = Material::findOrFail($this->materialId);

        // 1. Calculate Opening Balance
        $openingBalance = InventoryTransaction::where('material_id', $this->materialId)
            ->whereDate('created_at', '<', $this->startDate)
            ->selectRaw('SUM(volume_masuk) - SUM(volume_keluar) as balance')
            ->value('balance') ?? 0;

        // 2. Get Transactions in Period
        $transactions = InventoryTransaction::with('deliveryOrder')
            ->where('material_id', $this->materialId)
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->orderBy('created_at', 'asc') // Ascending for easier balance tracking in Excel
            ->get();

        return view('exports.material-history', [
            'material' => $material,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'openingBalance' => $openingBalance,
            'transactions' => $transactions
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['italic' => true]],
            4 => ['font' => ['bold' => true]],
        ];
    }
}
