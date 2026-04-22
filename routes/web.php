<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root ke dashboard
Route::get('/', fn() => redirect('/dashboard'));

use Livewire\Volt\Volt;

// Admin Pages
Route::prefix('dashboard')->middleware('auth')->group(function () {
    Route::get('/',        fn() => view('admin.dashboard'))->name('dashboard');
    Route::get('/produk',  fn() => view('admin.products'))->name('products');
    
    // Surat Jalan (Volt Components)
    Volt::route('/surat-jalan', 'surat-jalan.index')->name('surat-jalan.index');
    Volt::route('/surat-jalan/create', 'surat-jalan.create')->name('surat-jalan.create');
    Volt::route('/surat-jalan/{order}', 'surat-jalan.show')->name('surat-jalan.show');
    Volt::route('/stok', 'material.index')->name('material.index');
    Volt::route('/users', 'user.index')->name('user.index');
    Volt::route('/kategori', 'category.index')->name('category.index');

    Route::get('/order',   fn() => view('admin.orders'))->name('orders');
    Route::get('/pelanggan', fn() => view('admin.customers'))->name('customers');
    Volt::route('/laporan', 'laporan.index')->name('reports');
    Volt::route('/laporan/barang-masuk', 'laporan.barang-masuk')->name('laporan.barang-masuk');
    Volt::route('/laporan/stock-opname', 'laporan.rekap-opname')->name('laporan.stock-opname');
    
    // Fitur Tambahan
    Volt::route('/stock-opname', 'stock-opname.index')->name('stock-opname.index');
    Volt::route('/settings', 'settings')->name('settings');
    Route::get('/pengaturan', fn() => view('admin.settings'))->name('settings_alias');
});

// Auth
Route::get('/login',  fn() => view('auth.login'))->name('login');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
