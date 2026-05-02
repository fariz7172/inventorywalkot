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
            ['name' => 'Material', 'slug' => 'Material', 'desc' => 'Kategori  Material'],
            ['name' => 'Perkakas', 'slug' => 'Perkakas', 'desc' => 'Perkakas'],
            ['name' => 'Ban Mobil', 'slug' => 'Ban Mobil', 'desc' => 'Ban Mobil'],
            ['name' => 'AKI', 'slug' => 'AKI', 'desc' => 'AKI'],
        ];

        foreach ($categories as $cat) {
            $category = Category::create([
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'description' => $cat['desc']
            ]);

        }
    }
}
