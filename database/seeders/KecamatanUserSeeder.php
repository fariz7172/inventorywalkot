<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kecamatan;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class KecamatanUserSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan role kecamatan_admin ada
        $role = Role::firstOrCreate(['name' => 'kecamatan_admin']);

        $data = [
            'tanjungpriok' => 'Tanjung Priok',
            'koja' => 'Koja',
            'pademangan' => 'Pademangan',
            'cilincing' => 'Cilincing',
            'penjaringan' => 'Penjaringan',
            'kelapagading' => 'Kelapa Gading',
        ];

        foreach ($data as $emailPrefix => $namaKecamatan) {
            // Buat atau cari Kecamatan
            $kecamatan = Kecamatan::firstOrCreate(['nama_kecamatan' => $namaKecamatan]);

            // Buat User
            $user = User::firstOrCreate(
                ['email' => $emailPrefix . '@admin.com'],
                [
                    'name' => 'Admin ' . $namaKecamatan,
                    'password' => Hash::make('password'),
                    'kecamatan_id' => $kecamatan->id,
                ]
            );

            // Assign Role
            if (!$user->hasRole('kecamatan_admin')) {
                $user->assignRole($role);
            }
        }
    }
}
