<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $column) {
            $column->decimal('balance_after', 15, 2)->after('pcs')->default(0)->comment('Saldo sisa volume setelah transaksi');
            $column->integer('balance_pcs_after')->after('balance_after')->default(0)->comment('Saldo sisa pcs setelah transaksi');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $column) {
            $column->dropColumn(['balance_after', 'balance_pcs_after']);
        });
    }
};
