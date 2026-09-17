<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubBidang extends Model
{
    use HasFactory;

    protected $fillable = [
        'bidang_id',
        'nama_sub_bidang',
    ];

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function satkers(): HasMany
    {
        return $this->hasMany(Satker::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}