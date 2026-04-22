<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            
            $table->enum('type', ['in', 'out'])->default('out');
            
            $table->decimal('volume_masuk', 10, 2)->default(0);
            $table->decimal('volume_keluar', 10, 2)->default(0);
            $table->integer('pcs')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
