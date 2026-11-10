<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    public function test_admin_can_see_user_management(): void
    {
        $admin = User::role('superadmin')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get('/dashboard/users');
        $response->assertStatus(200);
        $response->assertSee('Manajemen User');
        $response->assertSee('Tambah User');
    }

    public function test_non_admin_can_access_own_profile(): void
    {
        $user = User::where('email', 'koja@admin.com')->first();
        if (!$user) {
            $user = User::role('kecamatan_admin')->first();
        }
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get('/dashboard/users');
        $response->assertStatus(200);
        $response->assertSee('Profil & Akun Saya');
        $response->assertDontSee('Tambah User');
    }

    public function test_non_admin_can_change_own_password(): void
    {
        $user = User::where('email', 'koja@admin.com')->first();
        if (!$user) {
            $user = User::role('kecamatan_admin')->first();
        }
        $this->assertNotNull($user);

        $this->actingAs($user);

        Volt::test('user.index')
            ->call('editUser', $user->id)
            ->set('name', $user->name)
            ->set('email', $user->email)
            ->set('password', 'newsecret123')
            ->call('saveUser')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('newsecret123', $user->password));

        // Kembalikan password ke default
        $user->update(['password' => Hash::make('password')]);
    }

    public function test_non_admin_cannot_edit_other_user(): void
    {
        $user = User::where('email', 'koja@admin.com')->first();
        $admin = User::role('superadmin')->first();

        $this->actingAs($user);

        Volt::test('user.index')
            ->call('editUser', $admin->id)
            ->assertSet('showModal', false)
            ->assertSee('Anda hanya memiliki izin untuk mengedit akun Anda sendiri!');
    }
}

