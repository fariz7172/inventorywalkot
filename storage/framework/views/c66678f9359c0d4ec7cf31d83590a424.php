<?php

use App\Models\InventoryTransaction;
use Livewire\Volt\Component;

?>

<div
    x-data="{ open: <?php if ((object) ('isOpen') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('isOpen'->value()); ?>')<?php echo e('isOpen'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('isOpen'); ?>')<?php endif; ?> }"
    x-show="open"
    class="fixed inset-0 z-[999] overflow-y-auto"
    x-cloak
>
    
    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"
        wire:click="closeModal"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg ring-1 ring-black/5 overflow-hidden"
        >
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($trx): ?>
            
            <div class="bg-emerald-600 px-8 py-8 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="inline-block px-2 py-1 rounded bg-white/20 text-[10px] font-black uppercase tracking-widest mb-3">Detail Barang Masuk</span>
                        <h3 class="text-2xl font-black"><?php echo e($trx->reference_number ?: 'Tanpa Referensi'); ?></h3>
                        <p class="text-white/70 text-sm mt-1 font-bold"><?php echo e($trx->created_at->format('d F Y, H:i')); ?></p>
                    </div>
                    <button wire:click="closeModal" class="bg-white/10 hover:bg-white/20 p-2 rounded-xl transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            
            <div class="px-8 py-8 space-y-6">

                
                <div class="flex items-center gap-4 p-4 bg-base/40 rounded-2xl border border-warm/60">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Jenis Material</p>
                        <p class="text-base font-black text-gray-800"><?php echo e($trx->material->name); ?></p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase"><?php echo e($trx->material->category->name ?? '-'); ?></p>
                    </div>
                </div>

                
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-emerald-50 p-4 rounded-2xl text-center border border-emerald-100">
                        <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Masuk</p>
                        <p class="text-xl font-black text-emerald-600">+<?php echo e((float)$trx->volume_masuk); ?></p>
                        <p class="text-[10px] font-bold text-emerald-400 uppercase"><?php echo e($trx->material->unit); ?></p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-2xl text-center border border-red-100">
                        <p class="text-[10px] font-black text-red-400 uppercase tracking-widest mb-1">Keluar</p>
                        <p class="text-xl font-black text-red-500"><?php echo e($trx->volume_keluar > 0 ? '-'.(float)$trx->volume_keluar : '0'); ?></p>
                        <p class="text-[10px] font-bold text-red-300 uppercase"><?php echo e($trx->material->unit); ?></p>
                    </div>
                    <div class="bg-accent/5 p-4 rounded-2xl text-center border border-accent/10">
                        <p class="text-[10px] font-black text-accent uppercase tracking-widest mb-1">Saldo</p>
                        <p class="text-xl font-black text-accent"><?php echo e((float)$trx->balance_after); ?></p>
                        <p class="text-[10px] font-bold text-accent/50 uppercase"><?php echo e($trx->material->unit); ?></p>
                    </div>
                </div>

                
                <div class="space-y-3">
                    <div class="bg-base/40 p-4 rounded-2xl border border-warm/60">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Supplier / Sumber</p>
                        <p class="text-sm font-bold text-gray-800"><?php echo e($trx->supplier ?: '-'); ?></p>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($trx->note): ?>
                    <div class="bg-base/40 p-4 rounded-2xl border border-warm/60">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Catatan</p>
                        <p class="text-sm text-gray-700"><?php echo e($trx->note); ?></p>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <?php
                    $allImages = [];
                    if ($trx->image) {
                        $allImages = array_merge($allImages, explode(',', $trx->image));
                    }
                    if ($trx->deliveryOrder && $trx->deliveryOrder->nota_dinas_photo) {
                        $allImages[] = $trx->deliveryOrder->nota_dinas_photo;
                    }
                ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($allImages)): ?>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Bukti / Nota Dinas</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $allImages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <img src="<?php echo e(asset('storage/' . trim($img))); ?>" 
                                 @click="$dispatch('open-lightbox-modal', '<?php echo e(asset('storage/' . trim($img))); ?>')"
                                 alt="Bukti Transaksi" class="w-full rounded-2xl border border-warm/60 object-cover max-h-56 cursor-pointer hover:opacity-90 transition-opacity">
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <div class="pt-4 border-t border-gray-50 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center text-[10px] font-black text-accent uppercase">
                        <?php echo e(substr($trx->user->name ?? '?', 0, 1)); ?>

                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Dicatat Oleh</p>
                        <p class="text-xs font-bold text-gray-800"><?php echo e($trx->user->name ?? 'System'); ?></p>
                    </div>
                </div>
            </div>

            
            <div class="px-8 pb-8">
                <button wire:click="closeModal" class="w-full bg-base hover:bg-warm/60 text-gray-700 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all">
                    Tutup
                </button>
            </div>


<div x-data="{ open: false, src: '' }" 
     @open-lightbox-modal.window="src = $event.detail; open = true" 
     x-show="open" 
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
     style="display: none;">
    
    <button @click="open = false" class="absolute top-6 right-6 text-white/50 hover:text-white p-2 transition-colors">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    
    <img :src="src" @click.away="open = false" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl object-contain">
</div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/laporan/transaction-detail-modal.blade.php ENDPATH**/ ?>