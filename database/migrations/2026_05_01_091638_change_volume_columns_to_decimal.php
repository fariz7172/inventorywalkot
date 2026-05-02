<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Change materials table
        Schema::table('materials', function (Blueprint $table) {
            $table->decimal('current_volume', 15, 2)->default(0)->change();
        });

        // 2. Change inventory_transactions table
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->decimal('volume_masuk', 15, 2)->default(0)->change();
            $table->decimal('volume_keluar', 15, 2)->default(0)->change();
        });

        // 3. Change delivery_order_materials table
        Schema::table('delivery_order_materials', function (Blueprint $table) {
            $table->decimal('requested_volume', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->integer('current_volume')->default(0)->change();
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->integer('volume_masuk')->default(0)->change();
            $table->integer('volume_keluar')->default(0)->change();
        });

        Schema::table('delivery_order_materials', function (Blueprint $table) {
            $table->integer('requested_volume')->default(0)->change();
        });
    }
};
