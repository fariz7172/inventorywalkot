<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Kecamatan;
use App\Models\User;
use App\Models\Rab;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $kecamatan = Kecamatan::firstOrCreate(['nama_kecamatan' => 'PEMEL']);

        // Kaitkan user pemel jika ada
        $pemelUser = User::role('pemel')->first();
        if ($pemelUser && empty($pemelUser->kecamatan_id)) {
            $pemelUser->update(['kecamatan_id' => $kecamatan->id]);
        }

        // Update data historis RAB yang memiliki PML / PEMEL ke kecamatan PEMEL
        Rab::where(function($q) {
            $q->where('lokasi', 'like', '%(PML)%')
              ->orWhere('lokasi', 'like', '%(PEMEL)%')
              ->orWhere('lokasi', 'like', '%PML%')
              ->orWhere('lokasi', 'like', '%PEMEL%')
              ->orWhere('lokasi', 'like', '%PEMELIHARAAN%');
        })->update(['kecamatan_id' => $kecamatan->id]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $kecamatan = Kecamatan::where('nama_kecamatan', 'PEMEL')->first();
        if ($kecamatan) {
            User::where('kecamatan_id', $kecamatan->id)->update(['kecamatan_id' => null]);
            Rab::where('kecamatan_id', $kecamatan->id)->update(['kecamatan_id' => null]);
            $kecamatan->delete();
        }
    }
};
