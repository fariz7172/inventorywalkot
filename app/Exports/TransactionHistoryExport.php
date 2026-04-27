<?php

namespace App\Exports;

use App\Models\InventoryTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionHistoryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search, $startDate, $endDate, $type;
    
    public function __construct($search, $startDate, $endDate, $type = 'all')
    {
        $this->search = $search;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
    }

    public function collection()
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

        // Sort by date DESC, then by reference to keep items together
        return $query->orderBy('created_at', 'desc')
                    ->orderBy('reference_number', 'asc')
                    ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Jam',
            'Jenis',
            'No. Referensi',
            'Material',
            'Volume',
            'Satuan',
            'Sumber / Tujuan',
            'Petugas',
            'Catatan'
        ];
    }

    public function map($trx): array
    {
        return [
            $trx->created_at->format('d/m/Y'),
            $trx->created_at->format('H:i'),
            $trx->type === 'in' ? 'MASUK' : 'KELUAR',
            $trx->reference_number ?: '-',
            $trx->material->name,
            ($trx->type === 'in' ? $trx->volume_masuk : $trx->volume_keluar),
            $trx->material->unit,
            $trx->type === 'in' ? ($trx->supplier ?: 'Restock Internal') : ($trx->deliveryOrder->lokasi ?? 'Pengeluaran'),
            $trx->user->name ?? 'System',
            $trx->note ?: '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']]],
        ];
    }
}
