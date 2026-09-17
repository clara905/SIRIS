<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_aset',
        'nama_aset',
        'kategori',
        'bidang_id',
        'sub_bidang_id',
        'satker_id',
        'deskripsi',
        'nilai_kekritisan',
    ];

    protected function casts(): array
    {
        return [
            'nilai_kekritisan' => 'integer',
        ];
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function subBidang(): BelongsTo
    {
        return $this->belongsTo(SubBidang::class);
    }

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class);
    }

    public function threats(): HasMany
    {
        return $this->hasMany(Threat::class);
    }

    public function riskAssessments(): HasMany
    {
        return $this->hasMany(RiskAssessment::class);
    }
}