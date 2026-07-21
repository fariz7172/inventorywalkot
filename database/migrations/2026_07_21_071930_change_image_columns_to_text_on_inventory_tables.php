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
        // On Laravel 10+, DB::statement is the safest way to alter column types if doctrine/dbal is missing,
        // but `->change()` is natively supported in some versions. We'll use change() if possible.
        // Wait, Laravel 10 might still require doctrine/dbal for changing column types on MySQL depending on the exact subversion.
        // Let's just use raw SQL to be 100% safe without checking for dbal.
        
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `inventory_transactions` CHANGE `image` `image` TEXT NULL");
        
        if (Schema::hasColumn('delivery_orders', 'progress_photo')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `delivery_orders` CHANGE `progress_photo` `progress_photo` TEXT NULL");
        }
        
        if (Schema::hasColumn('delivery_orders', 'nota_dinas_photo')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `delivery_orders` CHANGE `nota_dinas_photo` `nota_dinas_photo` TEXT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE `inventory_transactions` CHANGE `image` `image` VARCHAR(255) NULL");
        
        if (Schema::hasColumn('delivery_orders', 'progress_photo')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `delivery_orders` CHANGE `progress_photo` `progress_photo` VARCHAR(255) NULL");
        }
        
        if (Schema::hasColumn('delivery_orders', 'nota_dinas_photo')) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `delivery_orders` CHANGE `nota_dinas_photo` `nota_dinas_photo` VARCHAR(255) NULL");
        }
    }
};
