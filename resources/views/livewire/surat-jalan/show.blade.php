<?php

use App\Models\DeliveryOrder;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

new class extends Component {
    use WithFileUploads;

    public DeliveryOrder $order;
    public $spbFile;

    public function mount(DeliveryOrder $order)
    {
        $this->order = $order->load('materials');
    }

    public function rendering($view)
    {
        $view->layout('layouts.admin');
    }

    public function saveSPB()
    {
        $this->validate([
            'spbFile' => 'required|file|mimes:png,jpg,jpeg,webp,pdf|max:10240',
        ], [
            'spbFile.required' => 'Pilih file terlebih dahulu.',
            'spbFile.mimes' => 'Format file harus berupa gambar (png, jpg, jpeg, webp) atau pdf.',
            'spbFile.max' => 'Ukuran maksimal file adalah 10 MB.'
        ]);

        $extension = $this->spbFile->getClientOriginalExtension();
        if (strtolower($extension) === 'pdf') {
            $spbPath = $this->spbFile->store('spb_documents', 'public');
        } else {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($this->spbFile->getRealPath());
            $encoded = $image->toWebp(75);
            $filename = 'spb_documents/' . uniqid('spb_') . '.webp';
            Storage::disk('public')->put($filename, (string) $encoded);
            $spbPath = $filename;
        }
        $this->order->update(['spb_document' => $spbPath]);
        $this->spbFile = null;
        session()->flash('message_spb', 'Dokumen SPB berhasil diunggah!');
    }

    public function processShipment(InventoryService $service)
    {
        if (!$this->order->spb_document && !$this->spbFile) {
            $this->addError('spbFile', 'Dokumen SPB wajib diunggah sebelum konfirmasi kirim.');
            return;
        }

        if ($this->spbFile) {
            $this->validate([
                'spbFile' => 'file|mimes:png,jpg,jpeg,webp,pdf|max:10240',
            ], [
                'spbFile.mimes' => 'Format file harus berupa gambar (png, jpg, jpeg, webp) atau pdf.',
                'spbFile.max' => 'Ukuran maksimal file adalah 10 MB.'
            ]);
        }

        try {
            $formattedItems = [];
            foreach ($this->order->materials as $m) {
                $formattedItems[] = [
                    'material_id' => $m->id,
                    'volume_keluar' => $m->pivot->requested_volume,
                ];
            }

            if (empty($formattedItems)) {
                throw new \Exception("Daftar barang kosong. Tidak ada yang bisa dikirim.");
            }

            // Proses File Upload
            $spbPath = null;
            if ($this->spbFile) {
                $extension = $this->spbFile->getClientOriginalExtension();
                if (strtolower($extension) === 'pdf') {
                    $spbPath = $this->spbFile->store('spb_documents', 'public');
                } else {
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read($this->spbFile->getRealPath());
                    $encoded = $image->toWebp(75);
                    $filename = 'spb_documents/' . uniqid('spb_') . '.webp';
                    Storage::disk('public')->put($filename, (string) $encoded);
                    $spbPath = $filename;
                }
                $this->order->update(['spb_document' => $spbPath]);
            }

            $service->processDelivery($this->order->id, $formattedItems);
            
            session()->flash('message', 'Barang berhasil diproses dan stok telah terpotong.');
            return $this->redirect('/dashboard/surat-jalan', navigate: true);
        } catch (\Exception $e) {
            $this->addError('process', $e->getMessage());
        }
    }
    public function rejectOrder()
    {
        try {
            if ($this->order->status !== 'draft') {
                throw new \Exception("Hanya Surat Jalan berstatus draft yang bisa ditolak.");
            }
            $this->order->update(['status' => 'rejected']);
            session()->flash('message', 'Data Surat Jalan telah ditolak. Pemohon dapat mengedit kembali data ini.');
            return $this->redirect('/dashboard/surat-jalan', navigate: true);
        } catch (\Exception $e) {
            $this->addError('process', $e->getMessage());
        }
    }
}

?>

<div class="max-w-4xl mx-auto" x-data>
    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Surat Jalan</h1>
            <div class="flex items-center gap-2 mt-1">
                <p class="text-sm text-gray-500">Nomor: <span class="font-mono font-bold text-accent">{{ $order->surat_jalan_no }}</span></p>
                <span class="text-[10px] px-2 py-0.5 rounded bg-gray-100 text-gray-400 font-bold uppercase tracking-widest">
                    Role Anda: {{ auth()->user()->getRoleNames()->implode(', ') ?: 'Tidak Ada Role' }}
                </span>
            </div>
        </div>
        <a href="/dashboard/surat-jalan" wire:navigate class="text-sm font-semibold text-gray-500 hover:text-accent flex items-center gap-1 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
        <div class="flex items-center gap-2">
            <button onclick="printBA()" class="bg-white text-gray-700 border border-gray-200 px-4 py-2 rounded-xl font-bold text-sm shadow-sm flex items-center gap-2 hover:bg-gray-50 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Cetak Berita Acara
            </button>
            <button onclick="printSPB()" class="bg-blue-600 text-white px-4 py-2 rounded-xl font-bold text-sm shadow-sm flex items-center gap-2 hover:bg-blue-700 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Cetak SPB
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
                <h2 class="text-sm font-bold text-accent uppercase tracking-wider mb-4 border-b border-warm/60 pb-2">Informasi Pengiriman</h2>
                <div class="grid grid-cols-2 gap-y-4 text-sm">
                    <div class="text-gray-400">Tanggal</div>
                    <div class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($order->tanggal)->locale('id')->isoFormat('D MMMM Y, HH:mm') }}</div>
                    <div class="text-gray-400">Lokasi Tujuan</div>
                    <div class="font-bold text-gray-800">{{ $order->lokasi }}</div>
                    <div class="text-gray-400">Kecamatan</div>
                    <div class="font-bold text-gray-800">{{ $order->pelaksana_kecamatan ?: '-' }}</div>
                    <div class="text-gray-400">Penerima</div>
                    <div class="font-bold text-gray-800">{{ $order->penerima ?: '-' }}</div>
                    <div class="text-gray-400">No. Polisi</div>
                    <div class="font-bold text-gray-800 uppercase">{{ $order->no_polisi ?: '-' }}</div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
                <h2 class="text-sm font-bold text-accent uppercase tracking-wider mb-4 border-b border-warm/60 pb-2">Daftar Material</h2>
                <div class="space-y-3">
                    @forelse($order->materials as $m)
                    <div class="flex items-center justify-between p-3 bg-base/40 rounded-xl border border-warm/40">
                        <div>
                            <p class="text-sm font-bold text-gray-800">{{ $m->name }}</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-widest font-black">{{ $m->category->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-black text-gray-900">{{ (float)$m->pivot->requested_volume }} <span class="text-xs text-gray-400">{{ $m->unit }}</span></p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-6">
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Detail barang tidak ditemukan</p>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Mungkin surat jalan ini dibuat sebelum update sistem.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
                <h2 class="text-sm font-bold text-accent uppercase tracking-wider mb-4 border-b border-warm/60 pb-2">Bukti Dokumen & Foto Progress</h2>
                @if($order->nota_dinas_photo || $order->progress_photo)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if($order->nota_dinas_photo)
                    <div class="bg-base/40 p-4 rounded-2xl border border-warm/60">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 text-center">Nota Dinas</p>
                        <div class="grid {{ count(explode(',', $order->nota_dinas_photo)) > 1 ? 'grid-cols-2' : 'grid-cols-1' }} gap-3">
                            @foreach(explode(',', $order->nota_dinas_photo) as $nd)
                            <div @click="$dispatch('open-lightbox', '{{ Storage::url(trim($nd)) }}')" class="block group relative overflow-hidden rounded-xl cursor-pointer">
                                <img src="{{ Storage::url(trim($nd)) }}" class="w-full h-48 object-cover rounded-xl group-hover:scale-105 transition-transform duration-300 shadow-sm">
                                <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                    <span class="text-white text-xs font-bold">Perbesar</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($order->progress_photo)
                    <div class="bg-base/40 p-4 rounded-2xl border border-warm/60 {{ $order->nota_dinas_photo ? '' : 'sm:col-span-2' }}">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 text-center">Progress Pekerjaan</p>
                        <div class="grid {{ count(explode(',', $order->progress_photo)) > 1 ? 'grid-cols-2' : 'grid-cols-1' }} gap-3">
                            @foreach(explode(',', $order->progress_photo) as $pp)
                            <div @click="$dispatch('open-lightbox', '{{ Storage::url(trim($pp)) }}')" class="block group relative overflow-hidden rounded-xl cursor-pointer">
                                <img src="{{ Storage::url(trim($pp)) }}" class="w-full h-48 object-cover rounded-xl group-hover:scale-105 transition-transform duration-300 shadow-sm">
                                <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                    <span class="text-white text-xs font-bold">Perbesar</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @else
                <div class="text-center py-8 bg-base/20 rounded-xl border border-dashed border-warm/60">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Belum Ada Bukti Foto</p>
                    <p class="text-[10px] text-gray-400 mt-1 italic">Surat jalan ini tidak dilengkapi foto nota dinas ataupun foto progress.</p>
                </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
                <h2 class="text-sm font-bold text-accent uppercase tracking-wider mb-4 border-b border-warm/60 pb-2">Status & Aksi</h2>
                <div class="mb-6">
                    @if($order->status === 'draft')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-600">
                            <span class="w-2 h-2 bg-amber-500 rounded-full mr-2"></span>
                            Menunggu Gudang
                        </span>
                    @elseif($order->status === 'rejected')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-600">
                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                            Ditolak
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-600">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2"></span>
                            Sudah Dikirim
                        </span>
                    @endif
                </div>

                @error('process')
                    <div class="p-3 bg-red-50 text-red-500 text-xs rounded-lg mb-4 font-bold border border-red-100">
                        {{ $message }}
                    </div>
                @enderror

                @php
                    $isAdmin = auth()->user()->hasRole(['gudang', 'kepala_gudang', 'superadmin', 'sudin', 'pemel']);
                    $isUploader = auth()->user()->hasRole(['kasubag', 'kecamatan_admin']);
                @endphp

                @if($order->status === 'draft' && ($isAdmin || $isUploader))
                    
                    @if($isUploader && !$isAdmin)
                        <div class="mb-4 p-4 border border-dashed border-gray-300 rounded-xl bg-gray-50">
                            <label class="block text-xs font-bold text-gray-700 mb-2">Dokumen SPB <span class="text-red-500">*</span></label>
                            
                            @if($order->spb_document)
                                <div class="mb-3 flex items-center gap-2 text-sm text-emerald-600 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-100">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span class="font-bold text-xs">SPB Sudah Diunggah</span>
                                    <a href="{{ Storage::url($order->spb_document) }}" target="_blank" class="ml-auto text-xs underline hover:text-emerald-800">Lihat File</a>
                                </div>
                            @endif

                            <input type="file" wire:model="spbFile" accept=".png,.jpg,.jpeg,.webp,.pdf" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 transition-all">
                            <p class="text-[10px] text-gray-400 mt-2">Format: JPG, PNG, WEBP, PDF. Max: 10MB.</p>
                            @error('spbFile') <p class="text-xs text-red-500 font-bold mt-1">{{ $message }}</p> @enderror
                            
                            @if (session()->has('message_spb'))
                                <p class="text-xs text-emerald-500 font-bold mt-1">{{ session('message_spb') }}</p>
                            @endif

                            <button wire:click="saveSPB" {{ !$spbFile ? 'disabled' : '' }} wire:loading.attr="disabled" class="w-full mt-3 bg-gray-800 text-white py-3 rounded-xl font-bold shadow-lg shadow-gray-800/30 hover:bg-gray-900 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove>Upload SPB Sekarang</span>
                                <span wire:loading>Mengupload...</span>
                            </button>
                        </div>
                    @endif

                    @if($isAdmin)
                        @if($order->spb_document)
                            <div class="mb-4 p-4 border border-emerald-200 rounded-xl bg-emerald-50/50">
                                <label class="block text-xs font-bold text-gray-700 mb-2">Dokumen SPB</label>
                                <div class="flex items-center gap-2 text-sm text-emerald-600 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-100">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span class="font-bold text-xs">SPB Sudah Diunggah</span>
                                    <a href="{{ Storage::url($order->spb_document) }}" target="_blank" class="ml-auto text-xs underline hover:text-emerald-800">Lihat File</a>
                                </div>
                            </div>
                        @else
                            <div class="mb-4 p-4 bg-warm/30 rounded-xl border border-warm/60 text-center">
                                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Menunggu Upload Form SPB</p>
                                <p class="text-[10px] text-gray-400 mt-1 italic">Menunggu pihak Kecamatan untuk mengunggah dokumen SPB.</p>
                            </div>
                        @endif
                    @endif

                    @if($isAdmin)
                        <button wire:click="processShipment" {{ (!$order->spb_document) ? 'disabled' : '' }} wire:loading.attr="disabled" class="w-full bg-accent text-white py-4 rounded-xl font-bold shadow-lg shadow-accent/30 hover:bg-accent-dark transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg wire:loading.remove class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg wire:loading class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove>Konfirmasi Kirim</span>
                            <span wire:loading>Memproses...</span>
                        </button>
                        <button wire:click="rejectOrder" wire:confirm="Yakin ingin menolak data Surat Jalan ini? Pemohon akan diminta untuk memperbaiki/mengedit data." wire:loading.attr="disabled" class="w-full mt-3 bg-red-50 text-red-600 border border-red-200 py-3.5 rounded-xl font-bold hover:bg-red-100 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Tolak Permintaan (Kembalikan)
                        </button>
                        <p class="text-[10px] text-gray-400 text-center mt-4 italic font-medium px-2">Klik "Konfirmasi Kirim" jika barang dikirim, atau "Tolak" jika data salah.</p>
                    @endif
                @elseif($order->status === 'draft')
                    <div class="p-4 bg-warm/30 rounded-xl border border-warm/60 text-center">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Menunggu Konfirmasi</p>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Hanya petugas gudang, kepala gudang, superadmin, SUDIN, atau Pemel yang dapat melakukan konfirmasi pengiriman.</p>
                    </div>
                @elseif($order->status === 'rejected')
                    <div class="p-4 bg-red-50 rounded-xl border border-red-200 text-center">
                        <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest">Data Ditolak</p>
                        <p class="text-[10px] text-red-400 mt-1 italic">Silahkan kembali ke Daftar Surat Jalan dan edit data yang salah, lalu simpan ulang.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .print-target { display: none; }
        
        @media print {
            @page { 
                margin: 0; /* Menghilangkan header/footer bawaan browser (termasuk URL) */
            }
            html, body, .flex, main, .page-content {
                height: auto !important;
                overflow: visible !important;
                display: block !important;
            }
            body * {
                visibility: hidden;
            }
            .print-active, .print-active * {
                visibility: visible;
            }
            .print-active {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                display: block !important;
                padding: 1.5cm !important; /* Memberikan margin konten agar tidak menempel di ujung kertas */
                visibility: visible !important;
            }
        }
    </style>

    {{-- AREA CETAK BERITA ACARA --}}
    <div id="print-ba-area" class="print-target bg-white p-6 text-black leading-tight" style="font-family: 'Times New Roman', serif;">
        <img src="{{ asset('assets/kop.png') }}" class="w-full h-auto mb-8">

        @php
            $carbonDate = $order->tanggal;
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            $dayName = $days[$carbonDate->dayOfWeek];
            $monthName = $months[$carbonDate->month];
            
            $sumberTujuan = $order->lokasi ?: 'Internal';
            
            $title1 = "BERITA ACARA SERAH TERIMA BARANG";
            $title2 = "DISTRIBUSI/PENGELUARAN";
            $labelPihakSatu = 'Pengurus Barang/Pengurus Barang Pembantu';
            $labelPihakDua = 'Pemakai Persediaan';
        @endphp

        <div class="text-center mb-6">
            <h1 class="text-lg font-bold underline uppercase leading-tight">
                {{ $title1 }}<br>
                {{ $title2 }}
            </h1>
            <p class="text-sm font-bold mt-1">Nomor: {{ $order->surat_jalan_no ?: '……………………………' }}</p>
        </div>

        <div class="text-justify mb-4 text-[13px]">
            <p>Pada Hari ini <span class="font-bold">{{ $dayName }}</span> Tanggal <span class="font-bold">{{ $carbonDate->day }}</span> Bulan <span class="font-bold">{{ $monthName }}</span> Tahun <span class="font-bold">{{ $carbonDate->year }}</span> </p>
            <p>yang bertanda tangan dibawah ini:</p>
            
            <div class="mt-3 ml-8 space-y-0.5">
                <p>Nama : <span class="font-bold">{{ $order->petugas ?: auth()->user()->name }}</span></p>
                <p>Jabatan : <span class="font-bold text-[11px]">{{ $labelPihakSatu }}</span></p>
            </div>

            <p class="mt-3">
                Telah menyerahkan barang persedian yang diterima oleh <span class="font-bold text-sm underline">{{ $sumberTujuan }}</span> 
                sesuai dengan Berita Acara Pemeriksaan Barang Nomor <span class="font-bold">{{ $order->surat_jalan_no ?: '……' }}</span> 
                Tanggal <span class="font-bold">{{ $carbonDate->day }}</span> Bulan <span class="font-bold">{{ $monthName }}</span> Tahun <span class="font-bold">{{ $carbonDate->year }}</span>. 
                Sebagaimana daftar terlampir. Daftar barang yang diserahkan sebagai berikut:
            </p>
        </div>

        <table class="w-full border-collapse border border-black text-[12px] mb-6">
            <thead>
                <tr class="bg-gray-50">
                    <th class="border border-black px-2 py-1 text-center w-8">No</th>
                    <th class="border border-black px-3 py-1 text-left">Uraian Nama Barang</th>
                    <th class="border border-black px-3 py-1 text-center w-24">Satuan</th>
                    <th class="border border-black px-3 py-1 text-center w-24">Volume</th>
                    <th class="border border-black px-3 py-1 text-left">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->materials as $index => $m)
                <tr>
                    <td class="border border-black px-2 py-1.5 text-center">{{ $index + 1 }}</td>
                    <td class="border border-black px-3 py-1.5 font-bold uppercase">{{ $m->name }}</td>
                    <td class="border border-black px-3 py-1.5 text-center uppercase">{{ $m->unit }}</td>
                    <td class="border border-black px-3 py-1.5 text-center font-bold text-sm">
                        {{ (float)$m->pivot->requested_volume }}
                    </td>
                    <td class="border border-black px-3 py-1.5 italic text-[10px]">{{ $order->keterangan ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p class="text-[13px] mb-8">Demikian Berita Acara Serah Terima Barang ini dibuat dalam rangkap 2 (dua) untuk digunakan sebagaimana mestinya.</p>

        <div class="grid grid-cols-2 text-center text-[13px]">
            <div>
                <p>Jakarta, {{ $carbonDate->day }} {{ $monthName }} {{ $carbonDate->year }}</p>
                <p class="mt-1">Yang menyerahkan Barang,</p>
                <p class="font-bold text-[10px] uppercase max-w-[200px] mx-auto leading-tight mt-1">{{ $labelPihakSatu }}</p>
                
                <div class="h-20"></div>
                
                <p class="font-bold underline uppercase">{{ $order->petugas ?: auth()->user()->name }}</p>
            </div>
            <div>
                <p class="invisible">Jakarta, ...</p>
                <p class="mt-1">Yang menerima Barang,</p>
                <p class="font-bold text-[10px] uppercase max-w-[200px] mx-auto leading-tight mt-1">{{ $labelPihakDua }}</p>
                
                <div class="h-20"></div>
                
                <p class="font-bold underline uppercase">{{ $order->penerima ?: ($order->pemohon ?: $sumberTujuan) }}</p>
            </div>
        </div>
        <div class="fixed bottom-[1.5cm] left-[1.5cm] text-[10px] text-gray-500">{{ url()->current() }}</div>
        <div class="fixed bottom-[1.5cm] right-[1.5cm] text-[10px] text-gray-500">Dicetak pada: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    {{-- AREA CETAK SPB --}}
    <div id="print-spb-area" class="print-target bg-white p-6 text-black leading-tight" style="font-family: 'Times New Roman', serif;">
        <img src="{{ asset('assets/kop.png') }}" class="w-full h-auto mb-8">

        <div class="text-center mb-6">
            <h1 class="text-lg font-bold underline uppercase leading-tight">
                SURAT PERMINTAAN BARANG (SPB)
            </h1>
            <p class="text-sm font-bold mt-1">Nomor: {{ $order->surat_jalan_no ?: '……………………………' }}</p>
        </div>

        <table class="w-full border-collapse border border-black text-[12px] mb-8">
            <thead>
                <tr>
                    <th class="border border-black px-2 py-2 text-center w-8">No</th>
                    <th class="border border-black px-3 py-2 text-left">Uraian / Nama Barang</th>
                    <th class="border border-black px-3 py-2 text-center w-24">Jumlah</th>
                    <th class="border border-black px-3 py-2 text-left w-32">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->materials as $index => $m)
                <tr>
                    <td class="border border-black px-2 py-1.5 text-center">{{ $index + 1 }}</td>
                    <td class="border border-black px-3 py-1.5 font-bold uppercase">{{ $m->name }}</td>
                    <td class="border border-black px-3 py-1.5 text-center font-bold text-sm">
                        {{ (float)$m->pivot->requested_volume }} {{ $m->unit }}
                    </td>
                    <td class="border border-black px-3 py-1.5 italic text-[10px]">{{ $order->keterangan ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="grid grid-cols-2 text-center text-[13px] mt-12">
            <div>
                <p class="invisible">Jakarta, ...</p>
                <p class="mt-1">Mengetahui,</p>
                <p class="font-bold text-[11px] uppercase max-w-[200px] mx-auto leading-tight mt-1">Unit / Kabag / Kabid</p>
                <div class="h-24"></div>
                <p class="font-bold underline uppercase">______________________</p>
                <p class="text-[11px] mt-0.5">NIP: ..............................</p>
            </div>
            <div>
                <p>Jakarta, {{ $carbonDate->day }} {{ $monthName }} {{ $carbonDate->year }}</p>
                <p class="mt-1">Yang Meminta Barang,</p>
                <p class="font-bold text-[11px] uppercase max-w-[200px] mx-auto leading-tight mt-1">Petugas / Pemohon</p>
                <div class="h-24"></div>
                <p class="font-bold underline uppercase">{{ $order->pemohon ?: '______________________' }}</p>
                <p class="text-[11px] mt-0.5">NIP: ..............................</p>
            </div>
        </div>
        <div class="fixed bottom-[1.5cm] left-[1.5cm] text-[10px] text-gray-500">{{ url()->current() }}</div>
        <div class="fixed bottom-[1.5cm] right-[1.5cm] text-[10px] text-gray-500">Dicetak pada: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <script>
        function printBA() {
            document.querySelectorAll('.print-target').forEach(el => el.classList.remove('print-active'));
            document.getElementById('print-ba-area').classList.add('print-active');
            window.print();
        }
        function printSPB() {
            document.querySelectorAll('.print-target').forEach(el => el.classList.remove('print-active'));
            document.getElementById('print-spb-area').classList.add('print-active');
            window.print();
        }
    </script>

    {{-- Image Lightbox Modal --}}
    <div x-data="{ open: false, src: '' }" 
         @open-lightbox.window="src = $event.detail; open = true" 
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
</div>

