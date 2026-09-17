<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bidang extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_bidang',
        'kode_bidang',
    ];

    public function subBidangs(): HasMany
    {
        return $this->hasMany(SubBidang::class);
    }

    public function satkers(): HasMany
    {
        return $this->hasMany(Satker::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}