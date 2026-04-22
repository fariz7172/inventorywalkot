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
        $superadminRole = Role::create(['name' => 'superadmin']);
        $gudangRole = Role::create(['name' => 'gudang']);

        // Membuat Akun Superadmin
        $superadmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password')
        ]);
        $superadmin->assignRole($superadminRole);

        // Membuat Akun Orang Gudang
        $gudang = User::create([
            'name' => 'Petugas Gudang',
            'email' => 'gudang@gmail.com',
            'password' => Hash::make('password')
        ]);
        $gudang->assignRole($gudangRole);
    }
}
