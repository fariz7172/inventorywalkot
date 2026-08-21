<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;

?>

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Pengaturan Sistem</h1>
        <p class="text-sm text-gray-500 mt-1">Kelola informasi perusahaan dan preferensi global aplikasi.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Profil Perusahaan -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-6">
                <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-100">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center text-accent">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Profil Perusahaan</h2>
                        <p class="text-xs text-gray-500">Informasi ini akan muncul pada Kop Surat dan Form Login.</p>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('success_profile')): ?>
                    <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-sm font-bold"><?php echo e(session('success_profile')); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <form wire:submit="saveProfile" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Nama Aplikasi / Perusahaan -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1.5">Nama Perusahaan / Aplikasi <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="app_name" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-accent focus:border-accent block px-4 py-2.5 transition-all" required placeholder="Contoh: PT. Maju Bersama">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['app_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <!-- Deskripsi Pendek -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1.5">Deskripsi Singkat / Slogan</label>
                            <input type="text" wire:model="app_description" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-accent focus:border-accent block px-4 py-2.5 transition-all" placeholder="Contoh: Inventory Management System">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['app_description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    <!-- Logo Perusahaan -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Logo Perusahaan</label>
                        <div class="flex items-start gap-4">
                            <div class="w-20 h-20 rounded-xl border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden flex-shrink-0 shadow-inner">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($new_logo): ?>
                                    <img src="<?php echo e($new_logo->temporaryUrl()); ?>" class="w-full h-full object-cover rounded-xl">
                                <?php elseif($app_logo): ?>
                                    <img src="<?php echo e($app_logo); ?>" class="w-full h-full object-cover rounded-xl">
                                <?php else: ?>
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <input type="file" wire:model="new_logo" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 transition-all cursor-pointer">
                                <p class="text-[10px] text-gray-400 mt-2 font-medium">Format: PNG, JPG, JPEG. Ukuran Maksimal: 2MB.</p>
                                <div wire:loading wire:target="new_logo" class="text-xs text-accent mt-1 font-bold">Mengunggah logo...</div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['new_logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Kontak -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">No. Telp / Kontak</label>
                        <input type="text" wire:model="app_contact" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-accent focus:border-accent block px-4 py-2.5 transition-all" placeholder="Contoh: (021) 1234567">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['app_contact'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <!-- Alamat -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Alamat Perusahaan</label>
                        <textarea wire:model="app_address" rows="3" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-accent focus:border-accent block px-4 py-2.5 transition-all" placeholder="Alamat lengkap perusahaan..."></textarea>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['app_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="bg-accent hover:bg-accent-light text-white px-6 py-2.5 rounded-xl font-bold transition-all shadow-md flex items-center gap-2">
                            <span wire:loading.remove wire:target="saveProfile">Simpan Profil</span>
                            <span wire:loading wire:target="saveProfile">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kolom Kanan: Info Preferensi -->
        <div class="space-y-6">
            <div class="bg-gradient-to-br from-accent to-accent-light rounded-2xl shadow-card p-6 text-white relative overflow-hidden">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
                <div class="absolute -left-6 -bottom-6 w-32 h-32 bg-white/10 rounded-full blur-xl"></div>
                
                <h3 class="text-lg font-bold mb-2 relative z-10">Preferensi Sistem Lainnya</h3>
                <p class="text-sm text-white/80 mb-4 relative z-10 leading-relaxed">Tarik (backup) database terbaru dari server ke direktori lokal Anda untuk pengujian atau pencadangan.</p>
                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('success_backup')): ?>
                    <div class="mb-4 bg-emerald-500/20 border border-emerald-400/50 text-white px-4 py-3 rounded-xl flex items-start gap-2 relative z-10 text-sm">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><?php echo e(session('success_backup')); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('error_backup')): ?>
                    <div class="mb-4 bg-red-500/20 border border-red-400/50 text-white px-4 py-3 rounded-xl flex items-start gap-2 relative z-10 text-sm">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><?php echo e(session('error_backup')); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <button wire:click="runBackup" wire:loading.attr="disabled" class="relative z-10 w-full bg-white text-accent hover:bg-gray-50 font-bold py-3 px-4 rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                    <svg wire:loading.remove wire:target="runBackup" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <svg wire:loading wire:target="runBackup" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="runBackup">Mulai Backup Database</span>
                    <span wire:loading wire:target="runBackup">Memproses Backup...</span>
                </button>
            </div>
        </div>
    </div>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/settings.blade.php ENDPATH**/ ?>