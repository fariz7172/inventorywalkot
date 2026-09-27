<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rab extends Model
{
    use HasFactory;

    protected $fillable = ['lokasi', 'nomor_spt', 'kecamatan_id', 'user_id', 'is_locked'];

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'material_rab')->withPivot('target_volume')->withTimestamps();
    }

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
