<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('delivery_orders', 'spb_document')) {
            DB::statement("ALTER TABLE `delivery_orders` CHANGE `spb_document` `spb_document` TEXT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('delivery_orders', 'spb_document')) {
            DB::statement("ALTER TABLE `delivery_orders` CHANGE `spb_document` `spb_document` VARCHAR(255) NULL");
        }
    }
};
