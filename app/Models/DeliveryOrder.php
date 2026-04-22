<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'surat_jalan_no', 'tanggal', 'lokasi', 'pemohon', 'petugas', 
        'penerima', 'no_polisi', 'pelaksana_kecamatan', 'keterangan', 'status'
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Relasi ke Material melalui tabel pivot delivery_order_materials
     */
    public function materials()
    {
        return $this->belongsToMany(Material::class, 'delivery_order_materials', 'delivery_order_id', 'material_id')
            ->withPivot('requested_volume')
            ->withTimestamps();
    }
}
