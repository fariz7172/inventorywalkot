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
    public $selected_material_id = '';
    public $volume = '';
    public $note = '';
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
    public $photo;

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
        $query = Material::with('category')->latest();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('category', function($cq) {
                      $cq->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return [
            'materials' => $query->paginate($this->perPage),
            'categories' => Category::all(),
            'selectedMaterial' => $this->selected_material_id ? Material::find($this->selected_material_id) : null,
        ];
    }

    public function saveMaster()
    {
        abort_if(auth()->user()->hasRole('gudang'), 403);

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

    public function openRestock($id)
    {
        $this->selected_material_id = $id;
        $this->showModal = true;
    }

    public function openEdit($id)
    {
        abort_if(auth()->user()->hasRole('gudang'), 403);

        $material = Material::findOrFail($id);
        $this->edit_id          = $material->id;
        $this->edit_name        = $material->name;
        $this->edit_category_id = $material->category_id;
        $this->edit_unit        = $material->unit;
        $this->edit_volume      = (float)$material->current_volume;
        $this->showEditModal    = true;
    }

    public function saveEdit()
    {
        abort_if(auth()->user()->hasRole('gudang'), 403);

        $this->validate([
            'edit_name'        => 'required|string|max:255',
            'edit_category_id' => 'required|exists:categories,id',
            'edit_unit'        => 'required|string|max:50',
            'edit_volume'      => 'required|numeric|min:0',
        ]);

        Material::findOrFail($this->edit_id)->update([
            'name'           => $this->edit_name,
            'category_id'    => $this->edit_category_id,
            'unit'           => $this->edit_unit,
            'current_volume' => $this->edit_volume,
        ]);

        $this->showEditModal = false;
        $this->reset(['edit_id', 'edit_name', 'edit_category_id', 'edit_unit', 'edit_volume']);
        session()->flash('message', 'Data barang berhasil diperbarui!');
    }

    public function processIncoming(InventoryService $service)
    {
        $this->validate([
            'selected_material_id' => 'required|exists:materials,id',
            'volume' => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string|max:255',
            'supplier' => 'nullable|string|max:255',
        ]);

        $imagePath = null;
        if ($this->photo) {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($this->photo->getRealPath());
            
            // Resize if too large (optional, but good for performance)
            if ($image->width() > 1200) {
                $image->scale(width: 1200);
            }

            $filename = pathinfo($this->photo->hashName(), PATHINFO_FILENAME) . '.webp';
            $imagePath = 'transactions/' . $filename;
            
            $encoded = $image->toWebp(80);
            Storage::disk('public')->put($imagePath, (string)$encoded);
        }

        $service->processIncoming(
            $this->selected_material_id,
            $this->volume,
            $this->note,
            $this->reference_number,
            $this->supplier,
            $imagePath
        );

        $this->showModal = false;
        $this->reset(['volume', 'note', 'selected_material_id', 'reference_number', 'supplier', 'photo']);
        session()->flash('message', 'Stok berhasil ditambahkan!');
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
            @unless(auth()->user()->hasRole('gudang'))
            <button wire:click="$set('showMasterModal', true)" class="bg-white text-gray-700 border border-warm px-5 py-2.5 rounded-xl font-bold text-sm shadow-sm flex items-center gap-2 hover:bg-base transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                Tambah Jenis Barang
            </button>
            @endunless
            <button wire:click="$set('showModal', true)" class="bg-accent text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-accent/20 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Input Barang Masuk
            </button>
        </div>
    </div>

    {{-- Filter & Per Page --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-xl shadow-sm ring-1 ring-accent/5">
            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tampilkan:</label>
            <select wire:model.live="perPage" class="bg-transparent text-sm font-bold text-gray-700 outline-none border-none p-0 focus:ring-0 cursor-pointer">
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

    {{-- Grid Material --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($materials as $m)
        <div class="bg-white rounded-[2rem] shadow-card ring-1 ring-accent/5 p-8 relative group overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-accent/5 rounded-full transition-all group-hover:scale-150"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <span class="px-3 py-1 rounded-lg bg-accent/10 text-accent text-[10px] font-black uppercase tracking-widest">
                        {{ $m->category->name }}
                    </span>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $m->name }}</h3>
                <p class="text-xs text-gray-400 font-medium mb-6 uppercase tracking-tighter">Unit: {{ $m->unit }}</p>
                
                <div class="flex items-end justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Stok Saat Ini</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-3xl font-black text-gray-800">{{ (float)$m->current_volume }}</span>
                            <span class="text-sm font-bold text-gray-400">{{ $m->unit }}</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <a href="/dashboard/laporan/barang-masuk?search={{ urlencode($m->name) }}" wire:navigate class="w-10 h-10 bg-base rounded-xl flex items-center justify-center text-gray-400 hover:bg-accent hover:text-white transition-all shadow-sm group/btn relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <span class="absolute -top-10 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none font-bold uppercase tracking-widest ring-4 ring-white shadow-xl">Riwayat Rekap</span>
                        </a>

                        @unless(auth()->user()->hasRole('gudang'))
                        <button wire:click="openEdit({{ $m->id }})" class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center text-amber-500 hover:bg-amber-500 hover:text-white transition-all shadow-sm group/btn relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span class="absolute -top-10 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none font-bold uppercase tracking-widest ring-4 ring-white shadow-xl">Edit Data</span>
                        </button>
                        @endunless

                        <button wire:click="openRestock({{ $m->id }})" class="w-10 h-10 bg-base rounded-xl flex items-center justify-center text-gray-400 hover:bg-accent hover:text-white transition-all shadow-sm group/btn relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            <span class="absolute -top-10 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none font-bold uppercase tracking-widest ring-4 ring-white shadow-xl">Tambah Stok</span>
                        </button>
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
                    <select wire:model="new_category_id" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Nama Barang / Material</label>
                    <input type="text" wire:model="new_name" placeholder="Contoh: Semen Gresik 50kg" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Satuan</label>
                    <select wire:model="new_unit" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
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
                    <button type="button" wire:click="$set('showMasterModal', false)" class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-2xl font-bold text-sm">Batal</button>
                    <button type="submit" class="flex-1 bg-accent text-white py-3 rounded-2xl font-bold text-sm shadow-lg shadow-accent/20">Daftarkan Barang</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Modal Restock --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
        <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up">
            <h2 class="text-xl font-bold text-gray-900 mb-2">Input Barang Masuk</h2>
            <p class="text-xs text-gray-500 mb-6">Tambahkan stok ke gudang.</p>
            
            <form wire:submit="processIncoming" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Material</label>
                    @if($selectedMaterial)
                        <div class="w-full bg-accent/5 border border-accent/10 rounded-2xl px-4 py-3.5 flex items-center justify-between">
                            <span class="text-sm font-bold text-gray-700">{{ $selectedMaterial->name }}</span>
                            <span class="text-[10px] font-black text-accent uppercase bg-white px-2 py-1 rounded-lg shadow-sm">
                                {{ $selectedMaterial->unit }}
                            </span>
                        </div>
                        <input type="hidden" wire:model="selected_material_id">
                    @else
                        <select wire:model="selected_material_id" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                            <option value="">-- Pilih Barang --</option>
                            @foreach(App\Models\Material::all() as $mat)
                                <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->unit }})</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                @if($selected_material_id)
                <div class="mt-[-1rem] mb-4">
                    <a href="{{ route('laporan.barang-masuk', ['search' => $selectedMaterial->name]) }}" target="_blank" class="flex items-center gap-2 text-[10px] font-bold text-accent hover:text-accent/70 transition-colors ml-1 uppercase tracking-widest">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Lihat Riwayat Rekap {{ $selectedMaterial->name }}
                    </a>
                </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">No. Surat Masuk</label>
                        <input type="text" wire:model="reference_number" placeholder="SM-001" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Nama Supplier</label>
                        <input type="text" wire:model="supplier" placeholder="PT. Maju Jaya" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Jumlah Masuk</label>
                    <input type="number" step="any" wire:model="volume" placeholder="0" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Keterangan (Opsional)</label>
                    <textarea wire:model="note" placeholder="Tambahkan catatan jika perlu..." class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none h-24"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Bukti Foto Fisik</label>
                    <div class="relative">
                        <input type="file" wire:model="photo" id="photo-upload" class="hidden" accept="image/*">
                        <label for="photo-upload" class="w-full bg-base border-2 border-dashed border-warm rounded-2xl p-4 flex flex-col items-center justify-center cursor-pointer hover:border-accent/50 transition-all">
                            @if ($photo)
                                <img src="{{ $photo->temporaryUrl() }}" class="w-full h-32 object-cover rounded-xl mb-2">
                                <span class="text-[10px] font-bold text-accent uppercase">Ganti Foto</span>
                            @else
                                <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-[10px] font-bold text-gray-400 uppercase">Klik untuk ambil foto</span>
                            @endif
                        </label>
                    </div>
                    <div wire:loading wire:target="photo" class="text-[10px] text-accent font-bold mt-1 animate-pulse">Sedang mengunggah...</div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-2xl font-bold text-sm">Batal</button>
                    <button type="submit" class="flex-1 bg-accent text-white py-3 rounded-2xl font-bold text-sm shadow-lg shadow-accent/20">Tambah Stok</button>
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
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-500 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Edit Data Barang</h2>
                    <p class="text-xs text-gray-400">Perbarui informasi material</p>
                </div>
            </div>
            <form wire:submit="saveEdit" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Kategori</label>
                    <select wire:model="edit_category_id" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none outline-none">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('edit_category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Nama Barang</label>
                    <input type="text" wire:model="edit_name" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none outline-none">
                    @error('edit_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Satuan</label>
                    <select wire:model="edit_unit" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none outline-none">
                        <option>PCS</option><option>Liter</option><option>Zak</option><option>Kg</option>
                        <option>Lembar</option><option>M3</option><option>Batang</option><option>Buah</option>
                        <option>Set</option><option>Rol</option><option>Meter</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Stok Saat Ini (Koreksi)</label>
                    <input type="number" step="any" wire:model="edit_volume" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none outline-none font-bold text-accent">
                    @error('edit_volume') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="pt-4 flex gap-3">
                    <button type="button" wire:click="$set('showEditModal', false)" class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-2xl font-bold text-sm">Batal</button>
                    <button type="submit" class="flex-1 bg-amber-500 text-white py-3 rounded-2xl font-bold text-sm shadow-lg">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

