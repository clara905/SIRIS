<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiskAssessment;
use App\Models\RiskSubmission;
use App\Models\RiskTreatment;
use App\Services\SatkerPortal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SubmissionReviewController extends Controller
{
    private function query(Request $request): Builder
    {
        abort_unless($request->user()->sub_bidang_id && $request->user()->bidang_id, 403);

        return RiskSubmission::where('sub_bidang_id', $request->user()->sub_bidang_id)->where('bidang_id', $request->user()->bidang_id)->whereHas('asset', fn (Builder $query) => $query->where('sub_bidang_id', $request->user()->sub_bidang_id)->where('bidang_id', $request->user()->bidang_id));
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->query($request)->where('status', '!=', 'draft')->with(['asset', 'creator:id,name', 'threats', 'vulnerabilities', 'assessment.treatments'])->latest('updated_at')->paginate(15)]);
    }

    public function review(Request $request, int $id): JsonResponse
    {
        $input = $request->validate(['status' => ['required', Rule::in(['ditinjau', 'perlu_perbaikan', 'disetujui', 'ditolak'])], 'reviewer_note' => ['required_if:status,perlu_perbaikan,ditolak', 'nullable', 'string', 'max:10000']]);
        $submission = DB::transaction(function () use ($request, $id, $input) {
            $submission = $this->query($request)->lockForUpdate()->findOrFail($id);
            $allowed = $submission->status === 'diajukan' ? ['ditinjau'] : ($submission->status === 'ditinjau' ? ['perlu_perbaikan', 'disetujui', 'ditolak'] : []);
            abort_unless(in_array($input['status'], $allowed), 409, 'Transisi status tidak diizinkan.');
            $submission->update($input);
            SatkerPortal::event($submission, $input['status'], 'Review: '.str_replace('_', ' ', $input['status']));
            SatkerPortal::notify($submission->created_by, 'Status pengajuan diperbarui', str_replace('_', ' ', $input['status']).'. '.($input['reviewer_note'] ?? ''), '/satker/pengajuan/'.$submission->id);

            return $submission;
        });

        return response()->json(['data' => $submission]);
    }

    public function assessment(Request $request, int $id): JsonResponse
    {
        $input = $request->validate(['likelihood' => ['required', 'integer', 'between:1,5'], 'impact' => ['required', 'integer', 'between:1,5'], 'catatan' => ['nullable', 'string', 'max:10000']]);
        $risk = DB::transaction(function () use ($request, $id, $input) {
            $submission = $this->query($request)->lockForUpdate()->findOrFail($id);
            abort_unless($submission->status === 'disetujui' && ! $submission->assessment, 409, 'Pengajuan belum disetujui atau sudah dinilai.');
            $score = $input['likelihood'] * $input['impact'];
            $level = collect(config('risk_assessment.levels'))->first(fn (array $band): bool => $score >= $band['min'] && $score <= $band['max']);
            $risk = RiskAssessment::create([...$input, 'risk_submission_id' => $submission->id, 'asset_id' => $submission->asset_id, 'threat_id' => $submission->threats()->firstOrFail()->id, 'skor' => $score, 'level' => $level['key'], 'tanggal_penilaian' => now(), 'dinilai_oleh' => $request->user()->id]);
            $risk->threats()->sync($submission->threats()->pluck('threats.id'));
            $risk->vulnerabilities()->sync($submission->vulnerabilities()->pluck('vulnerabilities.id'));

            return $risk;
        });

        return response()->json(['data' => $risk], 201);
    }

    public function treatment(Request $request, int $id): JsonResponse
    {
        $input = $request->validate(['strategi' => ['required', Rule::in(['mitigasi', 'transfer', 'hindari', 'terima'])], 'target_selesai' => ['nullable', 'date_format:Y-m-d'], 'catatan' => ['required', 'string', 'max:10000']]);
        $treatment = DB::transaction(function () use ($request, $id, $input) {
            $submission = $this->query($request)->lockForUpdate()->findOrFail($id);
            abort_unless($submission->status === 'disetujui' && $submission->assessment && ! $submission->assessment->treatments()->exists(), 409, 'Assessment belum tersedia atau treatment sudah dibuat.');
            $treatment = RiskTreatment::create([...$input, 'risk_assessment_id' => $submission->assessment->id, 'pic' => $submission->created_by, 'status' => 'belum', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
            SatkerPortal::notify($submission->created_by, 'Treatment disetujui Kasub', 'Anda dapat memperbarui progres treatment.', '/satker/pengajuan/'.$submission->id);

            return $treatment;
        });

        return response()->json(['data' => $treatment], 201);
    }
}
