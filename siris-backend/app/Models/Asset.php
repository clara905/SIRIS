<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'jumlah',
        'kategori',
        'bidang_id',
        'sub_bidang_id',
        'satker_id',
        'deskripsi',
        'nilai_kekritisan',
        'idx',
        'kondisi',
        'merk',
        'snumber',
        'pengadaan',
        'lokasi',
    ];

    protected $appends = ['noinven', 'nama', 'stock', 'keterangan'];

    protected $hidden = ['pengadaan_lama'];

    protected function noinven(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->kode_aset);
    }

    protected function nama(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->nama_aset);
    }

    protected function stock(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->jumlah);
    }

    protected function keterangan(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->deskripsi);
    }

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'idx' => 'integer',
            'pengadaan' => 'date:Y-m-d',
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
