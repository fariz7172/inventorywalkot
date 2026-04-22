<?php

namespace App\Services;

use App\Models\Material;
use App\Models\InventoryTransaction;
use App\Models\DeliveryOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class InventoryService
{
    /**
     * Memproses pengiriman barang (Mutasi Keluar)
     */
    public function processDelivery(int $deliveryOrderId, array $items)
    {
        return DB::transaction(function () use ($deliveryOrderId, $items) {
            $deliveryOrder = DeliveryOrder::findOrFail($deliveryOrderId);

            foreach ($items as $item) {
                $material = Material::findOrFail($item['material_id']);

                // Validasi Stok
                if ($item['volume_keluar'] > $material->current_volume) {
                    throw new Exception("Stok material {$material->name} tidak mencukupi.");
                }

                // Buat Transaksi
                InventoryTransaction::create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'material_id' => $material->id,
                    'type' => 'out',
                    'volume_keluar' => $item['volume_keluar'],
                    'reference_number' => $deliveryOrder->surat_jalan_no,
                    'user_id' => Auth::id(),
                    'note' => 'Pengiriman Surat Jalan: ' . $deliveryOrder->surat_jalan_no
                ]);
                
                // Update Stok Material (Handled by Observer or manually here)
                // Assuming we have an observer or logic to update current_volume
            }

            // Update status surat jalan
            $deliveryOrder->update(['status' => 'shipped']);

            return $deliveryOrder;
        });
    }

    /**
     * Menangani barang masuk (Restock)
     */
    public function processIncoming(int $materialId, float $volume, string $note = null, string $referenceNumber = null, string $supplier = null, string $image = null)
    {
        return DB::transaction(function () use ($materialId, $volume, $note, $referenceNumber, $supplier, $image) {
            return InventoryTransaction::create([
                'material_id' => $materialId,
                'type' => 'in',
                'volume_masuk' => $volume,
                'reference_number' => $referenceNumber,
                'supplier' => $supplier,
                'note' => $note ?: 'Restock Barang Masuk',
                'image' => $image,
                'user_id' => Auth::id()
            ]);
        });
    }
}
