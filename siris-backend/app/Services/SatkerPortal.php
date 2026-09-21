<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\PortalNotification;
use App\Models\RiskAssessment;
use App\Models\RiskSubmission;
use App\Models\RiskTreatment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class SatkerPortal
{
    public static function assigned(User $user): void
    {
        abort_unless($user->bidang_id && $user->sub_bidang_id && $user->satker_id, 403, 'Penempatan organisasi belum lengkap. Hubungi admin.');
        abort_unless($user->subBidang && (int) $user->subBidang->bidang_id === (int) $user->bidang_id && $user->satker && (! $user->satker->bidang_id || (int) $user->satker->bidang_id === (int) $user->bidang_id), 403, 'Penempatan organisasi tidak sesuai. Hubungi admin.');
    }

    public static function scope(Builder $query, User $user): Builder
    {
        self::assigned($user);

        $query->where('bidang_id', $user->bidang_id)->where('sub_bidang_id', $user->sub_bidang_id);
        if ($query->getModel() instanceof Asset) {
            return $query;
        }

        return $query->where('satker_id', $user->satker_id);
    }

    public static function activeRisks(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query->whereDoesntHave('treatments')->orWhereHas('treatments', fn (Builder $query) => $query->where('status', '!=', 'selesai'));
        });
    }

    public static function assetData(Asset $asset, User $user): array
    {
        $asset->loadMissing(['creator:id,name', 'bidang', 'subBidang', 'satker', 'riskAssessments.threats', 'riskAssessments.threat', 'riskAssessments.vulnerabilities', 'riskAssessments.treatments']);
        $risks = $asset->riskAssessments->filter(fn (RiskAssessment $risk) => $risk->treatments->isEmpty() || $risk->treatments->contains(fn (RiskTreatment $treatment) => $treatment->status !== 'selesai'));
        $highest = $risks->sortByDesc('skor')->first();

        return [...$asset->toArray(), 'has_active_risk' => $risks->isNotEmpty(), 'risk_level' => $highest?->level, 'permissions' => ['can_view' => true, 'can_edit' => Gate::forUser($user)->allows('satker-edit-asset', $asset)]];
    }

    public static function notify(int $userId, string $title, string $message, string $url): void
    {
        PortalNotification::create(['user_id' => $userId, 'title' => $title, 'message' => $message, 'url' => $url]);
    }

    public static function assessmentChanged(RiskAssessment $risk): void
    {
        $asset = $risk->asset;
        if (! $asset) {
            return;
        }
        $users = User::where('is_active', true)->whereHas('role', fn (Builder $query) => $query->where('name', 'satker'))->where('sub_bidang_id', $asset->sub_bidang_id)->where('satker_id', $asset->satker_id)->where('bidang_id', $asset->bidang_id);
        if (! $asset->satker_id || ! $asset->sub_bidang_id) {
            return;
        }
        foreach ($users->get() as $user) {
            self::notify($user->id, 'Risk assessment tersedia', $asset->nama_aset.' memiliki risiko '.$risk->level.'.', '/satker/risk-assessments/'.$risk->id);
            self::notify($user->id, 'Asset memiliki risiko aktif', 'Pantau tindak lanjut untuk '.$asset->nama_aset.'.', '/satker/assets/'.$asset->id);
        }
        if ($risk->risk_submission_id && $risk->wasRecentlyCreated) {
            $submission = $risk->submission;
            if ($submission) {
                self::event($submission, 'assessment', 'Risk assessment selesai');
            }
        }
    }

    public static function event(RiskSubmission $submission, string $status, string $label): void
    {
        $timeline = $submission->timeline ?? [];
        $timeline[] = ['status' => $status, 'label' => $label, 'at' => now()->toIso8601String()];
        $submission->timeline = $timeline;
        $submission->save();
    }

    public static function treatmentChanged(RiskTreatment $treatment): void
    {
        if (! $treatment->wasChanged('status') && ! $treatment->wasRecentlyCreated) {
            return;
        }
        $risk = $treatment->riskAssessment;
        $asset = $risk?->asset;
        if ($asset && $asset->satker_id && $asset->sub_bidang_id && $treatment->status === 'selesai') {
            $users = User::where('is_active', true)->whereHas('role', fn (Builder $query) => $query->where('name', 'satker'))->where('bidang_id', $asset->bidang_id)->where('sub_bidang_id', $asset->sub_bidang_id)->where('satker_id', $asset->satker_id)->get();
            foreach ($users as $user) {
                self::notify($user->id, 'Risk treatment selesai', 'Treatment untuk '.$asset->nama_aset.' telah selesai.', '/satker/risk-assessments/'.$risk->id);
            }
        }
        $submission = $risk?->submission;
        if (! $submission) {
            return;
        }
        self::event($submission, 'treatment', 'Treatment: '.$treatment->status);
        self::notify($submission->created_by, 'Progres treatment', 'Status treatment: '.$treatment->status, '/satker/pengajuan/'.$submission->id);
        if ($risk->treatments()->where('status', '!=', 'selesai')->doesntExist()) {
            $submission->update(['status' => 'selesai']);
            self::event($submission, 'selesai', 'Risk treatment selesai');
            self::notify($submission->created_by, 'Risk treatment selesai', 'Pengajuan telah selesai ditangani.', '/satker/pengajuan/'.$submission->id);
        }
    }
}
