<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function printRiwayat($id, Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $material = Material::findOrFail($id);
        
        // 1. Calculate Opening Balance
        $openingBalance = 0;
        if ($startDate) {
            $openingBalance = InventoryTransaction::where('material_id', $id)
                ->whereDate('created_at', '<', $startDate)
                ->selectRaw('SUM(volume_masuk) - SUM(volume_keluar) as balance')
                ->value('balance') ?? 0;
        }

        // 2. Get Transactions
        $query = InventoryTransaction::with('deliveryOrder')
            ->where('material_id', $id);

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $transactions = $query->orderBy('created_at', 'asc')->get();

        return view('laporan.print-riwayat', compact('material', 'startDate', 'endDate', 'openingBalance', 'transactions'));
    }
}
