<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id', 
        'material_id', 
        'type', 
        'volume_masuk', 
        'volume_keluar', 
        'pcs',
        'balance_after',
        'reference_number',
        'supplier',
        'note',
        'user_id',
        'image'
    ];
    
    protected $casts = [
        'volume_masuk' => 'float',
        'volume_keluar' => 'float',
        'balance_after' => 'float',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
