<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn(['pcs', 'balance_pcs_after']);
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('current_pcs');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->integer('pcs')->nullable();
            $table->integer('balance_pcs_after')->default(0);
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->integer('current_pcs')->default(0);
        });
    }
};
