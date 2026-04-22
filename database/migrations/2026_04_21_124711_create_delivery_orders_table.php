<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('surat_jalan_no')->unique();
            $table->date('tanggal');
            $table->string('lokasi')->nullable();
            $table->string('pemohon')->nullable();
            $table->string('petugas')->nullable();
            $table->string('penerima')->nullable();
            $table->string('no_polisi')->nullable();
            $table->string('pelaksana_kecamatan')->nullable();
            $table->text('keterangan')->nullable();
            
            // draft = Dibuat superadmin, belum divalidasi gudang
            // processing = Sedang dikerjakan gudang
            // shipped = Sudah dikirim / selesai
            $table->enum('status', ['draft', 'processing', 'shipped'])->default('draft');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
