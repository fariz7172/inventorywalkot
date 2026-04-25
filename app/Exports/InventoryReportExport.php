<?php

namespace App\Exports;

use App\Models\InventoryTransaction;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryReportExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $period, $material_id, $search, $startDate, $endDate, $type;

    public function __construct($period, $material_id, $search, $startDate = null, $endDate = null, $type = '')
    {
        $this->period = $period;
        $this->material_id = $material_id;
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
    }

    public function view(): View
    {
        $query = InventoryTransaction::with(['material', 'deliveryOrder']);

        if ($this->material_id) {
            $query->where('material_id', $this->material_id);
        }

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('reference_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('material', fn($mq) => $mq->where('name', 'like', '%' . $this->search . '%'));
            });
        }

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        if ($this->period === 'today') {
            $query->whereDate('created_at', now());
        } elseif ($this->period === 'weekly') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->period === 'monthly') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        } elseif ($this->period === 'yearly') {
            $query->whereYear('created_at', now()->year);
        }

        $transactions = $query->orderBy('material_id')->orderBy('created_at', 'asc')->get();
        $grouped = $transactions->groupBy('material_id');

        return view('exports.inventory-report', [
            'grouped' => $grouped,
            'period' => $this->period
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style will be handled largely by Blade, but we can add global styles here if needed
        ];
    }
}
