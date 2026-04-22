<?php

namespace App\Observers;

use App\Models\InventoryTransaction;
use App\Models\Material;

class InventoryTransactionObserver
{
    public function created(InventoryTransaction $transaction)
    {
        $material = $transaction->material;

        if ($transaction->type === 'in') {
            $material->increment('current_volume', $transaction->volume_masuk);
        } else {
            $material->decrement('current_volume', $transaction->volume_keluar);
        }

        // Catat sisa saldo (running balance)
        $transaction->updateQuietly([
            'balance_after' => $material->current_volume,
        ]);
    }
}
