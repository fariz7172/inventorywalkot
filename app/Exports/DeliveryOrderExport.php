<?php

namespace App\Exports;

use App\Models\DeliveryOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DeliveryOrderExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $search, $status, $startDate, $endDate;

    public function __construct($search, $status, $startDate, $endDate)
    {
        $this->search = $search;
        $this->status = $status;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return DeliveryOrder::with('materials')
            ->when($this->search, function($q) {
                $q->where(function($sq) {
                    $sq->where('surat_jalan_no', 'like', '%' . $this->search . '%')
                      ->orWhere('lokasi', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->startDate, fn($q) => $q->whereDate('tanggal', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('tanggal', '<=', $this->endDate))
            ->orderBy('tanggal', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Lokasi Tujuan',
            'Pemohon',
            'Petugas Admin',
            'Penerima Barang',
            'No. Surat Tugas',
            'Kecamatan/Pelaksana',
            'Keterangan',
            'Daftar Material (Detail)',
        ];
    }

    public function map($order): array
    {
        $materialList = $order->materials->map(function($m) {
            return "- " . $m->name . " (Jumlah: " . (float)$m->pivot->requested_volume . " " . $m->unit . ")";
        })->implode("\n");

        return [
            $order->tanggal->format('d/m/Y'),
            $order->lokasi,
            $order->pemohon,
            $order->petugas,
            $order->penerima,
            $order->surat_jalan_no,
            $order->pelaksana_kecamatan,
            $order->keterangan,
            $materialList ?: '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Get number of rows to apply wrapping
        $lastRow = $sheet->getHighestRow();
        
        $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('A1:I' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
