<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $fillable = ['stock_opname_id', 'material_id', 'system_volume', 'physical_volume', 'difference', 'notes'];

    public function opname()
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
