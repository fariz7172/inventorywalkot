<?php

namespace App\Observers;

use App\Models\InventoryTransaction;
use App\Models\Material;

class InventoryTransactionObserver
{
    public function created(InventoryTransaction $transaction)
    {
        $this->adjustStock($transaction, 'add');
    }

    public function updated(InventoryTransaction $transaction)
    {
        // Jika volume atau tipe berubah, kita perlu hitung ulang
        if ($transaction->wasChanged(['volume_masuk', 'volume_keluar', 'type', 'material_id'])) {
            // 1. Kembalikan stok lama (reverse)
            $oldTransaction = new InventoryTransaction($transaction->getOriginal());
            $this->adjustStock($oldTransaction, 'reverse');

            // 2. Terapkan stok baru
            $this->adjustStock($transaction, 'add');
        }
    }

    public function deleted(InventoryTransaction $transaction)
    {
        $this->adjustStock($transaction, 'reverse');
    }

    private function adjustStock(InventoryTransaction $transaction, $mode = 'add')
    {
        $material = $transaction->material;
        if (!$material) return;

        $multiplier = ($mode === 'add') ? 1 : -1;

        if ($transaction->type === 'in') {
            $material->increment('current_volume', $transaction->volume_masuk * $multiplier);
        } else {
            // Jika mode add (transaksi keluar), stok berkurang -> multiply -1
            // Jika mode reverse (transaksi keluar dibatalkan), stok bertambah -> multiply 1
            $material->decrement('current_volume', $transaction->volume_keluar * $multiplier);
        }

        // Update running balance (quietly agar tidak trigger observer lagi)
        if ($mode === 'add') {
            $transaction->updateQuietly([
                'balance_after' => $material->current_volume,
            ]);
        }
    }
}
