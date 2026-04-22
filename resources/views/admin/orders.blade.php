@extends('layouts.admin')
@section('title', 'Order')

@section('content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Manajemen Order</h1>
        <p class="text-sm text-gray-500 mt-0.5">Pantau dan kelola semua transaksi.</p>
    </div>
    <div class="flex gap-2">
        <button class="flex items-center gap-2 bg-white border border-warm text-gray-600 px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-warm/40 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export
        </button>
        <button class="flex items-center gap-2 bg-accent text-white px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-accent-dark transition-all shadow-md shadow-accent/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Order Baru
        </button>
    </div>
</div>

{{-- Status Tabs --}}
<div class="flex gap-2 mb-5 overflow-x-auto pb-1">
    @php
    $tabs = [
        ['label'=>'Semua','count'=>'1,284','active'=>true],
        ['label'=>'Pending','count'=>'24','active'=>false],
        ['label'=>'Proses','count'=>'58','active'=>false],
        ['label'=>'Dikirim','count'=>'142','active'=>false],
        ['label'=>'Selesai','count'=>'1,048','active'=>false],
        ['label'=>'Dibatalkan','count'=>'12','active'=>false],
    ];
    @endphp
    @foreach($tabs as $tab)
    <button class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold whitespace-nowrap transition-all
        {{ $tab['active'] ? 'bg-accent text-white shadow-md shadow-accent/25' : 'bg-white text-gray-600 border border-warm hover:border-accent hover:text-accent' }}">
        {{ $tab['label'] }}
        <span class="text-[11px] px-1.5 py-0.5 rounded-md {{ $tab['active'] ? 'bg-white/20' : 'bg-warm' }}">{{ $tab['count'] }}</span>
    </button>
    @endforeach
</div>

{{-- Filter Bar --}}
<div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-4 mb-5 flex flex-col sm:flex-row gap-3">
    <div class="flex items-center bg-base rounded-xl px-3 py-2 gap-2 flex-1">
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" placeholder="Cari ID order atau nama pelanggan..." class="bg-transparent text-sm outline-none w-full text-gray-600 placeholder-gray-400"/>
    </div>
    <input type="date" class="bg-base rounded-xl px-3 py-2 text-sm text-gray-600 outline-none border-none">
    <select class="bg-base rounded-xl px-3 py-2 text-sm text-gray-600 outline-none border-none">
        <option>Semua Pembayaran</option>
        <option>Transfer Bank</option>
        <option>QRIS</option>
        <option>COD</option>
        <option>Kartu Kredit</option>
    </select>
</div>

{{-- Orders Table --}}
<div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-warm/40 border-b border-warm/60">
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <input type="checkbox" class="rounded accent-accent w-4 h-4">
                    </th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Order ID</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Tanggal</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Pembayaran</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="text-right px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-warm/40">
                @php
                $orders = [
                    ['id'=>'#ORD-2401','name'=>'Budi Santoso','email'=>'budi@mail.com','date'=>'21 Apr 2025','pay'=>'Transfer BCA','total'=>'Rp 8.500.000','status'=>'Selesai','sc'=>'bg-emerald-50 text-emerald-600','items'=>3],
                    ['id'=>'#ORD-2400','name'=>'Sari Dewi','email'=>'sari@mail.com','date'=>'21 Apr 2025','pay'=>'QRIS','total'=>'Rp 14.200.000','status'=>'Proses','sc'=>'bg-blue-50 text-blue-600','items'=>1],
                    ['id'=>'#ORD-2399','name'=>'Ahmad Fauzi','email'=>'ahmad@mail.com','date'=>'20 Apr 2025','pay'=>'COD','total'=>'Rp 5.900.000','status'=>'Dikirim','sc'=>'bg-purple-50 text-purple-600','items'=>2],
                    ['id'=>'#ORD-2398','name'=>'Rina Lestari','email'=>'rina@mail.com','date'=>'20 Apr 2025','pay'=>'Kartu Kredit','total'=>'Rp 1.250.000','status'=>'Selesai','sc'=>'bg-emerald-50 text-emerald-600','items'=>1],
                    ['id'=>'#ORD-2397','name'=>'Doni Prasetyo','email'=>'doni@mail.com','date'=>'19 Apr 2025','pay'=>'Transfer BRI','total'=>'Rp 850.000','status'=>'Dibatalkan','sc'=>'bg-red-50 text-red-500','items'=>1],
                    ['id'=>'#ORD-2396','name'=>'Mega Putri','email'=>'mega@mail.com','date'=>'19 Apr 2025','pay'=>'QRIS','total'=>'Rp 3.400.000','status'=>'Pending','sc'=>'bg-amber-50 text-amber-600','items'=>4],
                    ['id'=>'#ORD-2395','name'=>'Hendra Wijaya','email'=>'hendra@mail.com','date'=>'18 Apr 2025','pay'=>'Transfer BCA','total'=>'Rp 22.750.000','status'=>'Selesai','sc'=>'bg-emerald-50 text-emerald-600','items'=>2],
                ];
                @endphp
                @foreach($orders as $o)
                <tr class="hover:bg-base/60 transition-colors">
                    <td class="px-5 py-4"><input type="checkbox" class="rounded accent-accent w-4 h-4"></td>
                    <td class="px-5 py-4">
                        <p class="font-mono text-xs font-bold text-accent">{{ $o['id'] }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $o['items'] }} item</p>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-accent/10 flex items-center justify-center flex-shrink-0">
                                <span class="text-xs font-bold text-accent">{{ substr($o['name'],0,1) }}</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-xs">{{ $o['name'] }}</p>
                                <p class="text-[10px] text-gray-400">{{ $o['email'] }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-xs text-gray-500 hidden md:table-cell">{{ $o['date'] }}</td>
                    <td class="px-5 py-4 hidden lg:table-cell">
                        <span class="text-xs bg-warm/60 text-gray-600 px-2.5 py-1 rounded-lg font-medium">{{ $o['pay'] }}</span>
                    </td>
                    <td class="px-5 py-4 font-bold text-gray-800 text-sm">{{ $o['total'] }}</td>
                    <td class="px-5 py-4">
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg {{ $o['sc'] }}">{{ $o['status'] }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-end gap-1">
                            <button onclick="showInvoice('{{ $o['id'] }}')"
                                    class="w-8 h-8 rounded-lg hover:bg-accent/10 flex items-center justify-center transition-colors group" title="Invoice">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-accent transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </button>
                            <button class="w-8 h-8 rounded-lg hover:bg-amber-50 flex items-center justify-center transition-colors group" title="Edit">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
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
        <p class="text-xs text-gray-500">Menampilkan 1–7 dari 1,284 order</p>
        <div class="flex items-center gap-1">
            <button class="w-8 h-8 rounded-lg bg-white border border-warm text-gray-400 flex items-center justify-center hover:border-accent hover:text-accent transition-colors text-xs">‹</button>
            @foreach([1,2,3,'...',184] as $pg)
            <button class="w-8 h-8 rounded-lg text-xs font-medium transition-colors
                {{ $pg===1 ? 'bg-accent text-white' : 'bg-white border border-warm text-gray-600 hover:border-accent hover:text-accent' }}">
                {{ $pg }}
            </button>
            @endforeach
            <button class="w-8 h-8 rounded-lg bg-white border border-warm text-gray-400 flex items-center justify-center hover:border-accent hover:text-accent transition-colors text-xs">›</button>
        </div>
    </div>
</div>

{{-- Invoice Modal --}}
<div id="invoiceModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeInvoice()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Invoice</h3>
                <p id="invoiceId" class="text-sm text-accent font-mono font-bold">#ORD-2401</p>
            </div>
            <button onclick="closeInvoice()" class="w-8 h-8 rounded-xl bg-warm hover:bg-warm/60 flex items-center justify-center transition-colors">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex justify-between items-start mb-6 pb-5 border-b border-warm/60">
            <div>
                <div class="w-10 h-10 rounded-xl bg-accent flex items-center justify-center mb-2">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <p class="font-bold text-gray-900">AdminPro</p>
                <p class="text-xs text-gray-400">Jl. Contoh No. 123, Jakarta</p>
            </div>
            <div class="text-right">
                <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-600">Selesai</span>
                <p class="text-xs text-gray-400 mt-2">Tanggal: 21 Apr 2025</p>
                <p class="text-xs text-gray-400">Jatuh tempo: 28 Apr 2025</p>
            </div>
        </div>

        <div class="mb-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Tagihan Kepada</p>
            <p class="font-semibold text-gray-800">Budi Santoso</p>
            <p class="text-xs text-gray-400">budi@mail.com</p>
            <p class="text-xs text-gray-400">Jl. Pelanggan No. 45, Bandung</p>
        </div>

        <table class="w-full text-sm mb-5">
            <thead>
                <tr class="bg-base rounded-xl">
                    <th class="text-left px-3 py-2.5 text-xs font-semibold text-gray-500">Item</th>
                    <th class="text-center px-3 py-2.5 text-xs font-semibold text-gray-500">Qty</th>
                    <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-500">Harga</th>
                    <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-500">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-warm/40">
                <tr>
                    <td class="px-3 py-3 text-xs text-gray-700">Laptop ASUS VivoBook 15</td>
                    <td class="px-3 py-3 text-xs text-center text-gray-600">1</td>
                    <td class="px-3 py-3 text-xs text-right text-gray-600">Rp 7.500.000</td>
                    <td class="px-3 py-3 text-xs text-right font-semibold text-gray-800">Rp 7.500.000</td>
                </tr>
                <tr>
                    <td class="px-3 py-3 text-xs text-gray-700">Mouse Logitech G502</td>
                    <td class="px-3 py-3 text-xs text-center text-gray-600">2</td>
                    <td class="px-3 py-3 text-xs text-right text-gray-600">Rp 500.000</td>
                    <td class="px-3 py-3 text-xs text-right font-semibold text-gray-800">Rp 1.000.000</td>
                </tr>
            </tbody>
        </table>

        <div class="bg-base rounded-xl p-4 space-y-2 mb-6">
            <div class="flex justify-between text-xs text-gray-600"><span>Subtotal</span><span>Rp 8.500.000</span></div>
            <div class="flex justify-between text-xs text-gray-600"><span>Ongkos Kirim</span><span>Gratis</span></div>
            <div class="flex justify-between text-xs text-gray-600"><span>PPN (11%)</span><span>Rp 935.000</span></div>
            <div class="flex justify-between text-sm font-bold text-gray-900 pt-2 border-t border-warm/60"><span>Total</span><span>Rp 9.435.000</span></div>
        </div>

        <div class="flex gap-3">
            <button onclick="closeInvoice()" class="flex-1 py-2.5 rounded-xl border border-warm text-gray-600 text-sm font-semibold hover:bg-warm/50 transition-colors">
                Tutup
            </button>
            <button class="flex-1 py-2.5 rounded-xl bg-accent text-white text-sm font-semibold hover:bg-accent-dark transition-all shadow-md shadow-accent/30">
                Cetak Invoice
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function showInvoice(id) {
    document.getElementById('invoiceId').textContent = id;
    document.getElementById('invoiceModal').classList.remove('hidden');
}
function closeInvoice() {
    document.getElementById('invoiceModal').classList.add('hidden');
}
</script>
@endpush
