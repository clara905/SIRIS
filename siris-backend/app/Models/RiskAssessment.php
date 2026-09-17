<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiskAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'threat_id',
        'likelihood',
        'impact',
        'skor',
        'level',
        'dinilai_oleh',
        'tanggal_penilaian',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'likelihood' => 'integer',
            'impact' => 'integer',
            'skor' => 'integer',
            'tanggal_penilaian' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function threat(): BelongsTo
    {
        return $this->belongsTo(Threat::class);
    }

    public function dinilaiOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'dinilai_oleh'
        );
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(RiskTreatment::class);
    }
}