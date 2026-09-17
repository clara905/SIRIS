<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Threat extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_ancaman',
        'nama_ancaman',
        'deskripsi',
        'asset_id',
        'dibuat_oleh',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'dibuat_oleh'
        );
    }

    public function vulnerabilities(): BelongsToMany
    {
        return $this->belongsToMany(
            Vulnerability::class,
            'threat_vulnerability'
        )->withTimestamps();
    }

    public function riskAssessments(): HasMany
    {
        return $this->hasMany(RiskAssessment::class);
    }
}