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
        Schema::table('rabs', function (Blueprint $table) {
            $table->unsignedBigInteger('app_reses_survei_id')->nullable()->after('is_locked');
            $table->unsignedBigInteger('app_reses_pekerjaan_id')->nullable()->after('app_reses_survei_id');
            $table->string('app_reses_no_skpd')->nullable()->after('app_reses_pekerjaan_id');
            $table->string('app_reses_kode_tracking')->nullable()->after('app_reses_no_skpd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rabs', function (Blueprint $table) {
            $table->dropColumn([
                'app_reses_survei_id',
                'app_reses_pekerjaan_id',
                'app_reses_no_skpd',
                'app_reses_kode_tracking'
            ]);
        });
    }
};
