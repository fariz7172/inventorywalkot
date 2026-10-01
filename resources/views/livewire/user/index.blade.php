<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use function Livewire\Volt\{state, computed, layout};

layout('layouts.admin');

state([
    'showModal' => false,
    'editingUser' => null,
    'name' => '',
    'nip' => '',
    'email' => '',
    'password' => '',
    'selected_role' => 'seksi_pompa',
]);

$users = computed(fn() => User::with('roles')->get());
$roles = computed(fn() => Role::all());

$openCreate = function() {
    $this->reset(['editingUser', 'name', 'nip', 'email', 'password']);
    $this->selected_role = 'seksi_pompa';
    $this->resetErrorBag();
    $this->showModal = true;
};

$saveUser = function() {
    $rules = [
        'name' => 'required|string|max:255',
        'nip' => 'nullable|string|max:50',
        'email' => [
            'required',
            'email',
            Rule::unique('users', 'email')->ignore($this->editingUser['id'] ?? null),
        ],
        'selected_role' => 'required|exists:roles,name',
    ];

    if (!$this->editingUser) {
        $rules['password'] = 'required|min:6';
    }

    $messages = [
        'name.required' => 'Nama lengkap wajib diisi.',
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'email.unique' => 'Email sudah digunakan oleh akun lain.',
        'password.required' => 'Password wajib diisi untuk user baru.',
        'password.min' => 'Password minimal harus 6 karakter.',
        'selected_role.required' => 'Silakan pilih salah satu peran/role.',
        'selected_role.exists' => 'Role yang dipilih tidak valid di sistem.',
    ];

    $this->validate($rules, $messages);

    try {
        if ($this->editingUser) {
            $user = User::findOrFail($this->editingUser['id']);
            $user->update([
                'name' => $this->name,
                'nip' => $this->nip,
                'email' => $this->email,
            ]);
            if (!empty($this->password)) {
                $user->update(['password' => Hash::make($this->password)]);
            }
            $user->syncRoles([$this->selected_role]);
            session()->flash('message', 'User berhasil diperbarui!');
        } else {
            $user = User::create([
                'name' => $this->name,
                'nip' => $this->nip,
                'email' => $this->email,
                'password' => Hash::make($this->password),
            ]);
            $user->assignRole($this->selected_role);
            session()->flash('message', 'User baru berhasil dibuat!');
        }

        $this->showModal = false;
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Error saving user: ' . $e->getMessage());
        session()->flash('error', 'Gagal menyimpan user: ' . $e->getMessage());
    }
};

$editUser = function(User $user) {
    $this->editingUser = $user->toArray();
    $this->name = $user->name;
    $this->nip = $user->nip ?? '';
    $this->email = $user->email;
    $this->selected_role = $user->roles->first()?->name ?? 'seksi_pompa';
    $this->password = '';
    $this->resetErrorBag();
    $this->showModal = true;
};

$deleteUser = function(User $user) {
    if ($user->id === auth()->id()) {
        session()->flash('error', 'Anda tidak bisa menghapus akun Anda sendiri!');
        return;
    }
    $user->delete();
    session()->flash('message', 'User berhasil dihapus.');
};

?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manajemen User</h1>
            <p class="text-sm text-gray-500">Kelola akses tim (Admin Kantor & Petugas Gudang).</p>
        </div>
        <button wire:click="openCreate" class="bg-accent text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-accent/20 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Tambah User
        </button>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold">
            {{ session('message') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-[2rem] shadow-card ring-1 ring-accent/5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-warm/40 border-b border-warm/60 text-gray-500 font-bold uppercase text-[11px]">
                        <th class="text-left px-6 py-4">Nama</th>
                        <th class="text-left px-6 py-4">NIP / NRK</th>
                        <th class="text-left px-6 py-4">Email</th>
                        <th class="text-center px-6 py-4">Role</th>
                        <th class="text-center px-6 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm/40">
                    @foreach($this->users as $u)
                    <tr class="hover:bg-base/40 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-accent/10 rounded-full flex items-center justify-center text-accent font-bold text-xs uppercase">
                                    {{ substr($u->name, 0, 2) }}
                                </div>
                                <span class="font-bold text-gray-800">{{ $u->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-xs font-semibold {{ $u->nip ? 'text-gray-700 bg-gray-100 px-2.5 py-1 rounded-lg' : 'text-gray-400 italic' }}">
                                {{ $u->nip ?: '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $u->email }}</td>
                        <td class="px-6 py-4 text-center">
                            @foreach($u->roles as $role)
                                @php
                                    $displayRoles = [
                                        'superadmin' => 'Pengurus Barang',
                                        'kecamatan_admin' => 'Kasubag',
                                        'sudin' => 'Kasudin',
                                        'pemel' => 'Pemeliharaan',
                                        'seksi_pompa' => 'Seksi Pompa',
                                        'pompa' => 'Seksi Pompa'
                                    ];
                                    $displayRoleName = $displayRoles[$role->name] ?? $role->name;
                                @endphp
                                <span class="inline-block px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider {{ $role->name === 'superadmin' ? 'bg-indigo-100 text-indigo-600' : ($role->name === 'sudin' ? 'bg-purple-100 text-purple-600' : ($role->name === 'kepala_gudang' ? 'bg-blue-100 text-blue-600' : ($role->name === 'pemel' ? 'bg-orange-100 text-orange-600' : (in_array($role->name, ['seksi_pompa', 'pompa']) ? 'bg-cyan-100 text-cyan-700' : 'bg-emerald-100 text-emerald-600')))) }}">
                                    {{ $displayRoleName }}
                                </span>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button wire:click="editUser({{ $u->id }})" class="p-2 text-gray-400 hover:text-accent transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button wire:click="deleteUser({{ $u->id }})" wire:confirm="Hapus user ini? Tindakan ini tidak bisa dibatalkan." class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Create/Edit --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
        <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up max-h-[90vh] overflow-y-auto">
            <h2 class="text-xl font-bold text-gray-900 mb-2">{{ $editingUser ? 'Edit User' : 'Tambah User Baru' }}</h2>
            <p class="text-xs text-gray-500 mb-4">Berikan akses masuk ke sistem inventory.</p>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-2xl text-xs font-semibold space-y-1 mb-4">
                    <div class="font-bold text-red-700">Mohon periksa kesalahan berikut:</div>
                    @foreach ($errors->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
            @endif
            
            <form wire:submit="saveUser" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5 ml-1">Nama Lengkap</label>
                    <input type="text" wire:model="name" placeholder="John Doe" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border focus:ring-2 focus:ring-accent/20 outline-none {{ $errors->has('name') ? 'border-red-300' : 'border-transparent' }}">
                    @error('name') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5 ml-1">NIP / NRK</label>
                    <input type="text" wire:model="nip" placeholder="Contoh: 198001012005011001" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border focus:ring-2 focus:ring-accent/20 outline-none {{ $errors->has('nip') ? 'border-red-300' : 'border-transparent' }}">
                    @error('nip') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5 ml-1">Email</label>
                    <input type="email" wire:model="email" placeholder="john@example.com" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border focus:ring-2 focus:ring-accent/20 outline-none {{ $errors->has('email') ? 'border-red-300' : 'border-transparent' }}">
                    @error('email') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5 ml-1">Password {{ $editingUser ? '(Kosongkan jika tidak diubah)' : '(Minimal 6 karakter)' }}</label>
                    <input type="password" wire:model="password" placeholder="••••••••" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border focus:ring-2 focus:ring-accent/20 outline-none {{ $errors->has('password') ? 'border-red-300' : 'border-transparent' }}">
                    @error('password') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5 ml-1">Role / Peran</label>
                    <div class="grid grid-cols-2 gap-2.5">
                        @foreach($this->roles as $role)
                            @php
                                $displayRoles = [
                                    'superadmin' => 'Pengurus Barang',
                                    'kecamatan_admin' => 'Kasubag',
                                    'sudin' => 'Kasudin',
                                    'pemel' => 'Pemeliharaan',
                                    'seksi_pompa' => 'Seksi Pompa',
                                    'pompa' => 'Seksi Pompa'
                                ];
                                $displayRoleName = $displayRoles[$role->name] ?? $role->name;
                                $isSelected = ($selected_role === $role->name);
                            @endphp
                        <button 
                            type="button" 
                            wire:click="$set('selected_role', '{{ $role->name }}')" 
                            class="relative flex items-center justify-center p-3 rounded-2xl cursor-pointer transition-all border-2 text-center {{ $isSelected ? 'border-accent bg-accent/10 shadow-sm' : 'border-gray-200/80 bg-base hover:bg-accent/5' }}">
                            <span class="text-xs font-bold uppercase tracking-wider {{ $isSelected ? 'text-accent font-extrabold' : 'text-gray-500' }}">
                                {{ $displayRoleName }}
                            </span>
                        </button>
                        @endforeach
                    </div>
                    @error('selected_role') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-500 py-3 rounded-2xl font-bold text-sm transition-colors">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" class="flex-1 bg-accent hover:bg-accent-dark text-white py-3 rounded-2xl font-bold text-sm shadow-lg shadow-accent/20 flex items-center justify-center gap-2 disabled:opacity-50 transition-all">
                        <span wire:loading.remove wire:target="saveUser">Simpan User</span>
                        <span wire:loading wire:target="saveUser" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>