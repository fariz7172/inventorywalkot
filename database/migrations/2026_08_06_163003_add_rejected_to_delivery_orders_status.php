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
        DB::statement("ALTER TABLE delivery_orders MODIFY COLUMN status ENUM('draft', 'processing', 'shipped', 'rejected') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE delivery_orders MODIFY COLUMN status ENUM('draft', 'processing', 'shipped') DEFAULT 'draft'");
    }
};
