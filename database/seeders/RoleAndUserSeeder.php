<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Membuat Role
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin']);
        $gudangRole = Role::firstOrCreate(['name' => 'gudang']);
        $kepalaGudangRole = Role::firstOrCreate(['name' => 'kepala_gudang']);
        $sudinRole = Role::firstOrCreate(['name' => 'sudin']);

        // Membuat Akun Superadmin
        $superadmin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password')
            ]
        );
        $superadmin->assignRole($superadminRole);

        // Membuat Akun Orang Gudang
        $gudang = User::firstOrCreate(
            ['email' => 'gudang@gmail.com'],
            [
                'name' => 'Petugas Gudang',
                'password' => Hash::make('password')
            ]
        );
        $gudang->assignRole($gudangRole);

        // Membuat Akun Kepala Gudang
        $kepalaGudang = User::firstOrCreate(
            ['email' => 'kepalagudang@gmail.com'],
            [
                'name' => 'Kepala Gudang',
                'password' => Hash::make('password')
            ]
        );
        $kepalaGudang->assignRole($kepalaGudangRole);

        // Membuat Akun SUDIN
        $sudin = User::firstOrCreate(
            ['email' => 'sudin@gmail.com'],
            [
                'name' => 'Kepala Suku Dinas (SUDIN)',
                'password' => Hash::make('password')
            ]
        );
        $sudin->assignRole($sudinRole);
    }
}
