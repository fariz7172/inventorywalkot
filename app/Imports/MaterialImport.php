<?php

namespace App\Imports;

use App\Models\Material;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Exception;

class MaterialImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        // Skip header row
        $dataRows = $rows->slice(1);

        foreach ($dataRows as $row) {
            // Indices:
            // 0: Kategori
            // 1: Nama Barang
            // 2: Satuan

            if (empty($row[0]) || empty($row[1])) {
                continue;
            }

            $categoryName = trim($row[0]);
            $materialName = trim($row[1]);
            $unit = trim($row[2] ?? 'PCS');

            // Find or Create Category
            $category = Category::where('name', $categoryName)->first();
            if (!$category) {
                $category = Category::create(['name' => $categoryName]);
            }

            // Find existing or Create new Material
            $material = Material::where('name', $materialName)->first();
            
            if ($material) {
                // Update existing
                $material->update([
                    'category_id' => $category->id,
                    'unit' => $unit,
                ]);
            } else {
                // Create new
                Material::create([
                    'name' => $materialName,
                    'category_id' => $category->id,
                    'unit' => $unit,
                    'current_volume' => 0,
                ]);
            }
        }
    }
}
