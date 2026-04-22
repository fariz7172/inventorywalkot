<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('type');
            $table->string('supplier')->nullable()->after('reference_number');
            $table->text('note')->nullable()->after('supplier');
            $table->foreignId('user_id')->nullable()->after('note')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['reference_number', 'supplier', 'note', 'user_id']);
        });
    }
};
