<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_rab', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rab_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('target_volume', 15, 2)->default(0);
            $table->timestamps();

            // Prevent duplicate entries for same rab and material
            $table->unique(['rab_id', 'material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_rab');
    }
};
