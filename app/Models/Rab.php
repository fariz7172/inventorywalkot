<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rab extends Model
{
    use HasFactory;

    protected $fillable = ['lokasi'];

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'material_rab')->withPivot('target_volume')->withTimestamps();
    }
}
