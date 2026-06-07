<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kecamatan extends Model
{
    use HasFactory;

    protected $fillable = ['nama_kecamatan'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function rabs()
    {
        return $this->hasMany(Rab::class);
    }
}
