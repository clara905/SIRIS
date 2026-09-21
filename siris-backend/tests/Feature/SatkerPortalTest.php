<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\PortalNotification;
use App\Models\RiskAssessment;
use App\Models\RiskSubmission;
use App\Models\RiskTreatment;
use App\Models\Threat;
use App\Models\User;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SatkerPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $satker;

    private User $peer;

    private User $other;

    private User $kasub;

    private Asset $asset;

    private Threat $threat;

    private Vulnerability $vulnerability;

    protected function setUp(): void
    {
        parent::setUp();
        $role = DB::table('roles')->insertGetId(['name' => 'satker']);
        $kasubRole = DB::table('roles')->insertGetId(['name' => 'kasub']);
        $bidang = DB::table('bidangs')->insertGetId(['nama_bidang' => 'TI']);
        $sub = DB::table('sub_bidangs')->insertGetId(['nama_sub_bidang' => 'Infrastruktur', 'bidang_id' => $bidang]);
        $unit = DB::table('satkers')->insertGetId(['kode_satker' => 'A', 'nama_satker' => 'Unit A', 'bidang_id' => $bidang]);
        $otherUnit = DB::table('satkers')->insertGetId(['kode_satker' => 'B', 'nama_satker' => 'Unit B', 'bidang_id' => $bidang]);
        $attributes = ['role_id' => $role, 'bidang_id' => $bidang, 'sub_bidang_id' => $sub, 'satker_id' => $unit, 'is_active' => true];
        $this->satker = User::factory()->create($attributes);
        $this->peer = User::factory()->create($attributes);
        $this->other = User::factory()->create([...$attributes, 'satker_id' => $otherUnit]);
        $this->kasub = User::factory()->create([...$attributes, 'role_id' => $kasubRole]);
        $this->asset = Asset::create(['kode_aset' => 'TEST-ASSET', 'nama_aset' => 'Server', 'kategori' => 'physical', 'kondisi' => 'baik', 'jumlah' => 1, 'bidang_id' => $bidang, 'sub_bidang_id' => $sub, 'satker_id' => $unit, 'created_by' => $this->satker->id]);
        $this->threat = Threat::create(['kode_ancaman' => 'T1', 'nama_ancaman' => 'Malware', 'asset_id' => $this->asset->id, 'is_active' => true]);
        $this->vulnerability = Vulnerability::create(['nama_kerentanan' => 'Belum patch', 'is_active' => true]);
        $this->vulnerability->threats()->attach($this->threat);
        Sanctum::actingAs($this->satker);
    }

    public function test_satker_can_add_scoped_threat_and_use_it_in_submission(): void
    {
        $input = ['asset_id' => $this->asset->id, 'nama_ancaman' => 'Gangguan listrik', 'deskripsi' => 'Pasokan terputus', 'dibuat_oleh' => $this->other->id, 'is_active' => false];
        $id = $this->postJson('/api/satker/threats', $input)->assertCreated()
            ->assertJsonPath('data.dibuat_oleh', $this->satker->id)
            ->assertJsonPath('data.is_active', true)->assertJsonPath('data.vulnerabilities', [])->json('data.id');
        $this->getJson('/api/satker/options?asset_id='.$this->asset->id)->assertOk()->assertJsonFragment(['nama_ancaman' => 'Gangguan listrik']);
        $submission = [...$this->payload(), 'threat_ids' => [$id], 'vulnerability_ids' => [], 'new_vulnerabilities' => [['nama' => 'Tidak ada UPS', 'threat_ids' => [$id]]]];
        $reportId = $this->postJson('/api/satker/pengajuan', $submission)->assertCreated()->assertJsonPath('data.threats.0.id', $id)->json('data.id');
        $this->getJson('/api/satker/pengajuan/'.$reportId)->assertOk()->assertJsonPath('data.threats.0.id', $id);
        $this->postJson('/api/satker/threats', [...$input, 'nama_ancaman' => ' '])->assertUnprocessable();
        Sanctum::actingAs($this->other);
        $this->postJson('/api/satker/threats', $input)->assertNotFound();
        Sanctum::actingAs($this->kasub);
        $this->postJson('/api/satker/threats', $input)->assertForbidden();
    }

    private function payload(string $action = 'submit'): array
    {
        return ['asset_id' => $this->asset->id, 'threat_ids' => [$this->threat->id], 'vulnerability_ids' => [$this->vulnerability->id], 'new_vulnerabilities' => [], 'action' => $action, 'catatan' => 'Periksa asset'];
    }

    public function test_asset_scope_ownership_and_forged_ownership(): void
    {
        $this->getJson('/api/satker/assets')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.permissions.can_edit', true);
        $this->getJson('/api/satker/sub-bidang')->assertOk()->assertJsonPath('data.id', $this->satker->sub_bidang_id);
        $input = ['nama_aset' => 'Backup', 'kategori' => 'digital', 'kondisi' => 'baik', 'deskripsi' => 'Uji', 'created_by' => $this->other->id, 'satker_id' => $this->other->satker_id, 'bidang_id' => 999, 'sub_bidang_id' => 999];
        $id = $this->postJson('/api/satker/assets', $input)->assertCreated()->assertJsonPath('data.created_by', $this->satker->id)->assertJsonPath('data.satker_id', $this->satker->satker_id)->json('data.id');
        $this->putJson('/api/satker/assets/'.$id, [...$input, 'nama_aset' => 'Backup 2'])->assertOk()->assertJsonPath('data.permissions.can_edit', true);
        $this->deleteJson('/api/satker/assets/'.$id)->assertStatus(405);
        Sanctum::actingAs($this->peer);
        $this->getJson('/api/satker/assets/'.$id)->assertOk()->assertJsonPath('data.permissions.can_edit', false);
        $this->putJson('/api/satker/assets/'.$id, $input)->assertForbidden();
        Sanctum::actingAs($this->other);
        $this->getJson('/api/satker/assets')->assertOk()->assertJsonPath('data.total', 0);
        $this->getJson('/api/satker/assets/'.$id)->assertNotFound();
        $this->getJson('/api/satker/options?asset_id='.$id)->assertNotFound();
        $this->postJson('/api/satker/pengajuan', $this->payload())->assertNotFound();
        $this->getJson('/api/admin/users')->assertForbidden();
        $this->satker->update(['is_active' => false]);
        Sanctum::actingAs($this->satker->fresh());
        $this->getJson('/api/satker/assets')->assertForbidden();
    }

    public function test_missing_assignment_denies_access_and_subdivision_isolation(): void
    {
        $this->satker->update(['sub_bidang_id' => null]);
        Sanctum::actingAs($this->satker->fresh());
        $this->getJson('/api/satker/dashboard')->assertForbidden();
        $this->getJson('/api/satker/assets')->assertForbidden();
        $sub = DB::table('sub_bidangs')->insertGetId(['nama_sub_bidang' => 'Lain', 'bidang_id' => $this->peer->bidang_id]);
        $this->peer->update(['sub_bidang_id' => $sub]);
        Sanctum::actingAs($this->peer->fresh());
        $this->getJson('/api/satker/assets/'.$this->asset->id)->assertNotFound();
    }

    public function test_submission_draft_validation_and_visibility(): void
    {
        $id = $this->postJson('/api/satker/pengajuan', [...$this->payload('draft'), 'threat_ids' => [], 'vulnerability_ids' => [], 'created_by' => $this->other->id, 'status' => 'disetujui'])->assertCreated()->assertJsonPath('data.status', 'draft')->assertJsonPath('data.created_by', $this->satker->id)->json('data.id');
        $this->postJson('/api/satker/pengajuan', [...$this->payload(), 'threat_ids' => []])->assertUnprocessable();
        $otherVulnerability = Vulnerability::create(['nama_kerentanan' => 'Lain', 'is_active' => true]);
        $this->postJson('/api/satker/pengajuan', [...$this->payload(), 'vulnerability_ids' => [$otherVulnerability->id]])->assertUnprocessable();
        $this->putJson('/api/satker/pengajuan/'.$id, [...$this->payload(), 'vulnerability_ids' => [], 'new_vulnerabilities' => [['nama' => 'Celah baru', 'deskripsi' => 'Uji', 'threat_ids' => [$this->threat->id]]]])->assertOk()->assertJsonPath('data.status', 'diajukan');
        $this->assertDatabaseCount('vulnerabilities', 2);
        $this->putJson('/api/satker/pengajuan/'.$id, $this->payload())->assertStatus(409);
        $notification = $this->getJson('/api/satker/notifications')->assertOk()->assertJsonPath('data.unread', 1)->json('data.items.data.0.id');
        Sanctum::actingAs($this->peer);
        $this->getJson('/api/satker/pengajuan/'.$id)->assertOk();
        $this->getJson('/api/satker/pengajuan')->assertOk()->assertJsonPath('data.total', 0);
        $this->patchJson('/api/satker/notifications/'.$notification.'/read')->assertNotFound();
        $this->patchJson('/api/satker/notifications/read-all')->assertOk();
        $this->assertDatabaseHas('portal_notifications', ['id' => $notification, 'read_at' => null]);
        Sanctum::actingAs($this->satker);
        $this->patchJson('/api/satker/notifications/'.$notification.'/read')->assertOk();
        $this->getJson('/api/satker/notifications')->assertOk()->assertJsonPath('data.unread', 0);
    }

    public function test_full_review_assessment_and_approved_treatment_flow(): void
    {
        $id = $this->postJson('/api/satker/pengajuan', $this->payload())->assertCreated()->json('data.id');
        $this->patchJson('/api/kasub/pengajuan/'.$id.'/review', ['status' => 'disetujui'])->assertForbidden();
        Sanctum::actingAs($this->kasub);
        $this->patchJson('/api/kasub/pengajuan/'.$id.'/review', ['status' => 'disetujui'])->assertStatus(409);
        $this->patchJson('/api/kasub/pengajuan/'.$id.'/review', ['status' => 'ditinjau'])->assertOk();
        $this->patchJson('/api/kasub/pengajuan/'.$id.'/review', ['status' => 'perlu_perbaikan', 'reviewer_note' => 'Lengkapi kerentanan'])->assertOk();
        Sanctum::actingAs($this->satker);
        $this->getJson('/api/satker/dashboard')->assertOk()->assertJsonPath('data.summary.needs_revision', 1);
        $this->putJson('/api/satker/pengajuan/'.$id, $this->payload())->assertOk()->assertJsonPath('data.status', 'diajukan');
        Sanctum::actingAs($this->kasub);
        $this->patchJson('/api/kasub/pengajuan/'.$id.'/review', ['status' => 'ditinjau'])->assertOk();
        $this->patchJson('/api/kasub/pengajuan/'.$id.'/review', ['status' => 'disetujui'])->assertOk();
        $riskId = $this->postJson('/api/kasub/pengajuan/'.$id.'/assessment', ['likelihood' => 3, 'impact' => 4, 'skor' => 1])->assertCreated()->assertJsonPath('data.skor', 12)->json('data.id');
        $this->postJson('/api/kasub/pengajuan/'.$id.'/assessment', ['likelihood' => 3, 'impact' => 4])->assertStatus(409);
        $treatmentId = $this->postJson('/api/kasub/pengajuan/'.$id.'/treatment', ['strategi' => 'mitigasi', 'catatan' => 'Pasang patch'])->assertCreated()->assertJsonPath('data.approved_by', $this->kasub->id)->assertJsonPath('data.pic', $this->satker->id)->json('data.id');
        Sanctum::actingAs($this->peer);
        $this->patchJson('/api/satker/treatments/'.$treatmentId.'/progress', ['status' => 'selesai', 'catatan' => 'Selesai'])->assertForbidden();
        Sanctum::actingAs($this->other);
        $this->getJson('/api/satker/risk-assessments/'.$riskId)->assertOk();
        Sanctum::actingAs($this->satker);
        $this->getJson('/api/satker/risk-assessments/'.$riskId)->assertOk()->assertJsonPath('data.level', 'tinggi');
        $this->putJson('/api/satker/risk-assessments/'.$riskId, ['skor' => 1])->assertStatus(405);
        $this->getJson('/api/satker/dashboard')->assertOk()->assertJsonPath('data.summary.problem_assets', 1);
        $this->patchJson('/api/satker/treatments/'.$treatmentId.'/progress', ['status' => 'selesai', 'catatan' => 'Patch terpasang', 'strategi' => 'terima'])->assertOk()->assertJsonPath('data.strategi', 'mitigasi');
        $this->getJson('/api/satker/pengajuan/'.$id)->assertOk()->assertJsonPath('data.status', 'selesai');
        $this->getJson('/api/satker/dashboard')->assertOk()->assertJsonPath('data.summary.problem_assets', 0);
        $this->getJson('/api/satker/assets?risk=none')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson('/api/satker/pengajuan?history=1')->assertOk()->assertJsonPath('data.total', 1);
        $this->assertGreaterThan(4, PortalNotification::where('user_id', $this->satker->id)->count());
    }

    public function test_treatment_requires_approval_and_assigned_owner(): void
    {
        $risk = RiskAssessment::create(['asset_id' => $this->asset->id, 'threat_id' => $this->threat->id, 'likelihood' => 2, 'impact' => 2, 'skor' => 4, 'level' => 'rendah', 'tanggal_penilaian' => now()]);
        $treatment = RiskTreatment::create(['risk_assessment_id' => $risk->id, 'strategi' => 'mitigasi', 'pic' => $this->satker->id, 'status' => 'belum']);
        $this->patchJson('/api/satker/treatments/'.$treatment->id.'/progress', ['status' => 'selesai', 'catatan' => 'Uji', 'approved_by' => $this->kasub->id])->assertForbidden();
        $this->assertDatabaseHas('risk_treatments', ['id' => $treatment->id, 'status' => 'belum', 'approved_by' => null]);
        $this->getJson('/api/satker/assets?risk=active')->assertOk()->assertJsonPath('data.total', 1);
    }

    public function test_reviewer_scope_and_relocated_assets_are_not_exposed(): void
    {
        $id = $this->postJson('/api/satker/pengajuan', $this->payload())->assertCreated()->json('data.id');
        $sub = DB::table('sub_bidangs')->insertGetId(['nama_sub_bidang' => 'Lain', 'bidang_id' => $this->satker->bidang_id]);
        $this->kasub->update(['sub_bidang_id' => $sub]);
        Sanctum::actingAs($this->kasub->fresh());
        $this->getJson('/api/kasub/pengajuan')->assertOk()->assertJsonPath('data.total', 0);
        $this->patchJson('/api/kasub/pengajuan/'.$id.'/review', ['status' => 'ditinjau'])->assertNotFound();
        $this->asset->update(['satker_id' => $this->other->satker_id, 'sub_bidang_id' => $sub]);
        Sanctum::actingAs($this->satker);
        $this->getJson('/api/satker/pengajuan/'.$id)->assertNotFound();
    }

    public function test_invalid_threats_and_new_vulnerabilities_are_rejected(): void
    {
        $this->postJson('/api/satker/pengajuan', [...$this->payload(), 'threat_ids' => 'bad'])->assertUnprocessable();
        $this->postJson('/api/satker/pengajuan', [...$this->payload(), 'new_vulnerabilities' => [['nama' => 'Celah', 'threat_ids' => [99999]]]])->assertUnprocessable();
        $this->threat->update(['is_active' => false]);
        $this->postJson('/api/satker/pengajuan', $this->payload())->assertUnprocessable();
        $this->getJson('/api/satker/options?asset_id='.$this->asset->id)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_existing_unassigned_asset_in_same_subdivision_is_visible_read_only(): void
    {
        $this->asset->update(['satker_id' => null, 'created_by' => null, 'lokasi' => 'Jementa']);
        $this->getJson('/api/satker/assets')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.lokasi', 'Jementa')->assertJsonPath('data.data.0.permissions.can_edit', false);
        $this->getJson('/api/satker/assets/'.$this->asset->id)->assertOk();
        $this->getJson('/api/satker/dashboard')->assertOk()->assertJsonPath('data.summary.total_assets', 1);
        $this->putJson('/api/satker/assets/'.$this->asset->id, ['nama_aset' => 'Diubah', 'kategori' => 'physical', 'kondisi' => 'baik'])->assertForbidden();
        $this->postJson('/api/satker/pengajuan', $this->payload())->assertCreated()->assertJsonPath('data.satker_id', $this->satker->satker_id);
        $sub = DB::table('sub_bidangs')->insertGetId(['nama_sub_bidang' => 'Sub lain', 'bidang_id' => $this->satker->bidang_id]);
        $this->asset->update(['sub_bidang_id' => $sub]);
        $this->getJson('/api/satker/assets/'.$this->asset->id)->assertNotFound();
    }

    public function test_shared_submissions_hide_drafts_and_disallow_peer_edits(): void
    {
        $draft = $this->postJson('/api/satker/pengajuan', $this->payload('draft'))->assertCreated()->json('data.id');
        $sent = $this->postJson('/api/satker/pengajuan', $this->payload())->assertCreated()->json('data.id');
        Sanctum::actingAs($this->other);
        $this->getJson('/api/satker/pengajuan?scope=subbidang')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.data.0.id', $sent)->assertJsonPath('data.data.0.creator.id', $this->satker->id);
        $this->getJson('/api/satker/pengajuan?scope=mine')->assertOk()->assertJsonPath('data.total', 0);
        $this->getJson('/api/satker/pengajuan/'.$draft)->assertNotFound();
        $this->getJson('/api/satker/pengajuan/'.$sent)->assertOk();
        RiskSubmission::findOrFail($sent)->update(['status' => 'perlu_perbaikan']);
        Sanctum::actingAs($this->peer);
        $this->putJson('/api/satker/pengajuan/'.$sent, $this->payload())->assertNotFound();
        $this->getJson('/api/satker/pengajuan?scope=subbidang&status=draft')->assertOk()->assertJsonPath('data.total', 0);
        Sanctum::actingAs($this->satker);
        $this->getJson('/api/satker/pengajuan?scope=mine')->assertOk()->assertJsonPath('data.total', 2);
        $this->putJson('/api/satker/pengajuan/'.$sent, $this->payload())->assertOk();
        $sub = DB::table('sub_bidangs')->insertGetId(['nama_sub_bidang' => 'Terpisah', 'bidang_id' => $this->other->bidang_id]);
        $this->other->update(['sub_bidang_id' => $sub]);
        Sanctum::actingAs($this->other->fresh());
        $this->getJson('/api/satker/pengajuan?scope=subbidang')->assertOk()->assertJsonPath('data.total', 0);
        $this->getJson('/api/satker/pengajuan/'.$sent)->assertNotFound();
    }
}
