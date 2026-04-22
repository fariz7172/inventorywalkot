@extends('layouts.admin')
@section('title', 'Pelanggan')

@section('content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Data Pelanggan</h1>
        <p class="text-sm text-gray-500 mt-0.5">Kelola dan pantau semua pelanggan.</p>
    </div>
    <button class="flex items-center gap-2 bg-accent text-white px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-accent-dark transition-all shadow-md shadow-accent/30">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Tambah Pelanggan
    </button>
</div>

{{-- Stats Mini --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
    $mini = [
        ['label'=>'Total Pelanggan','val'=>'3,482','icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z','color'=>'bg-accent/10 text-accent'],
        ['label'=>'Pelanggan Baru','val'=>'340','icon'=>'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z','color'=>'bg-emerald-100 text-emerald-600'],
        ['label'=>'Pelanggan Aktif','val'=>'2,841','icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z','color'=>'bg-blue-100 text-blue-600'],
        ['label'=>'Tidak Aktif','val'=>'641','icon'=>'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z','color'=>'bg-red-100 text-red-500'],
    ];
    @endphp
    @foreach($mini as $m)
    <div class="bg-white rounded-2xl p-4 shadow-card ring-1 ring-accent/10 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl {{ $m['color'] }} flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $m['icon'] }}"/>
            </svg>
        </div>
        <div>
            <p class="text-xl font-bold text-gray-900">{{ $m['val'] }}</p>
            <p class="text-xs text-gray-500">{{ $m['label'] }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Filter --}}
<div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-4 mb-5 flex flex-col sm:flex-row gap-3">
    <div class="flex items-center bg-base rounded-xl px-3 py-2 gap-2 flex-1">
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" placeholder="Cari nama, email, atau telepon..." class="bg-transparent text-sm outline-none w-full text-gray-600 placeholder-gray-400"/>
    </div>
    <select class="bg-base rounded-xl px-3 py-2 text-sm text-gray-600 outline-none border-none">
        <option>Semua Status</option>
        <option>Aktif</option>
        <option>Tidak Aktif</option>
    </select>
    <select class="bg-base rounded-xl px-3 py-2 text-sm text-gray-600 outline-none border-none">
        <option>Urutkan: Terbaru</option>
        <option>Nama A-Z</option>
        <option>Order Terbanyak</option>
        <option>Pengeluaran Tertinggi</option>
    </select>
</div>

{{-- Customers Table --}}
<div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-warm/60 border-b border-warm">
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <input type="checkbox" class="rounded accent-accent w-4 h-4">
                    </th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Kontak</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Total Order</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Total Belanja</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-warm/40">
                @php
                $customers = [
                    ['name'=>'Budi Santoso','email'=>'budi@mail.com','phone'=>'0812-3456-7890','orders'=>24,'spent'=>'Rp 42.800.000','status'=>'Aktif','joined'=>'Jan 2024','tier'=>'Gold'],
                    ['name'=>'Sari Dewi','email'=>'sari@mail.com','phone'=>'0856-7890-1234','orders'=>18,'spent'=>'Rp 28.500.000','status'=>'Aktif','joined'=>'Mar 2024','tier'=>'Silver'],
                    ['name'=>'Ahmad Fauzi','email'=>'ahmad@mail.com','phone'=>'0878-9012-3456','orders'=>8,'spent'=>'Rp 9.200.000','status'=>'Aktif','joined'=>'Jun 2024','tier'=>'Bronze'],
                    ['name'=>'Rina Lestari','email'=>'rina@mail.com','phone'=>'0821-3456-7890','orders'=>3,'spent'=>'Rp 2.100.000','status'=>'Tidak Aktif','joined'=>'Aug 2024','tier'=>'Bronze'],
                    ['name'=>'Doni Prasetyo','email'=>'doni@mail.com','phone'=>'0895-4567-8901','orders'=>45,'spent'=>'Rp 87.300.000','status'=>'Aktif','joined'=>'Dec 2023','tier'=>'Platinum'],
                    ['name'=>'Mega Putri','email'=>'mega@mail.com','phone'=>'0812-6789-0123','orders'=>12,'spent'=>'Rp 15.400.000','status'=>'Aktif','joined'=>'Feb 2024','tier'=>'Silver'],
                    ['name'=>'Hendra Wijaya','email'=>'hendra@mail.com','phone'=>'0857-8901-2345','orders'=>1,'spent'=>'Rp 850.000','status'=>'Tidak Aktif','joined'=>'Oct 2024','tier'=>'Bronze'],
                ];
                $tierColor = ['Platinum'=>'bg-purple-100 text-purple-700','Gold'=>'bg-yellow-100 text-yellow-700','Silver'=>'bg-gray-100 text-gray-600','Bronze'=>'bg-orange-100 text-orange-600'];
                @endphp
                @foreach($customers as $c)
                <tr class="hover:bg-base/60 transition-colors">
                    <td class="px-5 py-4"><input type="checkbox" class="rounded accent-accent w-4 h-4"></td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-accent flex items-center justify-center flex-shrink-0 shadow-sm">
                                <span class="text-white text-sm font-bold">{{ substr($c['name'],0,1) }}</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">{{ $c['name'] }}</p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-md font-semibold {{ $tierColor[$c['tier']] }}">{{ $c['tier'] }}</span>
                                    <span class="text-[10px] text-gray-400">Bergabung {{ $c['joined'] }}</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 hidden md:table-cell">
                        <p class="text-xs text-gray-700">{{ $c['email'] }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $c['phone'] }}</p>
                    </td>
                    <td class="px-5 py-4 hidden lg:table-cell">
                        <p class="font-semibold text-gray-800 text-sm">{{ $c['orders'] }}</p>
                        <p class="text-[10px] text-gray-400">total order</p>
                    </td>
                    <td class="px-5 py-4 hidden lg:table-cell">
                        <p class="font-bold text-gray-800 text-sm">{{ $c['spent'] }}</p>
                        <p class="text-[10px] text-gray-400">total pengeluaran</p>
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg
                            {{ $c['status']==='Aktif' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">
                            {{ $c['status'] }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-end gap-1">
                            <button class="w-8 h-8 rounded-lg hover:bg-accent/10 flex items-center justify-center transition-colors group" title="Lihat Detail">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            <button class="w-8 h-8 rounded-lg hover:bg-amber-50 flex items-center justify-center transition-colors group" title="Edit">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button class="w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center transition-colors group" title="Hapus">
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
    <div class="flex items-center justify-between px-5 py-3.5 border-t border-warm/60 bg-warm/30">
        <p class="text-xs text-gray-500">Menampilkan 1–7 dari 3,482 pelanggan</p>
        <div class="flex items-center gap-1">
            <button class="w-8 h-8 rounded-lg bg-white border border-warm text-gray-400 flex items-center justify-center hover:border-accent hover:text-accent transition-colors text-xs">‹</button>
            @foreach([1,2,3,'...',498] as $pg)
            <button class="w-8 h-8 rounded-lg text-xs font-medium transition-colors
                {{ $pg===1 ? 'bg-accent text-white' : 'bg-white border border-warm text-gray-600 hover:border-accent hover:text-accent' }}">
                {{ $pg }}
            </button>
            @endforeach
            <button class="w-8 h-8 rounded-lg bg-white border border-warm text-gray-400 flex items-center justify-center hover:border-accent hover:text-accent transition-colors text-xs">›</button>
        </div>
    </div>
</div>

@endsection
