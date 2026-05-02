<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.admin')] class extends Component {
    use WithFileUploads;

    public $app_name;
    public $app_description;
    public $app_address;
    public $app_contact;
    public $app_logo;
    public $new_logo;

    public function mount()
    {
        $this->app_name = Setting::get('app_name', 'AdminPro');
        $this->app_description = Setting::get('app_description', 'Management System');
        $this->app_address = Setting::get('app_address', '');
        $this->app_contact = Setting::get('app_contact', '');
        $this->app_logo = Setting::get('app_logo', null);
    }

    public function saveProfile()
    {
        $this->validate([
            'app_name' => 'required|string|max:255',
            'app_description' => 'nullable|string|max:255',
            'app_address' => 'nullable|string',
            'app_contact' => 'nullable|string|max:255',
            'new_logo' => 'nullable|image|max:2048', // Max 2MB
        ]);

        Setting::set('app_name', $this->app_name);
        Setting::set('app_description', $this->app_description);
        Setting::set('app_address', $this->app_address);
        Setting::set('app_contact', $this->app_contact);

        if ($this->new_logo) {
            $path = $this->new_logo->store('public/settings');
            $url = Storage::url($path);
            Setting::set('app_logo', $url);
            $this->app_logo = $url;
        }

        session()->flash('success_profile', 'Profil Perusahaan berhasil disimpan!');
        
        // Dispatch event in case we want to refresh UI dynamically
        $this->dispatch('settings-updated');
    }
};

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

                @if (session()->has('success_profile'))
                    <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-sm font-bold">{{ session('success_profile') }}</span>
                    </div>
                @endif

                <form wire:submit="saveProfile" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Nama Aplikasi / Perusahaan -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1.5">Nama Perusahaan / Aplikasi <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="app_name" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-accent focus:border-accent block px-4 py-2.5 transition-all" required placeholder="Contoh: PT. Maju Bersama">
                            @error('app_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Deskripsi Pendek -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1.5">Deskripsi Singkat / Slogan</label>
                            <input type="text" wire:model="app_description" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-accent focus:border-accent block px-4 py-2.5 transition-all" placeholder="Contoh: Inventory Management System">
                            @error('app_description') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Logo Perusahaan -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Logo Perusahaan</label>
                        <div class="flex items-start gap-4">
                            <div class="w-20 h-20 rounded-xl border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden flex-shrink-0 shadow-inner">
                                @if ($new_logo)
                                    <img src="{{ $new_logo->temporaryUrl() }}" class="w-full h-full object-cover rounded-xl">
                                @elseif ($app_logo)
                                    <img src="{{ $app_logo }}" class="w-full h-full object-cover rounded-xl">
                                @else
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                @endif
                            </div>
                            <div class="flex-1">
                                <input type="file" wire:model="new_logo" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 transition-all cursor-pointer">
                                <p class="text-[10px] text-gray-400 mt-2 font-medium">Format: PNG, JPG, JPEG. Ukuran Maksimal: 2MB.</p>
                                <div wire:loading wire:target="new_logo" class="text-xs text-accent mt-1 font-bold">Mengunggah logo...</div>
                                @error('new_logo') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Kontak -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">No. Telp / Kontak</label>
                        <input type="text" wire:model="app_contact" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-accent focus:border-accent block px-4 py-2.5 transition-all" placeholder="Contoh: (021) 1234567">
                        @error('app_contact') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Alamat -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Alamat Perusahaan</label>
                        <textarea wire:model="app_address" rows="3" class="w-full bg-gray-50 border border-gray-200 text-gray-800 text-sm rounded-xl focus:ring-accent focus:border-accent block px-4 py-2.5 transition-all" placeholder="Alamat lengkap perusahaan..."></textarea>
                        @error('app_address') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
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
                <p class="text-sm text-white/80 mb-4 relative z-10 leading-relaxed">Pengaturan Lanjutan seperti Notifikasi Minimum Stok, Format Kode Penomoran, dan Backup Database akan segera hadir pada versi mendatang.</p>
                
                <div class="bg-white/10 rounded-xl p-4 relative z-10 backdrop-blur-sm border border-white/20">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-warm" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-xs font-bold text-white">Status Sistem: <span class="text-warm">Optimal</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
