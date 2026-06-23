<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Material;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class SyncMaterialStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:sync
                            {--dry-run : Tampilkan hasil tanpa menyimpan ke database}
                            {--material= : ID material tertentu (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi current_volume di tabel materials berdasarkan histori transaksi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun    = $this->option('dry-run');
        $materialId  = $this->option('material');

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║        SINKRONISASI STOK MATERIAL                    ║');
        $this->info('╚══════════════════════════════════════════════════════╝');

        if ($isDryRun) {
            $this->warn('  ⚠  Mode DRY-RUN aktif — tidak ada perubahan yang disimpan.');
        }

        $this->info('');

        // Ambil material
        $query = Material::query();
        if ($materialId) {
            $query->where('id', $materialId);
        }

        $materials = $query->orderBy('name')->get();

        if ($materials->isEmpty()) {
            $this->error('Tidak ada material yang ditemukan.');
            return Command::FAILURE;
        }

        $this->info("  Memproses {$materials->count()} material...");
        $this->info('');

        // Header tabel output
        $this->table(
            ['ID', 'Nama Material', 'current_volume (lama)', 'Harusnya (dari transaksi)', 'Selisih', 'Status'],
            $this->buildRows($materials, $isDryRun)
        );

        if (!$isDryRun) {
            $this->info('');
            $this->info('  ✅  Sinkronisasi selesai! Semua current_volume sudah diperbarui.');
        } else {
            $this->info('');
            $this->info('  ℹ  Jalankan tanpa --dry-run untuk menyimpan perubahan:');
            $this->info('     php artisan stock:sync');
        }

        $this->info('');

        return Command::SUCCESS;
    }

    /**
     * Hitung dan (opsional) simpan current_volume untuk setiap material.
     */
    private function buildRows($materials, bool $isDryRun): array
    {
        $rows        = [];
        $totalFixed  = 0;
        $totalOk     = 0;

        foreach ($materials as $material) {
            // Hitung total masuk & keluar dari SEMUA transaksi
            $result = InventoryTransaction::where('material_id', $material->id)
                ->selectRaw('
                    COALESCE(SUM(volume_masuk), 0) AS total_in,
                    COALESCE(SUM(volume_keluar), 0) AS total_out
                ')
                ->first();

            $totalIn   = (float) ($result->total_in  ?? 0);
            $totalOut  = (float) ($result->total_out ?? 0);
            $correct   = round($totalIn - $totalOut, 2);
            $old       = (float) $material->current_volume;
            $diff      = round($correct - $old, 2);

            if ($diff != 0) {
                $status = $isDryRun ? '⚠ Perlu Update' : '✅ Diperbaiki';
                $totalFixed++;

                if (!$isDryRun) {
                    // Update tanpa trigger observer (updateQuietly)
                    $material->updateQuietly(['current_volume' => $correct]);
                }
            } else {
                $status  = '✓ OK';
                $totalOk++;
            }

            // Hanya tampilkan yang perlu diupdate atau semua jika sedikit
            if ($diff != 0 || $materials->count() <= 20) {
                $rows[] = [
                    $material->id,
                    $material->name,
                    number_format($old, 2, '.', ','),
                    number_format($correct, 2, '.', ','),
                    ($diff > 0 ? '+' : '') . number_format($diff, 2, '.', ','),
                    $status,
                ];
            }
        }

        // Baris ringkasan
        $rows[] = ['---', '---', '---', '---', '---', '---'];
        $rows[] = [
            '',
            'TOTAL',
            '',
            '',
            "Fixed: $totalFixed | OK: $totalOk",
            '',
        ];

        return $rows;
    }
}
