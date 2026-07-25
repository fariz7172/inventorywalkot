<?php

use App\Models\Material;
use App\Models\Category;
use App\Services\InventoryService;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;
    use WithFileUploads;

    public function rendering($view)
    {
        $view->layout('layouts.admin');
    }

    public $showModal = false;
    public $showMasterModal = false;
    public $showEditModal = false;
    public $items = [];
    public $search = '';
    public $perPage = 50;

    // Edit Material State
    public $edit_id = '';
    public $edit_name = '';
    public $edit_category_id = '';
    public $edit_unit = '';
    public $edit_volume = '';

    // Incoming Goods Details
    public $reference_number = '';
    public $supplier = '';
    public $transaction_date = '';
    public $photos = [];

    public function mount()
    {
        $this->transaction_date = date('Y-m-d');
        $this->addItem();
    }

    public function addItem()
    {
        $this->items[] = [
            'material_id' => '',
            'volume' => '',
            'note' => '',
        ];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        if (empty($this->items)) {
            $this->addItem();
        }
    }

    // Import State
    public $showImportModal = false;
    public $importFile;

    public function importBarangMasuk()
    {
        abort_if(auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang'), 403);

        $this->validate([
            'importFile' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\BarangMasukImport, $this->importFile);
            $this->showImportModal = false;
            $this->reset('importFile');
            session()->flash('message', 'Data barang masuk berhasil di-import!');
        } catch (\Exception $e) {
            $this->showImportModal = false;
            session()->flash('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    #[On('global-search')]
    public function handleGlobalSearch($search)
    {
        $this->search = $search;
        $this->resetPage();
    }

    // Master Material State
    public $new_name = '';
    public $new_category_id = '';
    public $new_unit = 'PCS';
    public $showImportMasterModal = false;
    public $importMasterFile;

    public function downloadTemplate()
    {
        $categories = Category::pluck('name')->toArray();

        return response()->streamDownload(function () use ($categories) {
            $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $ss->getActiveSheet();
            $sheet->setTitle('Form Import');

            $sheet->setCellValue('A1', 'Kategori');
            $sheet->setCellValue('B1', 'Nama Barang');
            $sheet->setCellValue('C1', 'Satuan');

            $sheet->getStyle('A1:C1')->getFont()->setBold(true);
            $sheet->getColumnDimension('A')->setWidth(25);
            $sheet->getColumnDimension('B')->setWidth(35);
            $sheet->getColumnDimension('C')->setWidth(15);

            $catSheet = $ss->createSheet();
            $catSheet->setTitle('DaftarKategori');
            foreach ($categories as $index => $name) {
                $catSheet->setCellValue('A' . ($index + 1), $name);
            }
            $catSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);

            $categoryRange = 'DaftarKategori!$A$1:$A$' . count($categories);
            $validation = $sheet->getDataValidation('A2:A1000');
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1($categoryRange);

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
            $writer->save('php://output');
        }, 'template_master_barang.xlsx');
    }

    public function importMaster()
    {
        abort_if(auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang'), 403);

        $this->validate([
            'importMasterFile' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\MaterialImport, $this->importMasterFile);
            $this->showImportMasterModal = false;
            $this->reset('importMasterFile');
            session()->flash('message', 'Master barang berhasil di-import!');
        } catch (\Exception $e) {
            $this->showImportMasterModal = false;
            session()->flash('error', 'Gagal import master: ' . $e->getMessage());
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function with()
    {
        $query = Material::with('category')->orderBy('name', 'asc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('category', function ($cq) {
                        $cq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        return [
            'materials' => $query->paginate($this->perPage),
            'categories' => Category::all(),
            'allMaterials' => Material::orderBy('name', 'asc')->get(),
        ];
    }

    public function saveMaster()
    {
        abort_if(auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang'), 403);

        $this->validate([
            'new_name' => 'required|string|max:255',
            'new_category_id' => 'required|exists:categories,id',
            'new_unit' => 'required|string',
        ]);

        Material::create([
            'name' => $this->new_name,
            'category_id' => $this->new_category_id,
            'unit' => $this->new_unit,
            'current_volume' => 0,
        ]);

        $this->showMasterModal = false;
        $this->reset(['new_name', 'new_category_id', 'new_unit']);
        session()->flash('message', 'Master barang baru berhasil ditambahkan!');
    }

    public function openRestock($id = null)
    {
        abort_if(auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang'), 403);

        $this->reset(['items', 'reference_number', 'supplier', 'photos']);
        $this->transaction_date = date('Y-m-d');

        if ($id) {
            $this->items = [
                [
                    'material_id' => $id,
                    'volume' => '',
                    'note' => '',
                ]
            ];
        } else {
            $this->addItem();
        }

        $this->showModal = true;
    }

    public function openEdit($id)
    {
        abort_if(auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang'), 403);

        $material = Material::findOrFail($id);
        $this->edit_id = $material->id;
        $this->edit_name = $material->name;
        $this->edit_category_id = $material->category_id;
        $this->edit_unit = $material->unit;
        $this->edit_volume = (float) $material->current_volume;
        $this->showEditModal = true;
    }

    public function saveEdit()
    {
        abort_if(auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang'), 403);

        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_category_id' => 'required|exists:categories,id',
            'edit_unit' => 'required|string|max:50',
            'edit_volume' => 'required|numeric|min:0',
        ]);

        Material::findOrFail($this->edit_id)->update([
            'name' => $this->edit_name,
            'category_id' => $this->edit_category_id,
            'unit' => $this->edit_unit,
            'current_volume' => $this->edit_volume,
        ]);

        $this->showEditModal = false;
        $this->reset(['edit_id', 'edit_name', 'edit_category_id', 'edit_unit', 'edit_volume']);
        session()->flash('message', 'Data barang berhasil diperbarui!');
    }

    public function processIncoming(InventoryService $service)
    {
        abort_if(auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang'), 403);

        $this->validate([
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.volume' => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'photos.*' => 'nullable|image|max:5120',
        ]);

        $imagePaths = [];
        if (!empty($this->photos)) {
            $manager = new ImageManager(new Driver());
            foreach ($this->photos as $pic) {
                $image = $manager->read($pic->getRealPath());

                if ($image->width() > 1200) {
                    $image->scale(width: 1200);
                }

                $filename = pathinfo($pic->hashName(), PATHINFO_FILENAME) . '.webp';
                $path = 'transactions/' . $filename;

                $encoded = $image->toWebp(80);
                Storage::disk('public')->put($path, (string) $encoded);
                $imagePaths[] = $path;
            }
        }
        
        $imagePathStr = !empty($imagePaths) ? implode(',', $imagePaths) : null;

        foreach ($this->items as $item) {
            $service->processIncoming(
                $item['material_id'],
                $item['volume'],
                $item['note'],
                $this->reference_number,
                $this->supplier,
                $imagePathStr,
                $this->transaction_date
            );
        }

        $this->showModal = false;
        $this->reset(['items', 'reference_number', 'supplier', 'photos', 'transaction_date']);
        $this->transaction_date = date('Y-m-d');
        $this->addItem();
        session()->flash('message', 'Semua stok berhasil ditambahkan!');
    }
};

?>

<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manajemen Stok</h1>
            <p class="text-sm text-gray-500">Pantau sisa material dan lakukan penambahan stok (Barang Masuk).</p>
        </div>
        <div class="flex gap-2">
            @unless(auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang'))
                <button wire:click="$set('showMasterModal', true)"
                    class="bg-white text-gray-700 border border-warm px-5 py-2.5 rounded-xl font-bold text-sm shadow-sm flex items-center gap-2 hover:bg-base transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                    </svg>
                    Tambah Jenis Barang
                </button>
            @endunless
            @unless(auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang'))
                <button wire:click="$set('showImportModal', true)"
                    class="bg-emerald-100 text-emerald-700 border border-emerald-200 px-5 py-2.5 rounded-xl font-bold text-sm shadow-sm flex items-center gap-2 hover:bg-emerald-200 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Import Excel
                </button>
                <button wire:click="$set('showModal', true)"
                    class="bg-accent text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-accent/20 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Input Barang Masuk
                </button>
            @endunless
        </div>
    </div>

    {{-- Filter & Per Page --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-xl shadow-sm ring-1 ring-accent/5">
            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tampilkan:</label>
            <select wire:model.live="perPage"
                class="bg-transparent text-sm font-bold text-gray-700 outline-none border-none p-0 focus:ring-0 cursor-pointer">
                <option value="9">9 Data</option>
                <option value="25">25 Data</option>
                <option value="50">50 Data</option>
                <option value="100">100 Data</option>
            </select>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold">
            {{ session('error') }}
        </div>
    @endif

    @if (session()->has('warning'))
        <div class="bg-amber-50 border border-amber-200 text-amber-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold">
            {{ session('warning') }}
        </div>
    @endif

    {{-- Grid Material --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($materials as $m)
            <div class="bg-white rounded-[2rem] shadow-card ring-1 ring-accent/5 p-8 relative group overflow-hidden">
                <div
                    class="absolute -right-4 -top-4 w-24 h-24 bg-accent/5 rounded-full transition-all group-hover:scale-150">
                </div>

                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <span
                            class="px-3 py-1 rounded-lg bg-accent/10 text-accent text-[10px] font-black uppercase tracking-widest">
                            {{ $m->category->name }}
                        </span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $m->name }}</h3>
                    <p class="text-xs text-gray-400 font-medium mb-6 uppercase tracking-tighter">Unit: {{ $m->unit }}</p>

                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Stok Saat Ini</p>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-black text-gray-800">{{ (float) $m->current_volume }}</span>
                                <span class="text-sm font-bold text-gray-400">{{ $m->unit }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="/dashboard/laporan/barang-masuk?search={{ urlencode($m->name) }}" wire:navigate
                                class="w-10 h-10 bg-base rounded-xl flex items-center justify-center text-gray-400 hover:bg-accent hover:text-white transition-all shadow-sm group/btn relative">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                <span
                                    class="absolute -top-10 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none font-bold uppercase tracking-widest ring-4 ring-white shadow-xl">Riwayat
                                    Rekap</span>
                            </a>

                            @unless(auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang'))
                                <button wire:click="openEdit({{ $m->id }})"
                                    class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center text-amber-500 hover:bg-amber-500 hover:text-white transition-all shadow-sm group/btn relative">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span
                                        class="absolute -top-10 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none font-bold uppercase tracking-widest ring-4 ring-white shadow-xl">Edit
                                        Data</span>
                                </button>
                            @endunless

                            @unless(auth()->user()->hasRole('gudang') || auth()->user()->hasRole('kepala_gudang'))
                                <button wire:click="openRestock({{ $m->id }})"
                                    class="w-10 h-10 bg-base rounded-xl flex items-center justify-center text-gray-400 hover:bg-accent hover:text-white transition-all shadow-sm group/btn relative">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <span
                                        class="absolute -top-10 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none font-bold uppercase tracking-widest ring-4 ring-white shadow-xl">Tambah
                                        Stok</span>
                                </button>
                            @endunless
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($materials->hasPages())
        <div class="mt-8">
            {{ $materials->links() }}
        </div>
    @endif

    {{-- Modal Master Barang --}}
    @if($showMasterModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="$set('showMasterModal', false)"></div>
            <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Tambah Jenis Barang Baru</h2>
                <p class="text-xs text-gray-500 mb-6">Daftarkan material baru ke dalam sistem inventory.</p>

                <form wire:submit="saveMaster" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Kategori Barang</label>
                        <select wire:model="new_category_id"
                            class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Nama Barang /
                            Material</label>
                        <input type="text" wire:model="new_name" placeholder="Contoh: Semen Gresik 50kg"
                            class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Satuan</label>
                        <select wire:model="new_unit"
                            class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                            <option value="PCS">PCS</option>
                            <option value="Liter">Liter</option>
                            <option value="Zak">Zak</option>
                            <option value="Kg">Kg</option>
                            <option value="Lembar">Lembar</option>
                            <option value="M3">M3</option>
                            <option value="Batang">Batang</option>
                            <option value="Buah">Buah</option>
                            <option value="Set">Set</option>
                            <option value="Rol">Rol</option>
                            <option value="Meter">Meter</option>
                        </select>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" wire:click="$set('showMasterModal', false)"
                            class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-2xl font-bold text-sm">Batal</button>
                        <button type="submit"
                            class="flex-1 bg-accent text-white py-3 rounded-2xl font-bold text-sm shadow-lg shadow-accent/20">Daftarkan
                            Barang</button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Atau Import Banyak
                        Sekaligus</p>
                    <button type="button" wire:click="$set('showImportMasterModal', true); $set('showMasterModal', false);"
                        class="w-full bg-emerald-50 text-emerald-600 py-3 rounded-2xl font-bold text-xs flex items-center justify-center gap-2 transition-all hover:bg-emerald-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2a4 4 0 014-4h1m-1 4h2m-2 3h2m4-9a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Import Master Barang (Excel)
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Restock --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
            <div
                class="relative bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up max-h-[90vh] overflow-y-auto">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Input Barang Masuk</h2>
                <p class="text-xs text-gray-500 mb-6">Tambahkan stok ke gudang secara kolektif.</p>

                <form wire:submit="processIncoming" class="space-y-6">
                    @if ($errors->any())
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-bold text-red-700 uppercase">Ada kesalahan pada input Anda. Mohon cek
                                        kembali.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">No. Surat
                                Masuk</label>
                            <input type="text" wire:model="reference_number" placeholder="SM-001"
                                class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none @error('reference_number') ring-2 ring-red-500/50 @enderror">
                            @error('reference_number') <span
                            class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Nama Supplier</label>
                            <input type="text" wire:model="supplier" placeholder="PT. Maju Jaya"
                                class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none @error('supplier') ring-2 ring-red-500/50 @enderror">
                            @error('supplier') <span
                            class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Tanggal Masuk</label>
                            <input type="date" wire:model="transaction_date"
                                class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none @error('transaction_date') ring-2 ring-red-500/50 @enderror">
                            @error('transaction_date') <span
                            class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between ml-1">
                            <label class="text-xs font-bold text-gray-400 uppercase">Daftar Barang</label>
                            <button type="button" wire:click="addItem"
                                class="text-[10px] font-black text-accent uppercase tracking-widest hover:text-accent/70 flex items-center gap-1 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Baris
                            </button>
                        </div>

                        @foreach($items as $index => $item)
                            <div class="bg-base/40 rounded-3xl p-4 ring-1 ring-warm/30 relative group">
                                @if(count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})"
                                        class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-red-500/20 hover:bg-red-600 transition-all opacity-0 group-hover:opacity-100 z-10">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                @endif

                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                    <div class="md:col-span-6">
                                        <label
                                            class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Material</label>
                                        <select wire:model="items.{{ $index }}.material_id"
                                            class="w-full bg-white rounded-xl px-4 py-2.5 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none shadow-sm @error('items.' . $index . '.material_id') ring-2 ring-red-500/50 @enderror">
                                            <option value="">-- Pilih Barang --</option>
                                            @foreach($allMaterials as $mat)
                                                <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->unit }})</option>
                                            @endforeach
                                        </select>
                                        @error('items.' . $index . '.material_id') <span
                                        class="text-[9px] text-red-500 font-bold mt-1 ml-1">Harus dipilih</span> @enderror
                                    </div>
                                    <div class="md:col-span-3">
                                        <label
                                            class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Jumlah</label>
                                        <input type="number" step="any" wire:model="items.{{ $index }}.volume" placeholder="0"
                                            class="w-full bg-white rounded-xl px-4 py-2.5 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none shadow-sm @error('items.' . $index . '.volume') ring-2 ring-red-500/50 @enderror">
                                        @error('items.' . $index . '.volume') <span
                                        class="text-[9px] text-red-500 font-bold mt-1 ml-1">Minimal 0.01</span> @enderror
                                    </div>
                                    <div class="md:col-span-3">
                                        <label
                                            class="block text-[10px] font-bold text-gray-400 uppercase mb-1 ml-1">Keterangan</label>
                                        <input type="text" wire:model="items.{{ $index }}.note" placeholder="..."
                                            class="w-full bg-white rounded-xl px-4 py-2.5 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none shadow-sm">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Bukti Foto Fisik
                            (Opsional - Bisa Lebih Dari 1)</label>
                        <div class="relative">
                            <input type="file" wire:model="photos" id="photo-upload" class="hidden" accept="image/*" multiple>
                            <label for="photo-upload"
                                class="w-full bg-base border-2 border-dashed border-warm rounded-2xl p-4 flex flex-col items-center justify-center cursor-pointer hover:border-accent/50 transition-all @error('photos.*') border-red-500/50 bg-red-50 @enderror">
                                @if (!empty($photos))
                                    <div class="flex flex-wrap gap-2 mb-2 justify-center">
                                    @foreach($photos as $pic)
                                        @php
                                            $previewUrl = null;
                                            try {
                                                $previewUrl = $pic->temporaryUrl();
                                            } catch (\Exception $e) {
                                                $previewUrl = null;
                                            }
                                        @endphp
                                        @if($previewUrl)
                                            <img src="{{ $previewUrl }}" class="h-24 w-24 object-cover rounded-xl border border-gray-200">
                                        @endif
                                    @endforeach
                                    </div>
                                    <span class="text-[10px] font-bold text-accent uppercase mt-2">Tambah/Ganti Foto</span>
                                @else
                                    <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Klik untuk pilih beberapa foto</span>
                                @endif
                            </label>
                        </div>
                        @error('photos.*') <span class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</span>
                        @enderror
                        <div wire:loading wire:target="photos" class="text-[10px] text-accent font-bold mt-1 animate-pulse">
                            Sedang memuat gambar...</div>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="flex-1 bg-gray-100 text-gray-500 py-4 rounded-2xl font-bold text-sm">Batal</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="flex-1 bg-accent text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-accent/20 flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="processIncoming">Proses Masuk Gudang</span>
                            <span wire:loading wire:target="processIncoming" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Edit Barang --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="$set('showEditModal', false)"></div>
            <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div
                        class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-500 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Edit Data Barang</h2>
                        <p class="text-xs text-gray-400">Perbarui informasi material</p>
                    </div>
                </div>
                <form wire:submit="saveEdit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Kategori</label>
                        <select wire:model="edit_category_id"
                            class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none outline-none">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('edit_category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Nama Barang</label>
                        <input type="text" wire:model="edit_name"
                            class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none outline-none">
                        @error('edit_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Satuan</label>
                        <select wire:model="edit_unit"
                            class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none outline-none">
                            <option>PCS</option>
                            <option>Liter</option>
                            <option>Zak</option>
                            <option>Kg</option>
                            <option>Lembar</option>
                            <option>M3</option>
                            <option>Batang</option>
                            <option>Buah</option>
                            <option>Set</option>
                            <option>Rol</option>
                            <option>Meter</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Stok Saat Ini
                            (Koreksi)</label>
                        <input type="number" step="any" wire:model="edit_volume"
                            class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none outline-none font-bold text-accent">
                        @error('edit_volume') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="pt-4 flex gap-3">
                        <button type="button" wire:click="$set('showEditModal', false)"
                            class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-2xl font-bold text-sm">Batal</button>
                        <button type="submit"
                            class="flex-1 bg-amber-500 text-white py-3 rounded-2xl font-bold text-sm shadow-lg">Simpan
                            Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Import Excel --}}
    @if($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="$set('showImportModal', false)"></div>
            <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up">
                <div class="flex items-center gap-3 mb-6">
                    <div
                        class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 leading-tight">Import Barang Masuk</h2>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Gunakan file .xlsx
                            atau .csv</p>
                        <a href="/templates/template_barang_masuk.xlsx" download
                            class="text-[10px] text-emerald-600 font-black uppercase tracking-widest hover:underline mt-2 inline-block">
                            📥 Download Template Excel
                        </a>
                    </div>
                </div>

                <form wire:submit="importBarangMasuk" class="space-y-6">
                    <div
                        class="bg-base rounded-[1.5rem] p-6 border-2 border-dashed border-warm/60 group hover:border-emerald-500/50 transition-all relative">
                        <input type="file" wire:model="importFile" id="import-file-masuk" class="hidden"
                            accept=".xlsx,.xls,.csv">
                        <label for="import-file-masuk" class="flex flex-col items-center justify-center cursor-pointer">
                            @if($importFile)
                                <div
                                    class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center text-white mb-3 shadow-lg shadow-emerald-500/20">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <p class="text-sm font-bold text-gray-700 mb-1">{{ $importFile->getClientOriginalName() }}</p>
                                <p class="text-[10px] text-gray-400 font-medium italic uppercase">Klik untuk ganti file</p>
                            @else
                                <div
                                    class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-500 mb-3 group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                                <p class="text-sm font-bold text-gray-700 mb-1">Pilih File Excel</p>
                                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-tighter">Format: Tanggal,
                                    Nama Barang, Volume Masuk, dll</p>
                            @endif
                        </label>
                        <div wire:loading wire:target="importFile"
                            class="absolute inset-0 bg-white/80 backdrop-blur-[1px] flex items-center justify-center rounded-[1.5rem]">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce [animation-delay:0.2s]">
                                </div>
                                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce [animation-delay:0.4s]">
                                </div>
                            </div>
                        </div>
                    </div>

                    @error('importFile') <p class="text-red-500 text-[10px] font-bold uppercase mt-2 ml-2">{{ $message }}
                    </p> @enderror

                    <div class="flex flex-col gap-3">
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full bg-emerald-500 text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-emerald-500/20 hover:bg-emerald-600 transition-all flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="importBarangMasuk">Mulai Import Sekarang</span>
                            <span wire:loading wire:target="importBarangMasuk" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Sedang Memproses...
                            </span>
                        </button>
                        <button type="button" wire:click="$set('showImportModal', false)"
                            class="w-full bg-base text-gray-500 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-warm/40 transition-all">
                            Batalkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Import Master --}}
    @if($showImportMasterModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="$set('showImportMasterModal', false)">
            </div>
            <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Import Master Barang</h2>
                <p class="text-xs text-gray-500 mb-6">Upload file Excel untuk mendaftarkan banyak barang sekaligus.</p>

                <form wire:submit="importMaster" class="space-y-4">
                    <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl mb-4">
                        <p class="text-[10px] text-emerald-600 font-bold uppercase mb-2">Download Template</p>
                        <button type="button" wire:click="downloadTemplate"
                            class="inline-flex items-center gap-2 text-xs font-bold text-emerald-700 hover:underline">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            template_master_barang.xlsx (Klik untuk Download Terbaru)
                        </button>
                    </div>

                    <div class="relative group">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Pilih File Excel</label>
                        <div
                            class="relative h-32 w-full border-2 border-dashed border-gray-200 rounded-2xl flex flex-col items-center justify-center gap-2 transition-all group-hover:border-accent/30 group-hover:bg-accent/[0.02]">
                            <input type="file" wire:model="importMasterFile"
                                class="absolute inset-0 opacity-0 cursor-pointer">
                            <svg class="w-8 h-8 text-gray-300 group-hover:text-accent/50" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <span class="text-xs font-medium text-gray-500">
                                @if($importMasterFile)
                                    {{ $importMasterFile->getClientOriginalName() }}
                                @else
                                    Klik atau seret file ke sini
                                @endif
                            </span>
                        </div>
                        @error('importMasterFile') <span
                        class="text-[10px] text-red-500 font-bold mt-1 ml-2">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" wire:click="$set('showImportMasterModal', false)"
                            class="flex-1 bg-gray-100 text-gray-500 py-4 rounded-2xl font-bold text-sm">Batal</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="flex-1 bg-accent text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-accent/20 flex items-center justify-center gap-2">
                            <span wire:loading.remove wire:target="importMaster">Proses Import</span>
                            <span wire:loading wire:target="importMaster" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>