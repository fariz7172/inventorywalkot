<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

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

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('message')): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold">
            <?php echo e(session('message')); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('error')): ?>
        <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="bg-white rounded-[2rem] shadow-card ring-1 ring-accent/5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-warm/40 border-b border-warm/60 text-gray-500 font-bold uppercase text-[11px]">
                        <th class="text-left px-6 py-4">Nama</th>
                        <th class="text-left px-6 py-4">Email</th>
                        <th class="text-center px-6 py-4">Role</th>
                        <th class="text-center px-6 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm/40">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="hover:bg-base/40 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-accent/10 rounded-full flex items-center justify-center text-accent font-bold text-xs uppercase">
                                    <?php echo e(substr($u->name, 0, 2)); ?>

                                </div>
                                <span class="font-bold text-gray-800"><?php echo e($u->name); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600"><?php echo e($u->email); ?></td>
                        <td class="px-6 py-4 text-center">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $u->roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $displayRoles = [
                                        'superadmin' => 'Pengurus Barang',
                                        'kecamatan_admin' => 'Kasubag',
                                        'sudin' => 'Kasudin'
                                    ];
                                    $displayRoleName = $displayRoles[$role->name] ?? $role->name;
                                ?>
                                <span class="inline-block px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider <?php echo e($role->name === 'superadmin' ? 'bg-indigo-100 text-indigo-600' : ($role->name === 'sudin' ? 'bg-purple-100 text-purple-600' : ($role->name === 'kepala_gudang' ? 'bg-blue-100 text-blue-600' : ($role->name === 'pemel' ? 'bg-orange-100 text-orange-600' : 'bg-emerald-100 text-emerald-600')))); ?>">
                                    <?php echo e($displayRoleName); ?>

                                </span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button wire:click="editUser(<?php echo e($u->id); ?>)" class="p-2 text-gray-400 hover:text-accent transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button wire:click="deleteUser(<?php echo e($u->id); ?>)" wire:confirm="Hapus user ini? Tindakan ini tidak bisa dibatalkan." class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showModal): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
        <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up">
            <h2 class="text-xl font-bold text-gray-900 mb-2"><?php echo e($editingUser ? 'Edit User' : 'Tambah User Baru'); ?></h2>
            <p class="text-xs text-gray-500 mb-6">Berikan akses masuk ke sistem inventory.</p>
            
            <form wire:submit="saveUser" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Nama Lengkap</label>
                    <input type="text" wire:model="name" placeholder="John Doe" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Email</label>
                    <input type="email" wire:model="email" placeholder="john@example.com" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Password <?php echo e($editingUser ? '(Kosongkan jika tidak diubah)' : ''); ?></label>
                    <input type="password" wire:model="password" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Role / Peran</label>
                    <div class="grid grid-cols-2 gap-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $displayRoles = [
                                    'superadmin' => 'Pengurus Barang',
                                    'kecamatan_admin' => 'Kasubag',
                                    'sudin' => 'Kasudin'
                                ];
                                $displayRoleName = $displayRoles[$role->name] ?? $role->name;
                            ?>
                        <label class="relative flex items-center justify-center p-3 rounded-2xl bg-base cursor-pointer hover:bg-accent/5 transition-all border-2 <?php echo e($selected_role === $role->name ? 'border-accent bg-accent/5' : 'border-transparent'); ?>">
                            <input type="radio" wire:model="selected_role" value="<?php echo e($role->name); ?>" class="hidden">
                            <span class="text-xs font-bold uppercase tracking-wider <?php echo e($selected_role === $role->name ? 'text-accent' : 'text-gray-400'); ?>">
                                <?php echo e($displayRoleName); ?>

                            </span>
                        </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-2xl font-bold text-sm">Batal</button>
                    <button type="submit" class="flex-1 bg-accent text-white py-3 rounded-2xl font-bold text-sm shadow-lg shadow-accent/20">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/user/index.blade.php ENDPATH**/ ?>