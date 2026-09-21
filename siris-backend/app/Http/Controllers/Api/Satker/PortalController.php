<?php

namespace App\Http\Controllers\Api\Satker;

use App\Http\Controllers\Controller;
use App\Http\Requests\RiskSubmissionRequest;
use App\Http\Requests\SatkerAssetRequest;
use App\Models\Asset;
use App\Models\PortalNotification;
use App\Models\RiskAssessment;
use App\Models\RiskSubmission;
use App\Models\RiskTreatment;
use App\Models\Threat;
use App\Services\SatkerPortal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PortalController extends Controller
{
    private function response(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data], $status);
    }

    private function assetsQuery(Request $request): Builder
    {
        return SatkerPortal::scope(Asset::query(), $request->user());
    }

    private function reportableAssetsQuery(Request $request): Builder
    {
        return $this->assetsQuery($request)->where(fn (Builder $query) => $query->where('satker_id', $request->user()->satker_id)->orWhereNull('satker_id'));
    }

    private function submissionsQuery(Request $request): Builder
    {
        SatkerPortal::assigned($request->user());
        $user = $request->user();

        return RiskSubmission::where('bidang_id', $user->bidang_id)->where('sub_bidang_id', $user->sub_bidang_id)
            ->where(fn (Builder $query) => $query->where('created_by', $user->id)->orWhere('status', '!=', 'draft'))
            ->whereHas('asset', fn (Builder $query) => $query->where('bidang_id', $user->bidang_id)->where('sub_bidang_id', $user->sub_bidang_id));
    }

    private function assessmentQuery(Request $request): Builder
    {
        return RiskAssessment::where(function (Builder $query) use ($request) {
            $query->whereHas('asset', fn (Builder $query) => SatkerPortal::scope($query, $request->user()))
                ->orWhereIn('risk_submission_id', $this->submissionsQuery($request)->select('id')->where('status', '!=', 'draft'));
        });
    }

    private function relations(): array
    {
        return ['creator:id,name', 'satker:id,nama_satker', 'asset', 'threats', 'vulnerabilities', 'assessment.dinilaiOleh:id,name', 'assessment.treatments'];
    }

    public function dashboard(Request $request): JsonResponse
    {
        $assets = $this->assetsQuery($request);
        $submissions = $this->submissionsQuery($request)->where('created_by', $request->user()->id);
        $problem = (clone $assets)->whereHas('riskAssessments', SatkerPortal::activeRisks(...));

        return $this->response([
            'summary' => ['total_assets' => (clone $assets)->count(), 'active_submissions' => (clone $submissions)->whereIn('status', ['diajukan', 'ditinjau', 'perlu_perbaikan', 'disetujui'])->count(), 'needs_revision' => (clone $submissions)->where('status', 'perlu_perbaikan')->count(), 'problem_assets' => (clone $problem)->count()],
            'attention_treatments' => RiskTreatment::where('pic', $request->user()->id)->whereNotNull('approved_at')->whereNotNull('approved_by')->where('status', '!=', 'selesai')->whereHas('riskAssessment.asset', fn (Builder $query) => SatkerPortal::scope($query, $request->user()))->with('riskAssessment.asset')->latest()->limit(5)->get(),
            'attention' => (clone $submissions)->whereIn('status', ['perlu_perbaikan', 'ditolak'])->with($this->relations())->latest()->limit(5)->get(),
            'recent' => (clone $submissions)->with($this->relations())->latest()->limit(5)->get(),
            'problem_assets' => $problem->latest()->limit(5)->get()->map(fn (Asset $asset) => SatkerPortal::assetData($asset, $request->user())),
        ]);
    }

    public function subBidang(Request $request): JsonResponse
    {
        SatkerPortal::assigned($request->user());

        return $this->response($request->user()->subBidang->load('bidang'));
    }

    public function assets(Request $request): JsonResponse
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', Rule::in(['active', 'inactive'])], 'kondisi' => ['nullable', 'string', 'max:100'], 'risk' => ['nullable', Rule::in(['active', 'none'])]]);
        $query = $request->input('scope') === 'reportable' ? $this->reportableAssetsQuery($request) : $this->assetsQuery($request);
        if ($request->filled('search')) {
            $query->where(fn (Builder $query) => $query->where('nama_aset', 'like', '%'.$request->string('search').'%')->orWhere('kode_aset', 'like', '%'.$request->string('search').'%'));
        }
        foreach (['status', 'kondisi'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        if ($request->input('risk') === 'active') {
            $query->whereHas('riskAssessments', SatkerPortal::activeRisks(...));
        }
        if ($request->input('risk') === 'none') {
            $query->whereDoesntHave('riskAssessments', SatkerPortal::activeRisks(...));
        }

        return $this->response($query->latest()->paginate(12)->through(fn (Asset $asset) => SatkerPortal::assetData($asset, $request->user())));
    }

    public function asset(Request $request, int $id): JsonResponse
    {
        $asset = $this->assetsQuery($request)->findOrFail($id);
        Gate::authorize('satker-view-asset', $asset);
        $asset->load(['threats' => fn ($query) => $query->with('vulnerabilities'), 'riskAssessments.dinilaiOleh:id,name']);

        return $this->response(SatkerPortal::assetData($asset, $request->user()));
    }

    public function storeAsset(SatkerAssetRequest $request): JsonResponse
    {
        SatkerPortal::assigned($request->user());
        $user = $request->user();
        $asset = Asset::create([...$request->validated(), 'kode_aset' => 'AST-'.Str::uuid(), 'jumlah' => $request->validated('jumlah', 1), 'lokasi' => $user->satker->nama_satker, 'status' => 'active', 'bidang_id' => $user->bidang_id, 'sub_bidang_id' => $user->sub_bidang_id, 'satker_id' => $user->satker_id, 'created_by' => $user->id]);

        return $this->response(SatkerPortal::assetData($asset, $user), 201);
    }

    public function updateAsset(SatkerAssetRequest $request, int $id): JsonResponse
    {
        $asset = $this->assetsQuery($request)->findOrFail($id);
        Gate::authorize('satker-edit-asset', $asset);
        $asset->update($request->validated());

        return $this->response(SatkerPortal::assetData($asset, $request->user()));
    }

    public function storeThreat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_id' => ['required', 'integer'],
            'nama_ancaman' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
        ]);
        $asset = $this->reportableAssetsQuery($request)->findOrFail($data['asset_id']);
        $threat = Threat::create([
            'asset_id' => $asset->id,
            'nama_ancaman' => $data['nama_ancaman'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'kode_ancaman' => 'THR-'.Str::uuid(),
            'dibuat_oleh' => $request->user()->id,
            'is_active' => true,
        ]);

        return $this->response($threat->load('vulnerabilities'), 201);
    }

    public function options(Request $request): JsonResponse
    {
        $request->validate(['asset_id' => ['required', 'integer']]);
        $asset = $this->assetsQuery($request)->findOrFail($request->integer('asset_id'));
        $threats = Threat::where('is_active', true)->where(fn (Builder $query) => $query->whereNull('asset_id')->orWhere('asset_id', $asset->id))->with(['vulnerabilities' => fn ($query) => $query->where('is_active', true)])->orderBy('nama_ancaman')->get();

        return $this->response($threats);
    }

    public function submissions(Request $request): JsonResponse
    {
        $request->validate(['status' => ['nullable', Rule::in(['draft', 'diajukan', 'ditinjau', 'perlu_perbaikan', 'disetujui', 'ditolak', 'selesai'])], 'history' => ['nullable', 'boolean'], 'scope' => ['nullable', Rule::in(['mine', 'subbidang'])]]);
        $query = $this->submissionsQuery($request);
        if ($request->input('scope', 'mine') === 'mine') {
            $query->where('created_by', $request->user()->id);
        } else {
            $query->where('status', '!=', 'draft');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->boolean('history')) {
            $query->whereIn('status', ['selesai', 'ditolak']);
        }

        return $this->response($query->with($this->relations())->latest('updated_at')->paginate(12));
    }

    public function submission(Request $request, int $id): JsonResponse
    {
        return $this->response($this->submissionsQuery($request)->with($this->relations())->findOrFail($id));
    }

    public function storeSubmission(RiskSubmissionRequest $request): JsonResponse
    {
        return $this->saveSubmission($request);
    }

    public function updateSubmission(RiskSubmissionRequest $request, int $id): JsonResponse
    {
        return $this->saveSubmission($request, $id);
    }

    private function saveSubmission(RiskSubmissionRequest $request, ?int $id = null): JsonResponse
    {
        $this->reportableAssetsQuery($request)->findOrFail($request->integer('asset_id'));
        $submission = DB::transaction(function () use ($request, $id) {
            $user = $request->user();
            $submission = $id ? $this->submissionsQuery($request)->where('created_by', $user->id)->lockForUpdate()->findOrFail($id) : new RiskSubmission;
            abort_if($id && ! in_array($submission->status, ['draft', 'perlu_perbaikan']), 409, 'Pengajuan ini tidak dapat diedit pada status saat ini.');
            $input = $request->validated();
            $status = $input['action'] === 'submit' ? 'diajukan' : ($submission->status === 'perlu_perbaikan' ? 'perlu_perbaikan' : 'draft');
            $submission->fill(['asset_id' => $input['asset_id'], 'status' => $status, 'catatan' => $input['catatan'] ?? null, 'new_vulnerabilities' => $input['new_vulnerabilities']]);
            if (! $id) {
                $submission->fill(['created_by' => $user->id, 'bidang_id' => $user->bidang_id, 'sub_bidang_id' => $user->sub_bidang_id, 'satker_id' => $user->satker_id, 'timeline' => [['status' => 'draft', 'label' => 'Pengajuan dibuat', 'at' => now()->toIso8601String()]]]);
            }
            $submission->save();
            $submission->threats()->sync($input['threat_ids']);
            $submission->vulnerabilities()->sync($input['vulnerability_ids']);
            if ($status === 'diajukan') {
                SatkerPortal::event($submission, 'diajukan', 'Pengajuan dikirim');
                SatkerPortal::notify($user->id, 'Pengajuan berhasil dikirim', 'Pengajuan menunggu review Kasub.', '/satker/pengajuan/'.$submission->id);
            }

            return $submission;
        });

        return $this->response($submission->load($this->relations()), $id ? 200 : 201);
    }

    public function assessments(Request $request): JsonResponse
    {
        return $this->response($this->assessmentQuery($request)->with(['asset', 'threat', 'threats', 'vulnerabilities', 'dinilaiOleh:id,name', 'treatments'])->latest()->paginate(12));
    }

    public function assessment(Request $request, int $id): JsonResponse
    {
        return $this->response($this->assessmentQuery($request)->with(['asset', 'threat', 'threats', 'vulnerabilities', 'dinilaiOleh:id,name', 'treatments'])->findOrFail($id));
    }

    public function notifications(Request $request): JsonResponse
    {
        $query = PortalNotification::where('user_id', $request->user()->id);

        return $this->response(['unread' => (clone $query)->whereNull('read_at')->count(), 'items' => $query->latest()->paginate(15)]);
    }

    public function readNotification(Request $request, int $id): JsonResponse
    {
        $notification = PortalNotification::where('user_id', $request->user()->id)->findOrFail($id);
        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return $this->response($notification);
    }

    public function readAll(Request $request): JsonResponse
    {
        PortalNotification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return $this->response(null);
    }

    public function treatment(Request $request, int $id): JsonResponse
    {
        $input = $request->validate(['status' => ['required', Rule::in(['proses', 'selesai'])], 'catatan' => ['required', 'string', 'max:10000']]);
        $treatment = DB::transaction(function () use ($request, $id, $input) {
            $treatment = RiskTreatment::whereHas('riskAssessment.asset', fn (Builder $query) => SatkerPortal::scope($query, $request->user()))->lockForUpdate()->findOrFail($id);
            abort_unless($treatment->approved_by && $treatment->approved_at && (int) $treatment->pic === $request->user()->id, 403, 'Treatment belum disetujui atau tidak ditugaskan kepada Anda.');
            abort_if($treatment->status === 'selesai', 409, 'Treatment sudah selesai.');
            $treatment->update($input);

            return $treatment;
        });

        return $this->response($treatment);
    }
}
