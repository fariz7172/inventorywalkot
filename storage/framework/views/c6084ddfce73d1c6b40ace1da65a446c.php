<?php

use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Material;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Stock Opname</h1>
            <p class="text-sm text-gray-500 mt-1">Pencocokan fisik barang dan persetujuan penyesuaian saldo.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?php echo e(route('laporan.stock-opname')); ?>" class="bg-white border border-gray-300 text-gray-700 px-5 py-2.5 rounded-xl font-bold transition-all shadow-sm hover:bg-gray-50 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Lihat Laporan Rekap
            </a>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasRole('gudang')): ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasPendingOpname): ?>
                    <div class="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-xl text-sm font-bold border border-yellow-200">
                        Selesaikan Opname yang masih Pending
                    </div>
                <?php else: ?>
                    <button wire:click="openCreate" class="bg-accent hover:bg-accent-light text-white px-5 py-2.5 rounded-xl font-bold transition-all shadow-md flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Buat Opname
                    </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('error')): ?>
        <div class="mb-4 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline"><?php echo e(session('error')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="flex justify-between items-end mb-4">
        <div class="w-64">
            <label class="block text-sm font-semibold text-gray-700 mb-1">Filter Periode</label>
            <div class="relative">
                <select wire:model.live="filterPeriod" wire:change="loadData" class="w-full appearance-none bg-white border border-gray-300 text-gray-700 py-2.5 px-4 pr-8 rounded-xl focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent shadow-sm transition-all cursor-pointer font-medium">
                    <option value="all">Menampilkan Semua Data</option>
                    <option value="this_week">Minggu Ini</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="this_year">Tahun Ini</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Table List Opname -->
    <div class="bg-white rounded-2xl shadow-card overflow-hidden ring-1 ring-accent/5">
        <table class="w-full text-sm text-left">
            <thead class="bg-warm/30 text-gray-600 font-semibold border-b border-warm/60">
                <tr>
                    <th class="px-6 py-4">Tgl Opname</th>
                    <th class="px-6 py-4">Dibuat Oleh</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-warm/30">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $opnames; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr class="hover:bg-base/50 transition-colors">
                    <td class="px-6 py-4 font-medium text-gray-900"><?php echo e(\Carbon\Carbon::parse($op->opname_date)->format('d M Y')); ?></td>
                    <td class="px-6 py-4"><?php echo e($op->user->name); ?></td>
                    <td class="px-6 py-4">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($op->status === 'pending'): ?>
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Menunggu ACC</span>
                        <?php elseif($op->status === 'approved'): ?>
                            <span class="bg-emerald-100 text-emerald-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Disetujui</span>
                        <?php else: ?>
                            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-bold uppercase">Ditolak</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <button wire:click="openDetail(<?php echo e($op->id); ?>)" class="text-accent hover:text-accent-dark font-semibold">Lihat Detail</button>
                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($op->status === 'pending' && $op->user_id === auth()->id()): ?>
                                <span class="text-gray-300">|</span>
                                <button wire:click="editOpname(<?php echo e($op->id); ?>)" class="text-blue-600 hover:text-blue-800 font-semibold">Edit</button>
                                <button wire:click="deleteOpname(<?php echo e($op->id); ?>)" class="text-red-600 hover:text-red-800 font-semibold" onclick="return confirm('Yakin ingin membatalkan opname ini?')">Batalkan</button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">Belum ada riwayat Stock Opname.</td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- CREATE MODAL (Hanya Gudang) -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showCreateModal): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-4xl max-h-[90vh] flex flex-col rounded-3xl shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-warm/30">
                <h3 class="text-xl font-bold text-gray-800"><?php echo e($isEditing ? 'Edit Stock Opname' : 'Buat Laporan Stock Opname'); ?></h3>
                <button wire:click="$set('showCreateModal', false)" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Opname</label>
                        <input type="date" wire:model="opname_date" class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-accent/50 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan</label>
                        <input type="text" wire:model="notes" placeholder="Cth: Opname akhir bulan April..." class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-accent/50 outline-none">
                    </div>
                </div>

                <div class="border rounded-xl overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 font-semibold border-b">
                            <tr>
                                <th class="px-4 py-3">Barang / Material</th>
                                <th class="px-4 py-3 text-center">Stok Sistem</th>
                                <th class="px-4 py-3 text-center w-32">Stok Fisik</th>
                                <th class="px-4 py-3">Keterangan Selisih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900"><?php echo e($m->name); ?> <span class="text-xs text-gray-500">(<?php echo e($m->unit); ?>)</span></td>
                                <td class="px-4 py-3 text-center font-bold text-gray-500"><?php echo e((float)$m->current_volume); ?></td>
                                <td class="px-4 py-2">
                                    <input type="number" step="0.01" wire:model="opnameItems.<?php echo e($m->id); ?>" class="w-full text-center rounded-lg border-gray-300 focus:ring-accent focus:border-accent">
                                </td>
                                <td class="px-4 py-2">
                                    <input type="text" wire:model="itemNotes.<?php echo e($m->id); ?>" placeholder="Opsional (rusak, hilang...)" class="w-full text-sm rounded-lg border-gray-300 focus:ring-accent focus:border-accent">
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
                <button wire:click="$set('showCreateModal', false)" class="px-5 py-2.5 rounded-xl font-bold text-gray-600 hover:bg-gray-200 transition-colors">Batal</button>
                <button wire:click="saveOpname(false)" class="px-5 py-2.5 rounded-xl font-bold bg-accent hover:bg-accent-light text-white transition-colors"><?php echo e($isEditing ? 'Simpan Perubahan' : 'Kirim Ajuan Opname'); ?></button>
            </div>
        </div>
    </div>

    <!-- Peringatan Selisih Stok (Difference Confirmation) -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($needsDifferenceConfirmation): ?>
    <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-md flex flex-col rounded-3xl shadow-2xl overflow-hidden p-6 text-center animate-fade-in-up">
            <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Konfirmasi Selisih Stok</h3>
            <p class="text-gray-600 mb-6 text-sm">
                Anda memasukkan angka Stok Fisik yang <b>BERBEDA</b> dari Stok Sistem (ada kelebihan atau kekurangan) pada beberapa barang.<br><br>
                Apakah Anda sudah mengecek seluruh area gudang secara teliti dan yakin angka ini bukan salah ketik (typo)?
            </p>
            <div class="flex gap-3 justify-center">
                <button wire:click="$set('needsDifferenceConfirmation', false)" class="px-5 py-2.5 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors flex-1">Cek Kembali</button>
                <button wire:click="saveOpname(true)" class="px-5 py-2.5 rounded-xl font-bold bg-yellow-500 hover:bg-yellow-600 text-white transition-colors shadow-md flex-1">Ya, Sudah Benar</button>
            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- DETAIL MODAL -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showDetailModal && $selectedOpname): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-5xl max-h-[90vh] flex flex-col rounded-3xl shadow-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-warm/30">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">Detail Stock Opname</h3>
                    <p class="text-xs text-gray-500">Dibuat oleh <?php echo e($selectedOpname->user->name); ?> pada <?php echo e(\Carbon\Carbon::parse($selectedOpname->opname_date)->format('d M Y')); ?></p>
                </div>
                <button wire:click="closeDetail" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedOpname->notes): ?>
                    <div class="mb-4 p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <span class="font-bold text-sm text-gray-700">Catatan:</span> <?php echo e($selectedOpname->notes); ?>

                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                
                <div class="border rounded-xl overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 font-semibold border-b">
                            <tr>
                                <th class="px-4 py-3">Material</th>
                                <th class="px-4 py-3 text-center">Sistem</th>
                                <th class="px-4 py-3 text-center">Fisik</th>
                                <th class="px-4 py-3 text-center">Selisih</th>
                                <th class="px-4 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $selectedOpname->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="hover:bg-gray-50 <?php echo e($item->difference != 0 ? 'bg-orange-50/30' : ''); ?>">
                                <td class="px-4 py-3 font-medium text-gray-900"><?php echo e($item->material->name); ?> <span class="text-xs text-gray-500">(<?php echo e($item->material->unit); ?>)</span></td>
                                <td class="px-4 py-3 text-center font-bold text-gray-500"><?php echo e((float)$item->system_volume); ?></td>
                                <td class="px-4 py-3 text-center font-bold text-gray-800"><?php echo e((float)$item->physical_volume); ?></td>
                                <td class="px-4 py-3 text-center font-bold <?php echo e($item->difference > 0 ? 'text-emerald-600' : ($item->difference < 0 ? 'text-red-600' : 'text-gray-400')); ?>">
                                    <?php echo e($item->difference > 0 ? '+'.(float)$item->difference : ($item->difference == 0 ? '-' : (float)$item->difference)); ?>

                                </td>
                                <td class="px-4 py-3 text-gray-600 italic text-xs"><?php echo e($item->notes ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex flex-col gap-4">
                <!-- Jika ada Catatan dari Superadmin (History) -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedOpname->approver_notes): ?>
                    <div class="p-3 <?php echo e($selectedOpname->status === 'approved' ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200'); ?> border rounded-xl">
                        <span class="font-bold text-sm <?php echo e($selectedOpname->status === 'approved' ? 'text-emerald-700' : 'text-red-700'); ?>">Catatan Superadmin:</span>
                        <p class="text-sm text-gray-700 mt-1"><?php echo e($selectedOpname->approver_notes); ?></p>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <!-- Input Catatan Superadmin (Saat Pending) -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasRole('superadmin') && $selectedOpname->status === 'pending'): ?>
                    <div class="w-full">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Catatan Superadmin <span class="text-xs font-normal text-gray-500">(Wajib jika menolak)</span></label>
                        <textarea wire:model="approverNotes" rows="2" class="w-full rounded-xl border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-accent/50 outline-none" placeholder="Tuliskan alasan penolakan atau catatan tambahan persetujuan..."></textarea>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['approverNotes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs font-bold mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="flex justify-between items-center w-full">
                    <div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedOpname->status === 'approved'): ?>
                            <span class="text-sm font-bold text-emerald-600">Disetujui oleh <?php echo e($selectedOpname->approver->name ?? '-'); ?></span>
                        <?php elseif($selectedOpname->status === 'rejected'): ?>
                            <span class="text-sm font-bold text-red-600">Ditolak oleh <?php echo e($selectedOpname->approver->name ?? '-'); ?></span>
                        <?php else: ?>
                            <span class="text-sm font-bold text-yellow-600">Menunggu Persetujuan</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div class="flex gap-3">
                        <button onclick="printStockOpname()" class="px-5 py-2.5 rounded-xl font-bold bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Cetak
                        </button>
                        <button wire:click="closeDetail" class="px-5 py-2.5 rounded-xl font-bold text-gray-600 hover:bg-gray-200 transition-colors">Tutup</button>
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->hasRole('superadmin') && $selectedOpname->status === 'pending'): ?>
                            <button wire:click="rejectOpname(<?php echo e($selectedOpname->id); ?>)" class="px-5 py-2.5 rounded-xl font-bold bg-red-100 text-red-600 hover:bg-red-200 transition-colors">Tolak</button>
                            <button wire:click="approveOpname(<?php echo e($selectedOpname->id); ?>)" class="px-5 py-2.5 rounded-xl font-bold bg-emerald-500 text-white hover:bg-emerald-600 transition-colors shadow-md">Setujui & Sesuaikan Stok</button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        
        <div id="print-area-opname" class="hidden bg-white text-black" style="font-family: 'Times New Roman', serif; padding: 20px;">
            
            <img src="<?php echo e(asset('assets/kop.png')); ?>" style="width:100%; height:auto; margin-bottom: 20px;">

            <div style="text-align:center; margin-bottom: 16px;">
                <h1 style="font-size: 14pt; font-weight: bold; text-decoration: underline; text-transform: uppercase; line-height: 1.4; margin: 0;">
                    BERITA ACARA PEMERIKSAAN FISIK<br>
                    (BERITA ACARA STOCK OPNAME/BASO)
                </h1>
                <?php
                    $carbonDate = \Carbon\Carbon::parse($selectedOpname->opname_date);
                    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    $dayName = $days[$carbonDate->dayOfWeek];
                    $monthName = $months[$carbonDate->month];
                    $namaSuperAdmin = $selectedOpname->approver->name ?? '………………………………';
                    $namaGudang = $selectedOpname->user->name ?? '………………………………';
                ?>
                <p style="font-size: 11pt; font-weight: bold; margin-top: 4px;">Nomor: <?php echo e($selectedOpname->notes ?: '……………………………'); ?></p>
            </div>

            <div style="font-size: 11pt; text-align: justify; margin-bottom: 12px; line-height: 1.8;">
                <p>Pada Hari ini <b><?php echo e($dayName); ?></b> Tanggal <b><?php echo e($carbonDate->day); ?></b> Bulan <b><?php echo e($monthName); ?></b> Tahun <b><?php echo e($carbonDate->year); ?></b> yang bertanda tangan dibawah ini:</p>
                
                <table style="margin-left: 40px; border: none;">
                    <tr><td style="border:none; padding: 2px 8px 2px 0; width: 80px;">Nama</td><td style="border:none; padding: 2px 4px;">:</td><td style="border:none; padding: 2px 0;"><b><u><?php echo e($namaSuperAdmin); ?></u></b></td></tr>
                    <tr><td style="border:none; padding: 2px 8px 2px 0;">NIP</td><td style="border:none; padding: 2px 4px;">:</td><td style="border:none; padding: 2px 0;">……………………………</td></tr>
                </table>

                <p style="margin-top: 8px;">Sesuai Dengan Peraturan Dalam Negeri No 19 Tahun 2016 Tentang Pedoman Pengolahan Barang Milik Daerah, Kami Melakukan Pemeriksaan Setempat atas Sisa Barang Persediaan (stock Opname) Yang Dikelola Oleh :</p>
                
                <table style="margin-left: 40px; border: none;">
                    <tr><td style="border:none; padding: 2px 8px 2px 0; width: 80px;">Nama</td><td style="border:none; padding: 2px 4px;">:</td><td style="border:none; padding: 2px 0;"><b><u><?php echo e($namaGudang); ?></u></b></td></tr>
                    <tr><td style="border:none; padding: 2px 8px 2px 0;">NIP</td><td style="border:none; padding: 2px 4px;">:</td><td style="border:none; padding: 2px 0;">……………………………</td></tr>
                </table>

                <p style="margin-top: 8px;">Berdasarkan Keputusan Gurbernur ……… Nomor ……………… Tahun……….. Tanggal………. Ditugaskan Untuk Mengurus Barang, Berdasarkan Hasil Pemeriksaan Fisik Barang (Stok Opname), Kami Mendapatkan Hasil Sebagai Berikut:</p>
            </div>

            <table style="width:100%; border-collapse: collapse; font-size: 10pt; margin-bottom: 16px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid black; padding: 6px 4px; text-align: center; width: 30px;">No</th>
                        <th style="border: 1px solid black; padding: 6px 8px; text-align: left;">Uraian Nama Barang</th>
                        <th style="border: 1px solid black; padding: 6px 4px; text-align: center;">Satuan</th>
                        <th style="border: 1px solid black; padding: 6px 4px; text-align: center;">Volume<br>(Fisik)</th>
                        <th style="border: 1px solid black; padding: 6px 4px; text-align: center;">Jumlah</th>
                        <th style="border: 1px solid black; padding: 6px 8px; text-align: left;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $selectedOpname->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td style="border: 1px solid black; padding: 5px 4px; text-align: center;"><?php echo e($index + 1); ?></td>
                        <td style="border: 1px solid black; padding: 5px 8px; font-weight: bold; text-transform: uppercase;"><?php echo e($item->material->name); ?></td>
                        <td style="border: 1px solid black; padding: 5px 4px; text-align: center; text-transform: uppercase;"><?php echo e($item->material->unit); ?></td>
                        <td style="border: 1px solid black; padding: 5px 4px; text-align: center; font-weight: bold;"><?php echo e((float)$item->physical_volume); ?></td>
                        <td style="border: 1px solid black; padding: 5px 4px; text-align: center; font-weight: bold;"><?php echo e((float)$item->physical_volume); ?></td>
                        <td style="border: 1px solid black; padding: 5px 8px; font-style: italic;"><?php echo e($item->notes ?: '-'); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <tr>
                        <td colspan="6" style="border: 1px solid black; padding: 5px 8px; font-weight: bold;">Jumlah</td>
                    </tr>
                </tbody>
            </table>

            <p style="font-size: 11pt; margin-bottom: 40px; text-align: justify;">Demikian Berita Acara Stock Opname ini dibuat dalam rangkap 2 (dua) untuk digunakan sebagaimana mestinya.</p>

            <table style="width: 100%; border: none; font-size: 11pt; text-align: center;">
                <tr>
                    <td style="border: none; width: 50%; vertical-align: top;">
                        <p style="margin: 0;">Jakarta, <?php echo e($carbonDate->day); ?> <?php echo e($monthName); ?> <?php echo e($carbonDate->year); ?></p>
                        <p style="margin: 4px 0; font-weight: bold;">Yang Memeriksa Barang,</p>
                        <p style="margin: 0; font-weight: bold; font-size: 9pt; text-transform: uppercase;">(Super Admin)</p>
                        <div style="height: 80px;"></div>
                        <p style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;"><?php echo e($namaSuperAdmin); ?></p>
                        <p style="margin: 0; font-size: 9pt;">NIP: ……………………………</p>
                    </td>
                    <td style="border: none; width: 50%; vertical-align: top;">
                        <p style="margin: 0; visibility: hidden;">Jakarta, ...</p>
                        <p style="margin: 4px 0; font-weight: bold;">Pengurus Barang/Pengurus Barang Pembantu,</p>
                        <p style="margin: 0; font-weight: bold; font-size: 9pt; text-transform: uppercase;">(Gudang)</p>
                        <div style="height: 80px;"></div>
                        <p style="margin: 0; font-weight: bold; text-decoration: underline; text-transform: uppercase;"><?php echo e($namaGudang); ?></p>
                        <p style="margin: 0; font-size: 9pt;">NIP: ……………………………</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <script src="<?php echo e(asset('js/print-stock-opname.js')); ?>"></script>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/stock-opname/index.blade.php ENDPATH**/ ?>