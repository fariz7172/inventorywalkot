<?php

use App\Models\Category;
use function Livewire\Volt\{state, computed, layout, on};

layout('layouts.admin');

state([
    'showModal' => false,
    'editingCategory' => null,
    'name' => '',
    'description' => '',
    'search' => '',
]);

on(['global-search' => function($search) {
    $this->search = $search;
}]);

$categories = computed(function() {
    $query = Category::withCount('materials');
    if ($this->search) {
        $query->where('name', 'like', '%' . $this->search . '%');
    }
    return $query->get();
});

$openCreate = function() {
    $this->reset(['editingCategory', 'name', 'description']);
    $this->showModal = true;
};

$save = function() {
    $this->validate([
        'name' => 'required|string|max:255|unique:categories,name,' . ($this->editingCategory ? $this->editingCategory['id'] : 'NULL'),
    ]);

    if ($this->editingCategory) {
        Category::find($this->editingCategory['id'])->update([
            'name' => $this->name,
            'description' => $this->description,
        ]);
        session()->flash('message', 'Kategori berhasil diperbarui!');
    } else {
        Category::create([
            'name' => $this->name,
            'description' => $this->description,
        ]);
        session()->flash('message', 'Kategori baru berhasil ditambahkan!');
    }

    $this->showModal = false;
};

$edit = function(Category $category) {
    $this->editingCategory = $category->toArray();
    $this->name = $category->name;
    $this->description = $category->description;
    $this->showModal = true;
};

$delete = function(Category $category) {
    if ($category->materials()->exists()) {
        session()->flash('error', 'Kategori tidak bisa dihapus karena masih memiliki barang di dalamnya!');
        return;
    }
    $category->delete();
    session()->flash('message', 'Kategori berhasil dihapus.');
};

?>

<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kategori Barang</h1>
            <p class="text-sm text-gray-500">Kelola pengelompokan material inventory.</p>
        </div>
        <button wire:click="openCreate" class="bg-accent text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-accent/20 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Kategori
        </button>
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

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($this->categories as $c)
        <div class="bg-white rounded-[2rem] shadow-card ring-1 ring-accent/5 p-6 relative group overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-accent/5 rounded-full transition-all group-hover:scale-150"></div>
            
            <div class="relative z-10">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center text-accent">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01"/>
                        </svg>
                    </div>
                    <div class="flex gap-1">
                        <button wire:click="edit({{ $c->id }})" class="p-1.5 text-gray-400 hover:text-accent transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button wire:click="delete({{ $c->id }})" wire:confirm="Hapus kategori ini?" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $c->name }}</h3>
                <p class="text-xs text-gray-400 mb-4">{{ $c->description ?: 'Tidak ada deskripsi' }}</p>
                
                <div class="pt-4 border-t border-warm/60 flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Total Jenis Barang</span>
                    <span class="text-sm font-black text-accent">{{ $c->materials_count }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Modal Create/Edit --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>
        <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl p-8 animate-fade-in-up">
            <h2 class="text-xl font-bold text-gray-900 mb-2">{{ $editingCategory ? 'Edit Kategori' : 'Tambah Kategori Baru' }}</h2>
            <p class="text-xs text-gray-500 mb-6">Buat pengelompokan baru untuk barang gudang.</p>
            
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Nama Kategori</label>
                    <input type="text" wire:model="name" placeholder="Contoh: ATK, Konstruksi, dll" class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1.5 ml-1">Deskripsi (Opsional)</label>
                    <textarea wire:model="description" placeholder="..." class="w-full bg-base rounded-2xl px-4 py-3 text-sm border-none focus:ring-2 focus:ring-accent/20 outline-none h-24"></textarea>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 bg-gray-100 text-gray-500 py-3 rounded-2xl font-bold text-sm">Batal</button>
                    <button type="submit" class="flex-1 bg-accent text-white py-3 rounded-2xl font-bold text-sm shadow-lg shadow-accent/20">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>