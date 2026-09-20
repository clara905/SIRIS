<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RiskAssessmentRequest;
use App\Models\RiskAssessment;
use App\Models\Threat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RiskAssessmentController extends Controller
{
    public function rules(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => config('risk_assessment')]);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'level' => ['nullable', Rule::in(['rendah', 'sedang', 'tinggi'])]]);
        $query = RiskAssessment::with(['asset', 'threat', 'threats', 'vulnerabilities', 'dinilaiOleh:id,name']);
        if ($request->filled('level')) {
            $query->whereIn('level', $request->level === 'tinggi' ? ['tinggi', 'sangat_tinggi', 'ekstrem'] : [$request->level]);
        }
        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->where(function ($query) use ($search) {
                $query->whereHas('asset', fn ($query) => $query->where('nama_aset', 'like', $search)->orWhere('snumber', 'like', $search))
                    ->orWhereHas('threats', fn ($query) => $query->where('nama_ancaman', 'like', $search))
                    ->orWhereHas('threat', fn ($query) => $query->where('nama_ancaman', 'like', $search));
            });
        }

        return response()->json(['success' => true, 'data' => $query->orderByDesc('tanggal_penilaian')->orderByDesc('id')->paginate(15)]);
    }

    public function threatOptions(Request $request): JsonResponse
    {
        $request->validate(['asset_id' => ['required', 'integer', 'exists:assets,id'], 'search' => ['nullable', 'string', 'max:255']]);
        $query = Threat::where('is_active', true)->where(function ($query) use ($request) {
            $query->whereNull('asset_id')->orWhere('asset_id', $request->integer('asset_id'));
        });
        if ($request->filled('search')) {
            $query->where('nama_ancaman', 'like', '%'.$request->string('search').'%');
        }

        return response()->json(['success' => true, 'data' => $query->orderBy('nama_ancaman')->paginate(15)]);
    }

    public function store(RiskAssessmentRequest $request): JsonResponse
    {
        $risk = DB::transaction(function () use ($request) {
            $risk = RiskAssessment::create($this->attributes($request));
            $risk->threats()->sync($request->input('threat_ids', [$request->integer('threat_id')]));

            $risk->vulnerabilities()->sync($request->input('vulnerability_ids', []));

            return $risk;
        });

        return response()->json(['success' => true, 'message' => 'Penilaian risiko berhasil dibuat.', 'data' => $risk->load(['asset', 'threat', 'threats', 'vulnerabilities', 'dinilaiOleh:id,name'])], 201);
    }

    public function update(RiskAssessmentRequest $request, RiskAssessment $riskAssessment): JsonResponse
    {
        DB::transaction(function () use ($request, $riskAssessment) {
            $riskAssessment->update($this->attributes($request));
            $riskAssessment->threats()->sync($request->input('threat_ids', [$request->integer('threat_id')]));
            if ($request->has('vulnerability_ids')) {
                $riskAssessment->vulnerabilities()->sync($request->input('vulnerability_ids'));
            }
        });

        return response()->json(['success' => true, 'message' => 'Penilaian risiko berhasil diperbarui.', 'data' => $riskAssessment->load(['asset', 'threat', 'threats', 'vulnerabilities', 'dinilaiOleh:id,name'])]);
    }

    private function attributes(RiskAssessmentRequest $request): array
    {
        $attributes = $request->validated();
        $attributes['threat_id'] = $attributes['threat_ids'][0] ?? $attributes['threat_id'];
        unset($attributes['threat_ids'], $attributes['vulnerability_ids']);
        $score = $attributes['likelihood'] * $attributes['impact'];
        $level = collect(config('risk_assessment.levels'))->first(fn (array $level): bool => $score >= $level['min'] && $score <= $level['max']);
        $attributes['skor'] = $score;
        $attributes['level'] = $level['key'];
        $attributes['dinilai_oleh'] = $request->user()->id;

        return $attributes;
    }
}
