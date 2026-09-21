<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RiskSubmission extends Model
{
    protected $fillable = ['asset_id', 'created_by', 'bidang_id', 'sub_bidang_id', 'satker_id', 'status', 'new_vulnerabilities', 'catatan', 'reviewer_note', 'timeline'];

    protected function casts(): array
    {
        return ['new_vulnerabilities' => 'array', 'timeline' => 'array'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function threats(): BelongsToMany
    {
        return $this->belongsToMany(Threat::class);
    }

    public function vulnerabilities(): BelongsToMany
    {
        return $this->belongsToMany(Vulnerability::class);
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(RiskAssessment::class);
    }
}
