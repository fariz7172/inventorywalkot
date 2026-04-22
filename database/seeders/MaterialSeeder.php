<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Material;
use Illuminate\Support\Str;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Semen', 'slug' => 'semen', 'desc' => 'Kategori material semen'],
            ['name' => 'Pasir & Kerikil', 'slug' => 'pasir-kerikil', 'desc' => 'Material alam'],
            ['name' => 'Besi & Baja', 'slug' => 'besi-baja', 'desc' => 'Material konstruksi logam'],
            ['name' => 'Kayu', 'slug' => 'kayu', 'desc' => 'Material kayu konstruksi'],
        ];

        foreach ($categories as $cat) {
            $category = Category::create([
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'description' => $cat['desc']
            ]);

            if ($cat['name'] === 'Semen') {
                Material::create(['category_id' => $category->id, 'name' => 'Semen Padang 40kg', 'unit' => 'Sak', 'current_volume' => 500]);
                Material::create(['category_id' => $category->id, 'name' => 'Semen Tiga Roda 50kg', 'unit' => 'Sak', 'current_volume' => 200]);
            }

            if ($cat['name'] === 'Pasir & Kerikil') {
                Material::create(['category_id' => $category->id, 'name' => 'Pasir Beton', 'unit' => 'm3', 'current_volume' => 50]);
                Material::create(['category_id' => $category->id, 'name' => 'Batu Split 2/3', 'unit' => 'm3', 'current_volume' => 30]);
            }
            
            if ($cat['name'] === 'Besi & Baja') {
                Material::create(['category_id' => $category->id, 'name' => 'Besi Beton 10mm', 'unit' => 'Batang', 'current_volume' => 100]);
                Material::create(['category_id' => $category->id, 'name' => 'Kawat Beton', 'unit' => 'Kg', 'current_volume' => 25]);
            }
        }
    }
}
