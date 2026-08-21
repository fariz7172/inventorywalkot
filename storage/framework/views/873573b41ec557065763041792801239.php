<?php

use App\Models\Rab;
use App\Models\DeliveryOrder;
use App\Models\Kecamatan;

?>

<div class="max-w-[100vw] overflow-hidden flex flex-col h-full">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan RAB</h1>
            <p class="text-sm text-gray-500">Rekap pengambilan material harian berbanding Stok RAB.</p>
        </div>
        
        <div class="flex flex-col md:flex-row md:flex-wrap items-center gap-3 bg-white p-2 rounded-2xl shadow-sm border border-gray-100">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!auth()->user()->hasRole('kecamatan_admin') || auth()->user()->hasRole('pemel')): ?>
            <select wire:model.live="selectedKecamatanId" class="bg-gray-50 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 outline-none border-none focus:ring-2 focus:ring-accent/20">
                <option value="">-- Semua Kecamatan --</option>
                <option value="null">-- Nota Dinas --</option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->kecamatans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($kec->id); ?>"><?php echo e($kec->nama_kecamatan); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </select>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php
                $rabOptions = [];
                foreach($this->rabs as $rab) {
                    $rabOptions[] = ['id' => $rab->id, 'label' => $rab->lokasi];
                }
            ?>
            <div wire:key="dropdown-rab-<?php echo e($selectedKecamatanId); ?>" x-data="{
                    open: false,
                    search: '',
                    selectedId: <?php if ((object) ('selectedRabId') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('selectedRabId'->value()); ?>')<?php echo e('selectedRabId'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('selectedRabId'); ?>')<?php endif; ?>.live,
                    options: <?php echo e(json_encode($rabOptions)); ?>,
                    get filteredOptions() {
                        if (this.search === '') return this.options;
                        return this.options.filter(opt => opt.label.toLowerCase().includes(this.search.toLowerCase()));
                    },
                    get selectedLabel() {
                        const selectedOpt = this.options.find(opt => opt.id == this.selectedId);
                        return selectedOpt ? selectedOpt.label : '-- Pilih Lokasi RAB --';
                    }
                }"
                class="relative w-full md:w-72"
                @click.away="open = false"
            >
                <div @click="open = !open"
                     class="w-full bg-gray-50 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 outline-none border-none focus:ring-2 focus:ring-accent/20 cursor-pointer flex justify-between items-center gap-2">
                    <span x-text="selectedLabel" class="truncate flex-1 text-left block"></span>
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </div>

                <div x-show="open" 
                     x-transition.opacity
                     style="display: none;"
                     class="absolute z-50 w-full md:w-[400px] mt-1 bg-white border border-warm/60 rounded-xl shadow-xl max-h-60 overflow-y-auto left-0 md:left-auto">
                     
                     <div x-show="options.length > 10" class="p-2 sticky top-0 bg-white border-b border-warm/30 shadow-sm z-10">
                         <input type="text" x-model="search" placeholder="Cari lokasi RAB..." 
                                class="w-full bg-gray-50 rounded-md px-3 py-2 text-sm border border-warm/30 focus:outline-none focus:ring-1 focus:ring-accent"
                                @click.stop>
                     </div>

                     <ul class="py-1">
                         <template x-for="option in filteredOptions" :key="option.id">
                             <li @click="selectedId = option.id; open = false; search = ''"
                                 class="px-4 py-2 text-sm text-gray-700 hover:bg-accent hover:text-white cursor-pointer transition-colors border-b border-gray-50 last:border-0"
                                 x-text="option.label">
                             </li>
                         </template>
                         <li x-show="filteredOptions.length === 0" class="px-4 py-2 text-sm text-gray-400 italic">
                             Lokasi tidak ditemukan...
                         </li>
                     </ul>
                </div>
            </div>
            
            <select wire:model.live="selectedMonth" class="bg-gray-50 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 outline-none border-none focus:ring-2 focus:ring-accent/20">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $months; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $num => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($num); ?>"><?php echo e($name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </select>
            
            <select wire:model.live="selectedYear" class="bg-gray-50 rounded-xl px-4 py-2 text-sm font-bold text-gray-700 outline-none border-none focus:ring-2 focus:ring-accent/20">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($year); ?>"><?php echo e($year); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </select>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$this->selectedRabId): ?>
    <div class="bg-white rounded-3xl shadow-card ring-1 ring-accent/5 p-12 text-center flex flex-col items-center justify-center min-h-[400px]">
        <div class="w-16 h-16 bg-accent/10 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Pilih Lokasi RAB</h3>
        <p class="text-sm text-gray-500 mt-2 max-w-sm">Silakan pilih lokasi RAB, bulan, dan tahun pada filter di atas untuk melihat laporan matriks pengambilan barang.</p>
    </div>
    <?php elseif($this->reportData && empty($this->reportData['rows'])): ?>
    <div class="bg-white rounded-3xl shadow-card ring-1 ring-accent/5 p-12 text-center flex flex-col items-center justify-center min-h-[400px]">
        <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Material Belum Diatur</h3>
        <p class="text-sm text-gray-500 mt-2 max-w-sm">Anda belum menambahkan pengaturan <strong>Stok Material RAB</strong> untuk lokasi ini. Silakan kelola di menu Data RAB terlebih dahulu.</p>
        <a href="/dashboard/rab" wire:navigate class="mt-4 text-xs font-bold bg-accent text-white px-4 py-2 rounded-xl hover:bg-accent-dark transition-colors">Kelola Stok RAB</a>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-3xl shadow-card ring-1 ring-accent/5 flex-1 overflow-hidden flex flex-col">
        <div class="p-6 border-b border-warm/60 flex items-center justify-between bg-gray-50/50">
            <div>
                <h2 class="font-black text-gray-800 text-lg uppercase tracking-wider">LOKASI: <?php echo e($this->reportData['rab']->lokasi); ?></h2>
                <p class="text-xs font-bold text-gray-500 mt-1">PERIODE: <?php echo e(strtoupper($months[$selectedMonth])); ?> <?php echo e($selectedYear); ?></p>
            </div>
            <div class="flex gap-2">
                <button wire:click="exportExcel" class="text-xs font-bold text-white bg-green-600 border border-green-700 px-4 py-2 rounded-xl hover:bg-green-700 transition-colors flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </button>
                <button onclick="window.print()" class="text-xs font-bold text-gray-600 bg-white border border-gray-200 px-4 py-2 rounded-xl hover:bg-gray-50 transition-colors flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak Laporan
                </button>
            </div>
        </div>

        <div class="overflow-x-auto w-full">
            <style>
                @media print {
                    body * { visibility: hidden; }
                    .print-area, .print-area * { visibility: visible; }
                    .print-area { position: absolute; left: 0; top: 0; width: 100%; }
                    @page { size: landscape; margin: 1.5cm; }
                    .print-header { display: flex !important; }
                    table th, table td { color: black !important; border-color: #000 !important; }
                }
                .print-header { display: none; }
            </style>
            <div class="print-area inline-block min-w-full align-middle font-serif">
                
                <div class="print-header items-center border-b-[3px] border-black pb-2 mb-6 text-center">
                    <div class="w-[100px] pr-4">
                        <img src="<?php echo e(asset('assets/logo.png')); ?>" onerror="this.style.display='none'" class="w-full" alt="Logo">
                    </div>
                    <div class="flex-1 text-center text-black">
                        <h1 class="text-[11pt] font-bold leading-tight uppercase text-center">PEMERINTAH PROVINSI DAERAH KHUSUS IBUKOTA JAKARTA</h1>
                        <h2 class="text-[13pt] font-bold leading-tight uppercase text-center">DINAS SUMBER DAYA AIR</h2>
                        <h3 class="text-[11pt] font-bold leading-tight uppercase text-center">SUKU DINAS SUMBER DAYA AIR KOTA ADMINISTRASI JAKARTA UTARA</h3>
                    </div>
                </div>

                <div class="text-center mb-6 print-header flex-col">
                    <h1 class="text-[14pt] font-black underline tracking-widest uppercase text-black">LAPORAN MATRIKS RAB MATERIAL</h1>
                    <p class="font-bold text-black mt-1 text-[11pt]">LOKASI: <?php echo e($this->reportData['rab']->lokasi); ?></p>
                    <p class="text-black text-[10pt] mt-0.5">PERIODE: <?php echo e(strtoupper($months[$selectedMonth])); ?> <?php echo e($selectedYear); ?></p>
                </div>

                <table class="min-w-full border-collapse border border-gray-300 text-[10px]">
                    <thead>
                        <tr class="bg-gray-100">
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center w-8">NO</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-left min-w-[150px]">MATERIAL</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center">SATUAN</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center">STOCK MATERIAL RAB</th>
                            <th colspan="<?php echo e($this->reportData['daysInMonth']); ?>" class="border border-gray-300 px-1 py-1 text-center bg-gray-200">TANGGAL (<?php echo e(strtoupper($months[$selectedMonth])); ?>)</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center min-w-[100px]">TOTAL PENGAMBILAN</th>
                            <th rowspan="2" class="border border-gray-300 px-2 py-1 text-center min-w-[100px]">SISA PENGAMBILAN</th>
                        </tr>
                        <tr class="bg-gray-50">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 1; $i <= $this->reportData['daysInMonth']; $i++): ?>
                            <th class="border border-gray-300 px-1 py-1 text-center w-6"><?php echo e($i); ?></th>
                            <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->reportData['rows']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="hover:bg-warm/30 transition-colors">
                            <td class="border border-gray-300 px-2 py-1 text-center"><?php echo e($no++); ?></td>
                            <td class="border border-gray-300 px-2 py-1 font-bold"><?php echo e($row['material']); ?></td>
                            <td class="border border-gray-300 px-2 py-1 text-center text-gray-500"><?php echo e($row['unit']); ?></td>
                            <td class="border border-gray-300 px-2 py-1 text-right font-bold text-accent bg-accent/5"><?php echo e(number_format($row['target'], 2, ',', '.')); ?></td>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php for($i = 1; $i <= $this->reportData['daysInMonth']; $i++): ?>
                                <td class="border border-gray-300 px-1 py-1 text-center text-gray-600"><?php echo e($row['daily'][$i] > 0 ? number_format($row['daily'][$i], 2, ',', '.') : ''); ?></td>
                            <?php endfor; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <td class="border border-gray-300 px-2 py-1 text-right font-bold"><?php echo e(number_format($row['total'], 2, ',', '.')); ?></td>
                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row['sisa'] < 0): ?>
                                <td class="border border-gray-300 px-2 py-1 text-right font-bold text-red-600 bg-red-50/50">
                                    <?php echo e(number_format($row['sisa'], 2, ',', '.')); ?>

                                </td>
                            <?php else: ?>
                                <td class="border border-gray-300 px-2 py-1 text-right font-bold">
                                    <?php echo e(number_format($row['sisa'], 2, ',', '.')); ?>

                                </td>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>

                
                <div class="mt-12 mb-6">
                    <h3 class="text-[11pt] font-black text-gray-800 uppercase tracking-widest mb-4 border-b-2 border-warm/60 pb-2">Bukti Pengeluaran (Riwayat Surat Jalan)</h3>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($this->reportData['history_dos']) > 0): ?>
                        <div class="bg-white rounded-[2.5rem] shadow-sm ring-1 ring-accent/5 overflow-hidden border border-gray-100">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-base/50">
                                            <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Tanggal</th>
                                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Jenis / Ref</th>
                                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Rincian Barang</th>
                                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Petugas</th>
                                            <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-center">Bukti Nota / Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->reportData['history_dos']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $do): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr class="hover:bg-base/30 transition-colors">
                                                <td class="px-8 py-5">
                                                    <span class="text-xs font-bold text-gray-700 block"><?php echo e(\Carbon\Carbon::parse($do->tanggal)->format('d/m/Y')); ?></span>
                                                    <span class="text-[10px] text-gray-400"><?php echo e(\Carbon\Carbon::parse($do->created_at)->format('H:i')); ?> WIB</span>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="flex flex-col">
                                                        <span class="text-[10px] font-black uppercase tracking-widest mb-1 text-red-500">
                                                            Keluar
                                                        </span>
                                                        <span class="text-sm font-black text-gray-800"><?php echo e($do->surat_jalan_no); ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <div class="flex flex-col">
                                                        <span class="text-sm font-bold text-gray-800 mb-1"><?php echo e(count($do->materials)); ?> Item Barang:</span>
                                                        <ul class="list-disc list-inside text-xs text-gray-600 space-y-0.5">
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $do->materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <li><span class="font-bold"><?php echo e($mat->name); ?></span> : <span class="text-red-500"><?php echo e((float)$mat->pivot->requested_volume); ?> <?php echo e($mat->unit); ?></span></li>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-5">
                                                    <span class="text-xs font-bold text-gray-700"><?php echo e($do->petugas ?? 'System'); ?></span>
                                                </td>
                                                <td class="px-6 py-5 text-center">
                                                    <div class="flex justify-center gap-2">
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($do->nota_dinas_photo): ?>
                                                            <a href="<?php echo e(Storage::url(trim($do->nota_dinas_photo))); ?>" target="_blank" class="block hover:opacity-80 transition-opacity" title="Nota Dinas">
                                                                <img src="<?php echo e(Storage::url(trim($do->nota_dinas_photo))); ?>" class="w-10 h-10 rounded-lg object-cover ring-2 ring-white shadow-sm">
                                                            </a>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($do->progress_photo): ?>
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = explode(',', $do->progress_photo); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <a href="<?php echo e(Storage::url(trim($pp))); ?>" target="_blank" class="block hover:opacity-80 transition-opacity" title="Progress Pekerjaan">
                                                                <img src="<?php echo e(Storage::url(trim($pp))); ?>" class="w-10 h-10 rounded-lg object-cover ring-2 ring-accent/30 shadow-sm">
                                                            </a>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$do->nota_dinas_photo && !$do->progress_photo): ?>
                                                            <span class="text-[10px] font-bold text-gray-300 italic uppercase">No Photo</span>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 rounded-2xl p-8 text-center border border-dashed border-gray-300">
                            <p class="text-sm font-semibold text-gray-400 italic">Belum ada data pengeluaran (Surat Jalan) untuk lokasi ini.</p>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

            </div>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH D:\program file\Project Kantor\Inventory\resources\views\livewire/laporan/rab.blade.php ENDPATH**/ ?>