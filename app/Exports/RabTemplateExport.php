<?php

namespace App\Exports;

use App\Models\Material;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RabTemplateExport implements FromCollection, WithHeadings, WithStyles
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Material::orderBy('name', 'asc')->get()->map(function($material) {
            return [
                'nama_material' => $material->name,
                'target_kuota' => '', // Empty for user to fill
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nama Material',
            'Target Kuota (Isi dengan angka, kosongkan/isi 0 jika tidak perlu)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => ['font' => ['bold' => true]],
        ];
    }
}
