<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatans')->nullOnDelete();
        });

        Schema::table('rabs', function (Blueprint $table) {
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatans')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rabs', function (Blueprint $table) {
            $table->dropForeign(['kecamatan_id']);
            $table->dropColumn('kecamatan_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['kecamatan_id']);
            $table->dropColumn('kecamatan_id');
        });
    }
};
