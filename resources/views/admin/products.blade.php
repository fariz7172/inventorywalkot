@extends('layouts.admin')
@section('title', 'Data Produk')

@section('content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Data Produk</h1>
        <p class="text-sm text-gray-500 mt-0.5">Kelola semua produk dalam sistem.</p>
    </div>
    <button onclick="document.getElementById('modalTambah').classList.remove('hidden')"
            class="flex items-center gap-2 bg-accent text-white px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-accent-dark transition-all shadow-md shadow-accent/30">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Tambah Produk
    </button>
</div>

{{-- Filter Bar --}}
<div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-4 mb-5 flex flex-col sm:flex-row gap-3">
    <div class="flex items-center bg-base rounded-xl px-3 py-2 gap-2 flex-1">
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" placeholder="Cari produk..." class="bg-transparent text-sm outline-none w-full text-gray-600 placeholder-gray-400"/>
    </div>
    <select class="bg-base rounded-xl px-3 py-2 text-sm text-gray-600 outline-none border-none">
        <option>Semua Kategori</option>
        <option>Elektronik</option>
        <option>Aksesoris</option>
        <option>Gadget</option>
    </select>
    <select class="bg-base rounded-xl px-3 py-2 text-sm text-gray-600 outline-none border-none">
        <option>Urutkan: Terbaru</option>
        <option>Harga Tertinggi</option>
        <option>Harga Terendah</option>
        <option>Stok Menipis</option>
    </select>
</div>

{{-- Product Table --}}
<div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-warm/40 border-b border-warm/60">
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <input type="checkbox" class="rounded accent-accent w-4 h-4">
                    </th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Produk</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Kategori</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Harga</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Stok</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-warm/40">
                @php
                $products = [
                    ['name'=>'Laptop ASUS VivoBook 15','cat'=>'Elektronik','price'=>'Rp 8.500.000','stock'=>24,'status'=>'Aktif','sc'=>'bg-emerald-50 text-emerald-600','color'=>'bg-blue-100'],
                    ['name'=>'iPhone 15 Pro Max 256GB','cat'=>'Gadget','price'=>'Rp 22.999.000','stock'=>8,'status'=>'Aktif','sc'=>'bg-emerald-50 text-emerald-600','color'=>'bg-gray-100'],
                    ['name'=>'Samsung QLED TV 55"','cat'=>'Elektronik','price'=>'Rp 12.500.000','stock'=>3,'status'=>'Menipis','sc'=>'bg-amber-50 text-amber-600','color'=>'bg-purple-100'],
                    ['name'=>'Mechanical Keyboard RGB','cat'=>'Aksesoris','price'=>'Rp 850.000','stock'=>56,'status'=>'Aktif','sc'=>'bg-emerald-50 text-emerald-600','color'=>'bg-orange-100'],
                    ['name'=>'Mouse Logitech G502 X','cat'=>'Aksesoris','price'=>'Rp 1.250.000','stock'=>0,'status'=>'Habis','sc'=>'bg-red-50 text-red-500','color'=>'bg-green-100'],
                    ['name'=>'Headphone Sony WH-1000XM5','cat'=>'Aksesoris','price'=>'Rp 4.999.000','stock'=>12,'status'=>'Aktif','sc'=>'bg-emerald-50 text-emerald-600','color'=>'bg-pink-100'],
                ];
                @endphp
                @foreach($products as $p)
                <tr class="hover:bg-base/60 transition-colors">
                    <td class="px-5 py-4">
                        <input type="checkbox" class="rounded accent-accent w-4 h-4">
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl {{ $p['color'] }} flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">{{ $p['name'] }}</p>
                                <p class="text-xs text-gray-400">SKU-{{ rand(1000,9999) }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 hidden md:table-cell">
                        <span class="text-xs bg-accent/10 text-accent font-medium px-2.5 py-1 rounded-lg">{{ $p['cat'] }}</span>
                    </td>
                    <td class="px-5 py-4 font-semibold text-gray-800 text-sm">{{ $p['price'] }}</td>
                    <td class="px-5 py-4 hidden lg:table-cell">
                        <div class="flex items-center gap-2">
                            <div class="w-16 h-1.5 bg-warm rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $p['stock'] > 20 ? 'bg-emerald-400' : ($p['stock'] > 5 ? 'bg-amber-400' : 'bg-red-400') }}"
                                     style="width: {{ min(100, $p['stock']*2) }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-700">{{ $p['stock'] }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg {{ $p['sc'] }}">{{ $p['status'] }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-end gap-1">
                            <button class="w-8 h-8 rounded-lg hover:bg-accent/10 flex items-center justify-center transition-colors group">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <button class="w-8 h-8 rounded-lg hover:bg-amber-50 flex items-center justify-center transition-colors group">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button class="w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center transition-colors group">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="flex items-center justify-between px-5 py-3.5 border-t border-warm/60 bg-warm/20">
        <p class="text-xs text-gray-500">Menampilkan 1–6 dari 528 produk</p>
        <div class="flex items-center gap-1">
            <button class="w-8 h-8 rounded-lg bg-white border border-warm text-gray-400 flex items-center justify-center hover:border-accent hover:text-accent transition-colors text-xs">‹</button>
            @foreach([1,2,3,'...',24] as $pg)
                <button class="w-8 h-8 rounded-lg text-xs font-medium transition-colors
                    {{ $pg===1 ? 'bg-accent text-white' : 'bg-white border border-warm text-gray-600 hover:border-accent hover:text-accent' }}">
                    {{ $pg }}
                </button>
            @endforeach
            <button class="w-8 h-8 rounded-lg bg-white border border-warm text-gray-400 flex items-center justify-center hover:border-accent hover:text-accent transition-colors text-xs">›</button>
        </div>
    </div>
</div>

{{-- Modal Tambah Produk --}}
<div id="modalTambah" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="document.getElementById('modalTambah').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 animate-pulse-once">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-gray-900">Tambah Produk Baru</h3>
            <button onclick="document.getElementById('modalTambah').classList.add('hidden')"
                    class="w-8 h-8 rounded-xl bg-warm hover:bg-warm/60 flex items-center justify-center transition-colors">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nama Produk</label>
                    <input type="text" placeholder="Masukkan nama produk"
                           class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-accent/30 transition-all"/>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Harga</label>
                    <input type="text" placeholder="Rp 0"
                           class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-accent/30 transition-all"/>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Stok</label>
                    <input type="number" placeholder="0"
                           class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-accent/30 transition-all"/>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Kategori</label>
                    <select class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-accent/30 transition-all">
                        <option>Pilih Kategori</option>
                        <option>Elektronik</option>
                        <option>Aksesoris</option>
                        <option>Gadget</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Deskripsi</label>
                    <textarea rows="3" placeholder="Deskripsi produk..."
                              class="w-full bg-base rounded-xl px-4 py-2.5 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-accent/30 transition-all resize-none"></textarea>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')"
                        class="flex-1 py-2.5 rounded-xl border border-warm text-gray-600 text-sm font-semibold hover:bg-warm/50 transition-colors">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 py-2.5 rounded-xl bg-accent text-white text-sm font-semibold hover:bg-accent-dark transition-all shadow-md shadow-accent/30">
                    Simpan Produk
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
