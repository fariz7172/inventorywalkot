<?php

use App\Models\DeliveryOrder;
use App\Exports\DeliveryOrderExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\WithPagination;
use Livewire\Volt\Component;

?>

<div>
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Daftar Surat Jalan</h1>
            <p class="text-sm text-gray-500 mt-0.5">Kelola permintaan pengiriman barang ke gudang.</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="$set('showImportModal', true)" class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-emerald-100 transition-all shadow-sm">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Import Excel
            </button>
            <button wire:click="exportExcel" class="flex items-center gap-2 bg-white border border-warm text-gray-700 px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-base transition-all shadow-sm">
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Excel
            </button>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (\Illuminate\Support\Facades\Blade::check('hasanyrole', 'superadmin|sudin|kecamatan_admin|pemel|kepala_gudang|gudang')): ?>
            <a href="/dashboard/surat-jalan/create" wire:navigate class="flex items-center gap-2 bg-accent text-white px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-accent-dark transition-all shadow-md shadow-accent/30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Surat Jalan
            </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-4 mb-5 flex flex-col gap-4">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('message')): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-4 text-sm font-bold">
                <?php echo e(session('message')); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session()->has('error')): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-4 text-sm font-bold">
                <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="flex flex-col sm:flex-row gap-3">
            <select wire:model.live="status" class="bg-base rounded-xl px-3 py-2 text-sm text-gray-600 outline-none border border-warm/60 focus:ring-accent/30 transition-all">
                <option value="">Semua Status</option>
                <option value="draft">Draft (Ordered)</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped (Sent)</option>
                <option value="rejected">Ditolak (Rejected)</option>
            </select>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3 pt-3 border-t border-gray-100">
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest whitespace-nowrap">Periode:</label>
                <input type="date" wire:model.live="startDate" class="bg-base rounded-xl px-3 py-2 text-sm text-gray-600 border border-warm/60 focus:ring-accent/30 outline-none w-full sm:w-44">
            </div>
            <span class="text-gray-400 hidden sm:block font-bold text-xs">s/d</span>
            <div class="w-full sm:w-auto">
                <input type="date" wire:model.live="endDate" class="bg-base rounded-xl px-3 py-2 text-sm text-gray-600 border border-warm/60 focus:ring-accent/30 outline-none w-full sm:w-44">
            </div>
            <button wire:click="$set('startDate', ''); $set('endDate', '')" class="text-[10px] font-bold text-red-500 hover:text-red-600 uppercase tracking-widest px-2 transition-colors ml-auto sm:ml-0">Reset Filter</button>
        </div>
    </div>

    
    <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-warm/40 border-b border-warm/60">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">No. Surat Jalan</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tujuan / Lokasi</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pemohon</th>
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-warm/40">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $deliveryOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-base/60 transition-colors">
                        <td class="px-5 py-4">
                            <p class="font-bold text-accent font-mono text-xs"><?php echo e($order->surat_jalan_no); ?></p>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->user): ?>
                                <span class="text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded mt-1 inline-block">
                                    Diinput oleh: <span class="font-semibold"><?php echo e($order->user->name); ?></span>
                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e(\Carbon\Carbon::parse($order->tanggal)->locale('id')->isoFormat('D MMMM Y, HH:mm')); ?></td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-800 text-xs"><?php echo e($order->lokasi); ?></p>
                            <p class="text-[10px] text-gray-400"><?php echo e($order->pelaksana_kecamatan); ?></p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 font-medium"><?php echo e($order->pemohon); ?></td>
                        <td class="px-5 py-4">
                            <?php
                                $statusClasses = [
                                    'draft' => 'bg-amber-50 text-amber-600',
                                    'processing' => 'bg-blue-50 text-blue-600',
                                    'shipped' => 'bg-emerald-50 text-emerald-600',
                                    'rejected' => 'bg-red-50 text-red-600',
                                ];
                            ?>
                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg <?php echo e($statusClasses[$order->status] ?? 'bg-gray-50 text-gray-600'); ?> block w-max">
                                <?php echo e($order->status === 'rejected' ? 'Ditolak' : ucfirst($order->status)); ?>

                            </span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($order->status === 'draft'): ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($order->spb_document)): ?>
                                    <span class="text-[9px] font-black text-red-500 mt-1.5 block leading-tight">
                                        (Segera Upload Form SPB)
                                    </span>
                                <?php else: ?>
                                    <span class="text-[9px] font-black text-emerald-500 mt-1.5 block leading-tight">
                                        (Menunggu Konfirmasi Gudang)
                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <a href="/dashboard/surat-jalan/<?php echo e($order->id); ?>" wire:navigate class="w-8 h-8 rounded-lg hover:bg-accent/10 flex items-center justify-center transition-colors group" title="Lihat Detail">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <?php
                                    $canEdit = auth()->user()->hasRole('superadmin') || auth()->user()->hasRole('sudin') || 
                                              ((auth()->user()->hasRole('pemel') || auth()->user()->hasRole('kecamatan_admin')) && in_array($order->status, ['draft', 'rejected']));
                                ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canEdit): ?>
                                <a href="/dashboard/surat-jalan/<?php echo e($order->id); ?>/edit" wire:navigate class="w-8 h-8 rounded-lg hover:bg-amber-50 flex items-center justify-center transition-colors group" title="Edit Data">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(in_array($order->status, ['draft', 'rejected'])): ?>
                                <button wire:click="deleteOrder(<?php echo e($order->id); ?>)" wire:confirm="Yakin ingin menghapus data ini?" class="w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center transition-colors group">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-gray-400 italic">Belum ada data surat jalan.</td>
                    </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-warm/60 bg-warm/20">
            <?php echo e($deliveryOrders->links()); ?>

        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showImportModal): ?>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="$set('showImportModal', false)"></div>
        <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900 leading-tight">Import Surat Jalan</h2>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Gunakan file .xlsx atau .csv</p>
                    <a href="/templates/template_surat_jalan.xlsx" download class="text-[10px] text-emerald-600 font-black uppercase tracking-widest hover:underline mt-2 inline-block">
                        📥 Download Template Excel
                    </a>
                </div>
            </div>

            <form wire:submit="importSuratJalan" class="space-y-6">
                <div class="bg-base rounded-[1.5rem] p-6 border-2 border-dashed border-warm/60 group hover:border-emerald-500/50 transition-all relative">
                    <input type="file" wire:model="importFile" id="import-file-sj" class="hidden" accept=".xlsx,.xls,.csv">
                    <label for="import-file-sj" class="flex flex-col items-center justify-center cursor-pointer">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($importFile): ?>
                            <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center text-white mb-3 shadow-lg shadow-emerald-500/20">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-gray-700 mb-1"><?php echo e($importFile->getClientOriginalName()); ?></p>
                            <p class="text-[10px] text-gray-400 font-medium italic uppercase">Klik untuk ganti file</p>
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-500 mb-3 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-gray-700 mb-1">Pilih File Excel</p>
                            <p class="text-[10px] text-gray-400 font-medium uppercase tracking-tighter">Satu nomor SJ bisa banyak baris barang</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </label>
                    <div wire:loading wire:target="importFile" class="absolute inset-0 bg-white/80 backdrop-blur-[1px] flex items-center justify-center rounded-[1.5rem]">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                            <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce [animation-delay:0.4s]"></div>
                        </div>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['importFile'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-[10px] font-bold uppercase mt-2 ml-2"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="flex flex-col gap-3">
                    <button type="submit" wire:loading.attr="disabled" class="w-full bg-emerald-500 text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-emerald-500/20 hover:bg-emerald-600 transition-all flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="importSuratJalan">Mulai Import Sekarang</span>
                        <span wire:loading wire:target="importSuratJalan" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sedang Memproses...
                        </span>
                    </button>
                    <button type="button" wire:click="$set('showImportModal', false)" class="w-full bg-base text-gray-500 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-warm/40 transition-all">
                        Batalkan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/surat-jalan/index.blade.php ENDPATH**/ ?>