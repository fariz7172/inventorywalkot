<?php

namespace App\Exports;

use App\Models\InventoryTransaction;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use DB;

class TransactionHistoryExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $search, $startDate, $endDate, $type;
    
    public function __construct($search, $startDate, $endDate, $type = 'all')
    {
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
    }

    public function view(): View
    {
        $query = InventoryTransaction::with(['material', 'user', 'deliveryOrder']);

        if ($this->type && $this->type !== 'all') {
            $query->where('type', $this->type);
        }

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('reference_number', 'like', '%' . $this->search . '%')
                  ->orWhere('supplier', 'like', '%' . $this->search . '%')
                  ->orWhereHas('material', function($mq) {
                      $mq->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $transactions = $query->orderBy('created_at', 'desc')
                            ->orderBy('reference_number', 'asc')
                            ->get();

        // Grouping in PHP to keep logic simple
        $grouped = $transactions->groupBy(function($item) {
            return ($item->reference_number ?: 'SJ-' . $item->delivery_order_id) . '|' . $item->type . '|' . $item->created_at->format('Y-m-d H:i');
        });

        return view('exports.transaction-history', [
            'groups' => $grouped
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Standard styles can be added here if needed, but we'll use Blade for most styling
        ];
    }
}
