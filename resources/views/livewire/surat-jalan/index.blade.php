<?php

use App\Models\DeliveryOrder;
use App\Exports\DeliveryOrderExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new class extends Component {
    use WithPagination;

    public function rendering($view)
    {
        $view->layout('layouts.admin');
    }

    public $search = '';
    public $status = '';
    public $startDate = '';
    public $endDate = '';

    use \Livewire\WithFileUploads;
    public $showImportModal = false;
    public $importFile;

    public function importSuratJalan()
    {
        $this->validate([
            'importFile' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\SuratJalanImport, $this->importFile->getRealPath());
            $this->showImportModal = false;
            $this->reset('importFile');
            session()->flash('message', 'Data Surat Jalan berhasil di-import!');
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    protected $listeners = ['global-search' => 'handleGlobalSearch'];

    public function handleGlobalSearch($search)
    {
        $this->search = $search;
        $this->resetPage();
    }

    public function deleteOrder(DeliveryOrder $order)
    {
        $order->delete();
    }

    public function exportExcel()
    {
        $export = new DeliveryOrderExport($this->search, $this->status, $this->startDate, $this->endDate);
        return Excel::download($export, 'rekap-surat-jalan-' . date('Y-m-d') . '.xlsx');
    }

    public function with()
    {
        return [
            'deliveryOrders' => DeliveryOrder::query()
                ->when(auth()->user()->hasRole('kecamatan_admin'), function($q) {
                    $lokasiKecamatan = \App\Models\Rab::where('kecamatan_id', auth()->user()->kecamatan_id)->pluck('lokasi');
                    $q->whereIn('lokasi', $lokasiKecamatan);
                })
                ->when($this->search, function($q) {
                    $q->where(function($sq) {
                        $sq->where('surat_jalan_no', 'like', '%' . $this->search . '%')
                          ->orWhere('lokasi', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->status, fn($q) => $q->where('status', $this->status))
                ->when($this->startDate, fn($q) => $q->whereDate('tanggal', '>=', $this->startDate))
                ->when($this->endDate, fn($q) => $q->whereDate('tanggal', '<=', $this->endDate))
                ->orderBy('tanggal', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(50)
        ];
    }
};

?>

<div>
    {{-- Header --}}
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
            @hasanyrole('superadmin|sudin|kecamatan_admin|kepala_gudang|gudang')
            <a href="/dashboard/surat-jalan/create" wire:navigate class="flex items-center gap-2 bg-accent text-white px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-accent-dark transition-all shadow-md shadow-accent/30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Surat Jalan
            </a>
            @endhasanyrole
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-card ring-1 ring-accent/10 p-4 mb-5 flex flex-col gap-4">
        @if (session()->has('message'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-4 text-sm font-bold">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-4 text-sm font-bold">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-col sm:flex-row gap-3">
            <select wire:model.live="status" class="bg-base rounded-xl px-3 py-2 text-sm text-gray-600 outline-none border border-warm/60 focus:ring-accent/30 transition-all">
                <option value="">Semua Status</option>
                <option value="draft">Draft (Ordered)</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped (Sent)</option>
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

    {{-- Table --}}
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
                    @forelse($deliveryOrders as $order)
                    <tr class="hover:bg-base/60 transition-colors">
                        <td class="px-5 py-4">
                            <p class="font-bold text-accent font-mono text-xs">{{ $order->surat_jalan_no }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600">{{ $order->tanggal }}</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-800 text-xs">{{ $order->lokasi }}</p>
                            <p class="text-[10px] text-gray-400">{{ $order->pelaksana_kecamatan }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 font-medium">{{ $order->pemohon }}</td>
                        <td class="px-5 py-4">
                            @php
                                $statusClasses = [
                                    'draft' => 'bg-amber-50 text-amber-600',
                                    'processing' => 'bg-blue-50 text-blue-600',
                                    'shipped' => 'bg-emerald-50 text-emerald-600',
                                ];
                            @endphp
                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg {{ $statusClasses[$order->status] }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <a href="/dashboard/surat-jalan/{{ $order->id }}" wire:navigate class="w-8 h-8 rounded-lg hover:bg-accent/10 flex items-center justify-center transition-colors group" title="Lihat Detail">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @if(auth()->user()->hasRole('superadmin') || auth()->user()->hasRole('sudin'))
                                <a href="/dashboard/surat-jalan/{{ $order->id }}/edit" wire:navigate class="w-8 h-8 rounded-lg hover:bg-amber-50 flex items-center justify-center transition-colors group" title="Edit Data">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @endif
                                @if($order->status === 'draft')
                                <button wire:click="deleteOrder({{ $order->id }})" wire:confirm="Yakin ingin menghapus data ini?" class="w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center transition-colors group">
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-gray-400 italic">Belum ada data surat jalan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-warm/60 bg-warm/20">
            {{ $deliveryOrders->links() }}
        </div>
    </div>

    {{-- Modal Import Excel --}}
    @if($showImportModal)
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
                        @if($importFile)
                            <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center text-white mb-3 shadow-lg shadow-emerald-500/20">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-gray-700 mb-1">{{ $importFile->getClientOriginalName() }}</p>
                            <p class="text-[10px] text-gray-400 font-medium italic uppercase">Klik untuk ganti file</p>
                        @else
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-500 mb-3 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-gray-700 mb-1">Pilih File Excel</p>
                            <p class="text-[10px] text-gray-400 font-medium uppercase tracking-tighter">Satu nomor SJ bisa banyak baris barang</p>
                        @endif
                    </label>
                    <div wire:loading wire:target="importFile" class="absolute inset-0 bg-white/80 backdrop-blur-[1px] flex items-center justify-center rounded-[1.5rem]">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                            <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce [animation-delay:0.4s]"></div>
                        </div>
                    </div>
                </div>

                @error('importFile') <p class="text-red-500 text-[10px] font-bold uppercase mt-2 ml-2">{{ $message }}</p> @enderror

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
    @endif
</div>
