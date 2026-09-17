<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskTreatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'risk_assessment_id',
        'strategi',
        'pic',
        'target_selesai',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'target_selesai' => 'date',
        ];
    }

    public function riskAssessment(): BelongsTo
    {
        return $this->belongsTo(
            RiskAssessment::class
        );
    }

    public function picUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'pic'
        );
    }
}