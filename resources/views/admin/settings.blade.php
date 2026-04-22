@extends('layouts.admin')
@section('title', 'Pengaturan')

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Pengaturan Sistem</h1>
    <p class="text-sm text-gray-500 mt-0.5">Konfigurasi aplikasi dan preferensi akun.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    {{-- Settings Sidebar --}}
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-3 sticky top-6">
            @php
            $settingMenus = [
                ['icon'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z','label'=>'Profil Akun','active'=>true],
                ['icon'=>'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z','label'=>'Keamanan','active'=>false],
                ['icon'=>'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9','label'=>'Notifikasi','active'=>false],
                ['icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','label'=>'Tampilan','active'=>false],
                ['icon'=>'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z','label'=>'Pembayaran','active'=>false],
                ['icon'=>'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16','label'=>'Danger Zone','active'=>false],
            ];
            @endphp
            @foreach($settingMenus as $m)
            <button class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all
                {{ $m['active'] ? 'bg-accent text-white shadow-md shadow-accent/25' : 'text-gray-600 hover:bg-base' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $m['icon'] }}"/>
                </svg>
                {{ $m['label'] }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- Settings Content --}}
    <div class="lg:col-span-3 space-y-5">

        {{-- Profile Card --}}
        <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
            <h2 class="font-semibold text-gray-800 mb-5 pb-4 border-b border-warm/60">Informasi Profil</h2>

            <div class="flex items-center gap-5 mb-6">
                <div class="relative">
                    <div class="w-20 h-20 rounded-2xl bg-accent flex items-center justify-center shadow-lg shadow-accent/30">
                        <span class="text-white text-3xl font-bold">A</span>
                    </div>
                    <button class="absolute -bottom-1.5 -right-1.5 w-7 h-7 rounded-lg bg-white border border-warm shadow-md flex items-center justify-center hover:bg-warm transition-colors">
                        <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                </div>
                <div>
                    <p class="font-bold text-gray-900 text-lg">Administrator</p>
                    <p class="text-sm text-gray-500">admin@panel.com</p>
                    <span class="text-[11px] bg-accent/10 text-accent font-semibold px-2.5 py-0.5 rounded-lg mt-1 inline-block">Super Admin</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nama Depan</label>
                    <input type="text" value="Administrator" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-accent/30 transition-all border border-warm/60 focus:border-accent"/>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nama Belakang</label>
                    <input type="text" value="Panel" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-accent/30 transition-all border border-warm/60 focus:border-accent"/>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email</label>
                    <input type="email" value="admin@panel.com" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-accent/30 transition-all border border-warm/60 focus:border-accent"/>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nomor Telepon</label>
                    <input type="tel" value="0812-3456-7890" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-accent/30 transition-all border border-warm/60 focus:border-accent"/>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Bio</label>
                    <textarea rows="3" class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-accent/30 transition-all border border-warm/60 focus:border-accent resize-none">Super Admin dari AdminPro Management System.</textarea>
                </div>
            </div>

            <div class="flex justify-end mt-5 pt-4 border-t border-warm/60">
                <button class="bg-accent text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-accent-dark transition-all shadow-md shadow-accent/30">
                    Simpan Perubahan
                </button>
            </div>
        </div>

        {{-- Notification Preferences --}}
        <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-6">
            <h2 class="font-semibold text-gray-800 mb-5 pb-4 border-b border-warm/60">Preferensi Notifikasi</h2>
            <div class="space-y-4">
                @php
                $notifs = [
                    ['label'=>'Order Baru','desc'=>'Notifikasi saat ada order masuk','on'=>true],
                    ['label'=>'Pembayaran Diterima','desc'=>'Notifikasi saat pembayaran berhasil','on'=>true],
                    ['label'=>'Stok Menipis','desc'=>'Peringatan saat stok produk < 5','on'=>true],
                    ['label'=>'Laporan Mingguan','desc'=>'Ringkasan performa mingguan via email','on'=>false],
                    ['label'=>'Login Baru','desc'=>'Notifikasi saat ada login dari perangkat baru','on'=>true],
                ];
                @endphp
                @foreach($notifs as $n)
                <div class="flex items-center justify-between py-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $n['label'] }}</p>
                        <p class="text-xs text-gray-400">{{ $n['desc'] }}</p>
                    </div>
                    <button onclick="this.classList.toggle('bg-accent');this.classList.toggle('bg-warm');this.querySelector('span').classList.toggle('translate-x-5');this.querySelector('span').classList.toggle('translate-x-0')"
                            class="relative inline-flex w-11 h-6 rounded-full transition-colors duration-200 {{ $n['on'] ? 'bg-accent' : 'bg-warm' }}">
                        <span class="inline-block w-4 h-4 mt-1 rounded-full bg-white shadow-sm transform transition-transform duration-200 {{ $n['on'] ? 'translate-x-5' : 'translate-x-1' }}"></span>
                    </button>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

@endsection
