@extends('layouts.admin')
@section('title', 'Laporan')

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Laporan & Analitik</h1>
    <p class="text-sm text-gray-500 mt-0.5">Ringkasan performa bisnis secara menyeluruh.</p>
</div>

{{-- Period Selector --}}
<div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-4 mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
    <div class="flex gap-2">
        @foreach(['Harian','Mingguan','Bulanan','Tahunan'] as $i => $p)
        <button class="px-4 py-2 rounded-xl text-sm font-semibold transition-all
            {{ $i===2 ? 'bg-accent text-white shadow-md shadow-accent/25' : 'bg-warm/60 text-gray-600 hover:bg-warm' }}">
            {{ $p }}
        </button>
        @endforeach
    </div>
    <div class="flex items-center gap-2">
        <input type="month" value="{{ date('Y-m') }}" class="bg-base rounded-xl px-3 py-2 text-sm text-gray-600 outline-none border-none">
        <button class="flex items-center gap-2 bg-accent text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-accent-dark transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export PDF
        </button>
    </div>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
    $kpis = [
        ['label'=>'Pendapatan Bulan Ini','val'=>'Rp 48,2 Jt','prev'=>'Rp 43,1 Jt','up'=>true,'pct'=>'+11.8%','color'=>'text-accent','bg'=>'bg-accent/10'],
        ['label'=>'Total Transaksi','val'=>'1.284','prev'=>'1.187','up'=>true,'pct'=>'+8.2%','color'=>'text-emerald-600','bg'=>'bg-emerald-100'],
        ['label'=>'Rata-rata Order','val'=>'Rp 375.000','prev'=>'Rp 363.000','up'=>true,'pct'=>'+3.3%','color'=>'text-purple-600','bg'=>'bg-purple-100'],
        ['label'=>'Tingkat Konversi','val'=>'3.8%','prev'=>'4.1%','up'=>false,'pct'=>'-0.3%','color'=>'text-red-500','bg'=>'bg-red-100'],
    ];
    @endphp
    @foreach($kpis as $k)
    <div class="bg-white rounded-2xl p-5 shadow-card ring-1 ring-accent/10">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-gray-500">{{ $k['label'] }}</span>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg {{ $k['up'] ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">{{ $k['pct'] }}</span>
        </div>
        <p class="text-xl font-bold text-gray-900 mb-1">{{ $k['val'] }}</p>
        <p class="text-[10px] text-gray-400">vs bulan lalu: {{ $k['prev'] }}</p>
    </div>
    @endforeach
</div>

{{-- Charts Row --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    {{-- Revenue Chart --}}
    <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="font-semibold text-gray-800">Pendapatan Bulanan</h2>
                <p class="text-xs text-gray-400">Januari – April 2025</p>
            </div>
        </div>
        <div class="flex items-end gap-2 h-40 px-2">
            @php
            $bars = [
                ['month'=>'Jan','val'=>68,'amount'=>'Rp 32,8 Jt'],
                ['month'=>'Feb','val'=>75,'amount'=>'Rp 36,1 Jt'],
                ['month'=>'Mar','val'=>90,'amount'=>'Rp 43,3 Jt'],
                ['month'=>'Apr','val'=>100,'amount'=>'Rp 48,2 Jt'],
            ];
            @endphp
            @foreach($bars as $i => $b)
            <div class="flex-1 flex flex-col items-center gap-1.5 group cursor-pointer">
                <span class="text-[9px] font-semibold text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity">{{ $b['amount'] }}</span>
                <div class="w-full rounded-t-lg transition-all duration-300 group-hover:opacity-80"
                     style="height: {{ $b['val'] }}%; background: {{ $i===3 ? 'linear-gradient(180deg,#2F2FE4,#5B5BFF)' : 'linear-gradient(180deg,#c5cef5,#dde0fb)' }}"></div>
                <span class="text-xs text-gray-500 font-medium">{{ $b['month'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Top Products --}}
    <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-5">
        <div class="mb-4">
            <h2 class="font-semibold text-gray-800">Produk Terlaris</h2>
            <p class="text-xs text-gray-400">Berdasarkan jumlah terjual bulan ini</p>
        </div>
        <div class="space-y-3">
            @php
            $tops = [
                ['name'=>'iPhone 15 Pro Max','cat'=>'Gadget','sold'=>142,'pct'=>100,'rev'=>'Rp 32,7 Jt'],
                ['name'=>'Laptop ASUS VivoBook','cat'=>'Elektronik','sold'=>98,'pct'=>69,'rev'=>'Rp 8,3 Jt'],
                ['name'=>'Samsung QLED TV 55"','cat'=>'Elektronik','sold'=>67,'pct'=>47,'rev'=>'Rp 8,4 Jt'],
                ['name'=>'Headphone Sony WH-1000','cat'=>'Aksesoris','sold'=>54,'pct'=>38,'rev'=>'Rp 2,7 Jt'],
                ['name'=>'Keyboard Mech RGB','cat'=>'Aksesoris','sold'=>201,'pct'=>88,'rev'=>'Rp 1,7 Jt'],
            ];
            @endphp
            @foreach($tops as $i => $t)
            <div class="flex items-center gap-3">
                <span class="w-5 h-5 rounded-md bg-accent/10 text-accent text-[10px] font-bold flex items-center justify-center flex-shrink-0">{{ $i+1 }}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs font-semibold text-gray-700 truncate">{{ $t['name'] }}</p>
                        <p class="text-xs font-bold text-gray-800 ml-2 flex-shrink-0">{{ $t['sold'] }}</p>
                    </div>
                    <div class="h-1.5 bg-warm rounded-full overflow-hidden">
                        <div class="h-full bg-accent rounded-full" style="width: {{ $t['pct'] }}%"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Summary Table --}}
<div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-warm/60">
        <h2 class="font-semibold text-gray-800">Ringkasan per Kategori</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-warm/40">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kategori</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Produk</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Terjual</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pendapatan</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kontribusi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-warm/40">
                @php
                $cats = [
                    ['cat'=>'Elektronik','products'=>124,'sold'=>312,'rev'=>'Rp 28,4 Jt','pct'=>59,'color'=>'bg-blue-400'],
                    ['cat'=>'Gadget','products'=>87,'sold'=>201,'rev'=>'Rp 14,2 Jt','pct'=>30,'color'=>'bg-accent'],
                    ['cat'=>'Aksesoris','products'=>317,'sold'=>771,'rev'=>'Rp 5,6 Jt','pct'=>11,'color'=>'bg-emerald-400'],
                ];
                @endphp
                @foreach($cats as $c)
                <tr class="hover:bg-base/60 transition-colors">
                    <td class="px-5 py-3.5">
                        <span class="text-xs font-semibold text-accent bg-accent/10 px-2.5 py-1 rounded-lg">{{ $c['cat'] }}</span>
                    </td>
                    <td class="px-5 py-3.5 text-sm font-medium text-gray-700">{{ $c['products'] }}</td>
                    <td class="px-5 py-3.5 text-sm font-medium text-gray-700">{{ $c['sold'] }}</td>
                    <td class="px-5 py-3.5 text-sm font-bold text-gray-800">{{ $c['rev'] }}</td>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2">
                            <div class="w-20 h-1.5 bg-warm rounded-full overflow-hidden">
                                <div class="{{ $c['color'] }} h-full rounded-full" style="width: {{ $c['pct'] }}%"></div>
                            </div>
                            <span class="text-xs font-semibold text-gray-600">{{ $c['pct'] }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
