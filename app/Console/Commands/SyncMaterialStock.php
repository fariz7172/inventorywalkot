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
            // Ambil semua transaksi secara kronologis untuk dihitung ulang running balance-nya
            $transactions = InventoryTransaction::where('material_id', $material->id)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $runningBalance = 0;
            $trxFixedCount  = 0;

            foreach ($transactions as $trx) {
                $runningBalance += (float) $trx->volume_masuk - (float) $trx->volume_keluar;
                $runningBalance = round($runningBalance, 2);

                if (round((float) $trx->balance_after, 2) !== $runningBalance) {
                    if (!$isDryRun) {
                        $trx->updateQuietly(['balance_after' => $runningBalance]);
                    }
                    $trxFixedCount++;
                }
            }

            $correct   = $runningBalance;
            $old       = (float) $material->current_volume;
            $diff      = round($correct - $old, 2);

            if ($diff != 0 || $trxFixedCount > 0) {
                $status = $isDryRun ? "⚠ Perlu Update ($trxFixedCount trx stale)" : "✅ Diperbaiki ($trxFixedCount trx stale)";
                $totalFixed++;

                if (!$isDryRun && $diff != 0) {
                    // Update tanpa trigger observer (updateQuietly)
                    $material->updateQuietly(['current_volume' => $correct]);
                }
            } else {
                $status  = '✓ OK';
                $totalOk++;
            }

            // Hanya tampilkan yang perlu diupdate atau semua jika sedikit
            if ($diff != 0 || $trxFixedCount > 0 || $materials->count() <= 20) {
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
