<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KecamatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kecamatans = [
            ['nama_kecamatan' => 'Kecamatan Pusat'],
            ['nama_kecamatan' => 'Kecamatan Utara'],
            ['nama_kecamatan' => 'Kecamatan Selatan'],
        ];

        foreach ($kecamatans as $kecamatan) {
            \App\Models\Kecamatan::firstOrCreate($kecamatan);
        }
    }
}
