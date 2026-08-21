<?php

use App\Models\DeliveryOrder;
use App\Models\Material;
use App\Models\Category;
use App\Models\Rab;

?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Buat Surat Jalan Baru</h1>
            <p class="text-sm text-gray-500">Isi data pengiriman barang ke lokasi tujuan.</p>
        </div>
        <a href="/dashboard/surat-jalan" wire:navigate class="text-sm font-semibold text-gray-500 hover:text-accent flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
            <h2 class="text-sm font-bold text-accent uppercase tracking-wider mb-4 border-b border-warm/60 pb-2">1. Informasi Pengiriman</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">No. Surat Jalan</label>
                    <div class="w-full bg-gray-50 rounded-xl px-4 py-2.5 text-sm text-gray-600 font-mono font-bold border border-warm/60 cursor-not-allowed flex items-center justify-between">
                        <span><?php echo e($this->nextSuratJalanNo); ?> <span class="text-[10px] font-normal text-gray-400 ml-1">(Preview)</span></span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tanggal</label>
                    <input type="datetime-local" wire:model="tanggal" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-accent/50 outline-none transition-all <?php $__errorArgs = ['tanggal'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['tanggal'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-gray-600">Lokasi Tujuan</label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" wire:model.live="is_manual_lokasi" class="rounded text-accent focus:ring-accent border-warm/60">
                            <span class="text-[10px] font-bold text-gray-500 uppercase">Input Manual</span>
                        </label>
                    </div>
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($is_manual_lokasi): ?>
                        <div class="bg-accent/5 border border-accent/20 rounded-xl p-4 mb-3">
                            <label class="block text-xs font-bold text-accent mb-2">Silahkan Upload(Photo Bukti) Surat Permintaan Barang NOTA DINAS</label>
                            
                            <input type="file" wire:model="nota_dinas_photo" accept="image/*" multiple class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 mb-2">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['nota_dinas_photo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['nota_dinas_photo.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <div wire:loading wire:target="nota_dinas_photo" class="text-xs text-accent font-bold">Uploading...</div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($nota_dinas_photo)): ?>
                                <div class="mt-3 flex flex-wrap gap-3">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $nota_dinas_photo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="relative inline-block group">
                                        <img src="<?php echo e($photo->temporaryUrl()); ?>" class="h-24 w-24 rounded-lg object-cover border border-warm/40">
                                        <button type="button" wire:click="removePhoto(<?php echo e($index); ?>)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1.5 shadow-md hover:bg-red-600 transition-all z-10" title="Hapus Foto">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($nota_dinas_photo): ?>
                            <textarea wire:model="lokasi" rows="2" placeholder="Masukkan detail lokasi manual..." class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all <?php $__errorArgs = ['lokasi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"></textarea>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php else: ?>
                        <div x-data="{
                                open: false,
                                search: '',
                                selected: <?php if ((object) ('lokasi') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('lokasi'->value()); ?>')<?php echo e('lokasi'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('lokasi'); ?>')<?php endif; ?>.live,
                                options: <?php echo e(json_encode($this->rabs->pluck('lokasi')->toArray())); ?>,
                                get filteredOptions() {
                                    if (this.search === '') {
                                        return this.options;
                                    }
                                    return this.options.filter(opt => opt.toLowerCase().includes(this.search.toLowerCase()));
                                }
                            }"
                            class="relative w-full"
                            @click.away="open = false"
                        >
                            <div @click="open = !open"
                                 class="w-full bg-base rounded-xl px-4 py-2.5 text-sm border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all cursor-pointer flex justify-between items-center <?php $__errorArgs = ['lokasi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <span x-text="selected ? selected : '-- Pilih Lokasi --'" :class="selected ? 'text-gray-700 font-bold' : 'text-gray-500'"></span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>

                            <div x-show="open" 
                                 x-transition.opacity
                                 style="display: none;"
                                 class="absolute z-50 w-full mt-1 bg-white border border-warm/60 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                                 
                                 <div x-show="options.length > 10" class="p-2 sticky top-0 bg-white border-b border-warm/30 shadow-sm">
                                     <input type="text" x-model="search" placeholder="Cari lokasi..." 
                                            class="w-full bg-gray-50 rounded-lg px-3 py-2 text-sm border border-warm/30 focus:outline-none focus:ring-1 focus:ring-accent"
                                            @click.stop>
                                 </div>

                                 <ul class="py-1">
                                     <template x-for="option in filteredOptions" :key="option">
                                         <li @click="selected = option; open = false; search = ''"
                                             class="px-4 py-2 text-sm text-gray-700 hover:bg-accent hover:text-white cursor-pointer transition-colors"
                                             x-text="option">
                                         </li>
                                     </template>
                                     <li x-show="filteredOptions.length === 0" class="px-4 py-2 text-sm text-gray-400 italic">
                                         Lokasi tidak ditemukan...
                                     </li>
                                 </ul>
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['lokasi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$is_manual_lokasi): ?>
                        <div class="bg-accent/5 border border-accent/20 rounded-xl p-4 mt-3 mb-3">
                            <label class="block text-xs font-bold text-accent mb-2">Silahkan Upload Photo Progress Yang Sudah Dikerjakan (Minimal 3 Foto)</label>
                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($progress_photo)): ?>
                                <input type="file" wire:model="progress_photo" multiple accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 mb-2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['progress_photo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['progress_photo.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div wire:loading wire:target="progress_photo" class="text-xs text-accent font-bold">Uploading...</div>
                            <?php else: ?>
                                <div class="mt-2 flex flex-wrap gap-3">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $progress_photo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $photo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="relative inline-block group">
                                        <img src="<?php echo e($photo->temporaryUrl()); ?>" class="h-24 w-24 rounded-lg object-cover border border-warm/40">
                                        <button type="button" wire:click="removeProgressPhoto(<?php echo e($index); ?>)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1.5 shadow-md hover:bg-red-600 transition-all z-10" title="Hapus Foto">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-[10px] font-bold text-gray-400 mb-1">Pilih Ulang Foto (Ganti Semua Foto)</label>
                                    <input type="file" wire:model="progress_photo" multiple accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-[10px] file:font-semibold file:bg-gray-100 file:text-gray-600 hover:file:bg-gray-200">
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['progress_photo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['progress_photo.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <div wire:loading wire:target="progress_photo" class="text-xs text-accent font-bold mt-2">Uploading...</div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Kecamatan / Pelaksana</label>
                    <input type="text" wire:model="pelaksana_kecamatan" placeholder="Contoh: Kec. Cilincing" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Pemohon</label>
                    <input type="text" wire:model="pemohon" placeholder="Nama Pemohon" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all <?php $__errorArgs = ['pemohon'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['pemohon'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Petugas (Admin)</label>
                    <input type="text" wire:model="petugas" placeholder="Nama Petugas" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all <?php $__errorArgs = ['petugas'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['petugas'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Penerima Barang</label>
                    <input type="text" wire:model="penerima" placeholder="Nama Penerima" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all <?php $__errorArgs = ['penerima'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['penerima'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[10px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">No. Polisi Kendaraan</label>
                    <input type="text" wire:model="no_polisi" placeholder="Contoh: B 1234 ABC" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Keterangan Tambahan</label>
                    <input type="text" wire:model="keterangan" placeholder="..." class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 border border-warm/60 focus:ring-2 focus:ring-accent/30 outline-none transition-all">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
            <div class="flex items-center justify-between mb-4 border-b border-warm/60 pb-2">
                <h2 class="text-sm font-bold text-accent uppercase tracking-wider">2. Daftar Material</h2>
                <button type="button" wire:click="addMaterial" class="text-xs font-bold text-accent bg-accent/10 px-3 py-1.5 rounded-lg hover:bg-accent hover:text-white transition-all flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Baris
                </button>
            </div>

            <div class="space-y-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $selected_materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="flex flex-col sm:flex-row gap-3 bg-base/40 p-3 rounded-xl border border-warm/40 sm:items-end">
                    <div class="flex-1 w-full min-w-0">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Pilih Material</label>
                        
                        <?php
                            $materialOptions = [];
                            foreach($this->allMaterials as $m) {
                                $sisaRABText = ($lokasi && !$is_manual_lokasi) ? (isset($this->remainingQuotas[$m->id]) ? $this->remainingQuotas[$m->id] : 0) : '?';
                                $label = $m->name . ' (Stok: ' . (float)$m->current_volume . ' | RAB Sisa: ' . $sisaRABText . ')';
                                $materialOptions[] = ['id' => $m->id, 'label' => $label];
                            }
                        ?>
                        
                        <div x-data="{
                                open: false,
                                search: '',
                                selectedId: <?php if ((object) ('selected_materials.' . $index . '.material_id') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('selected_materials.' . $index . '.material_id'->value()); ?>')<?php echo e('selected_materials.' . $index . '.material_id'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('selected_materials.' . $index . '.material_id'); ?>')<?php endif; ?>.live,
                                options: <?php echo e(json_encode($materialOptions)); ?>,
                                get filteredOptions() {
                                    if (this.search === '') return this.options;
                                    return this.options.filter(opt => opt.label.toLowerCase().includes(this.search.toLowerCase()));
                                },
                                get selectedLabel() {
                                    const selectedOpt = this.options.find(opt => opt.id == this.selectedId);
                                    return selectedOpt ? selectedOpt.label : '-- Pilih Material --';
                                }
                            }"
                            class="relative w-full"
                            @click.away="open = false"
                        >
                            <div @click="open = !open"
                                 class="w-full bg-white rounded-lg px-3 py-2 text-xs border border-warm/60 focus:ring-1 focus:ring-accent outline-none cursor-pointer flex justify-between items-center gap-2 <?php $__errorArgs = ['selected_materials.'.$index.'.material_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <span x-text="selectedLabel" :class="selectedId ? 'text-gray-700 font-medium' : 'text-gray-500'" class="truncate flex-1 text-left block"></span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>

                            <div x-show="open" 
                                 x-transition.opacity
                                 style="display: none;"
                                 class="absolute z-50 w-full mt-1 bg-white border border-warm/60 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                                 
                                 <div x-show="options.length > 10" class="p-2 sticky top-0 bg-white border-b border-warm/30 shadow-sm z-10">
                                     <input type="text" x-model="search" placeholder="Cari material..." 
                                            class="w-full bg-gray-50 rounded-md px-3 py-1.5 text-xs border border-warm/30 focus:outline-none focus:ring-1 focus:ring-accent"
                                            @click.stop>
                                 </div>

                                 <ul class="py-1">
                                     <template x-for="option in filteredOptions" :key="option.id">
                                         <li @click="selectedId = option.id; open = false; search = ''"
                                             class="px-3 py-2 text-xs text-gray-700 hover:bg-accent hover:text-white cursor-pointer transition-colors"
                                             x-text="option.label">
                                         </li>
                                     </template>
                                     <li x-show="filteredOptions.length === 0" class="px-3 py-2 text-xs text-gray-400 italic">
                                         Material tidak ditemukan...
                                     </li>
                                 </ul>
                            </div>
                        </div>
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['selected_materials.'.$index.'.material_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-[9px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="w-full sm:w-32">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Jumlah Keluar</label>
                        <input type="number" step="any" wire:model="selected_materials.<?php echo e($index); ?>.requested_volume" class="w-full bg-white rounded-lg px-3 py-2 text-xs text-gray-700 border border-warm/60 focus:ring-1 focus:ring-accent outline-none <?php $__errorArgs = ['selected_materials.'.$index.'.requested_volume'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['selected_materials.'.$index.'.requested_volume'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-[9px] text-red-500 mt-1 font-bold"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($selected_materials) > 1): ?>
                    <button type="button" wire:click="removeMaterial(<?php echo e($index); ?>)" class="w-8 h-8 bg-red-50 text-red-500 rounded-lg flex items-center justify-center hover:bg-red-500 hover:text-white transition-all mb-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="bg-accent text-white px-10 py-3 rounded-2xl font-bold hover:bg-accent-dark transition-all shadow-xl shadow-accent/40 flex items-center gap-2">
                Simpan & Kirim ke Gudang
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7m0 0l-7 7m7-7H6"/>
                </svg>
            </button>
        </div>
    </form>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/surat-jalan/create.blade.php ENDPATH**/ ?>