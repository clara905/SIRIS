<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RiskAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private int $assetId;

    private int $threatId;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $role = DB::table('roles')->insertGetId(['name' => 'admin']);
        $this->admin = User::factory()->create(['role_id' => $role]);
        Sanctum::actingAs($this->admin);
        $this->assetId = $this->postJson('/api/admin/assets', ['nama' => 'Laptop', 'stock' => 1])->assertCreated()->json('data.id');
        $this->threatId = $this->postJson('/api/admin/threats', ['kode_ancaman' => 'TH-1', 'nama_ancaman' => 'Pencurian', 'asset_id' => $this->assetId])->assertCreated()->json('data.id');
    }

    public function test_admin_can_delete_unused_records_but_not_linked_assets_or_vulnerabilities(): void
    {
        $id = $this->postJson('/api/admin/risk-assessments', $this->payload())->assertCreated()->json('data.id');
        $this->deleteJson('/api/admin/threats/'.$this->threatId)->assertUnprocessable();
        $this->deleteJson('/api/admin/risk-assessments/'.$id)->assertOk();
        $this->assertDatabaseMissing('risk_assessments', ['id' => $id]);
        $this->assertDatabaseMissing('risk_assessment_threat', ['risk_assessment_id' => $id]);
        $this->assertDatabaseHas('threats', ['id' => $this->threatId]);
        $this->deleteJson('/api/admin/assets/'.$this->assetId)->assertUnprocessable();
        $asset = $this->postJson('/api/admin/assets', ['nama' => 'Unused', 'stock' => 1])->assertCreated()->json('data.id');
        $this->deleteJson('/api/admin/assets/'.$asset)->assertOk();
        $this->assertDatabaseMissing('assets', ['id' => $asset]);
        $vulnerability = Vulnerability::create(['nama_kerentanan' => 'Unused', 'is_active' => true]);
        $vulnerability->threats()->attach($this->threatId);
        $this->deleteJson('/api/admin/vulnerabilities/'.$vulnerability->id)->assertUnprocessable();
        $vulnerability->threats()->detach();
        $this->deleteJson('/api/admin/vulnerabilities/'.$vulnerability->id)->assertOk();
        $this->assertDatabaseMissing('vulnerabilities', ['id' => $vulnerability->id]);
        $this->deleteJson('/api/admin/threats/'.$this->threatId)->assertOk();
        $this->assertDatabaseMissing('threats', ['id' => $this->threatId]);
    }

    private function payload(): array
    {
        return ['asset_id' => $this->assetId, 'threat_id' => $this->threatId, 'likelihood' => 3, 'impact' => 4, 'tanggal_penilaian' => today()->toDateString(), 'catatan' => 'Pengujian'];
    }

    public function test_score_is_computed_by_server_and_edit_updates_dashboard(): void
    {
        $input = $this->payload();
        $response = $this->postJson('/api/admin/risk-assessments', [...$input, 'skor' => 1, 'level' => 'rendah', 'dinilai_oleh' => 999])
            ->assertCreated()->assertJsonPath('data.skor', 12)->assertJsonPath('data.level', 'tinggi')->assertJsonPath('data.dinilai_oleh.name', $this->admin->name);
        $id = $response->json('data.id');
        $this->putJson('/api/admin/risk-assessments/'.$id, [...$input, 'likelihood' => 2, 'impact' => 2])->assertOk()->assertJsonPath('data.skor', 4)->assertJsonPath('data.level', 'rendah');
        $this->assertDatabaseHas('risk_assessments', ['id' => $id, 'skor' => 4, 'dinilai_oleh' => $this->admin->id]);
        $this->getJson('/api/admin/dashboard')->assertOk()->assertJsonPath('data.summary.total_risks', 1)->assertJsonPath('data.risk_levels.rendah', 1);
        $this->getJson('/api/admin/risk-assessments?search=Laptop&level=rendah')->assertOk()->assertJsonPath('data.total', 1);
    }

    public function test_all_matrix_scores_match_policy(): void
    {
        $this->getJson('/api/admin/risk-assessments/rules')->assertOk()->assertJsonPath('data.scale_max', 5);
        foreach (range(1, 5) as $likelihood) {
            foreach (range(1, 5) as $impact) {
                $score = $likelihood * $impact;
                $level = $score <= 4 ? 'rendah' : ($score <= 9 ? 'sedang' : 'tinggi');
                $this->postJson('/api/admin/risk-assessments', [...$this->payload(), 'likelihood' => $likelihood, 'impact' => $impact])
                    ->assertCreated()->assertJsonPath('data.skor', $score)->assertJsonPath('data.level', $level);
            }
        }
    }

    public function test_invalid_scales_dates_and_mismatched_threat_are_rejected(): void
    {
        foreach ([['likelihood' => 0], ['impact' => 6], ['impact' => 1.5], ['tanggal_penilaian' => 'not-a-date'], ['tanggal_penilaian' => today()->addDay()->toDateString()]] as $change) {
            $this->postJson('/api/admin/risk-assessments', [...$this->payload(), ...$change])->assertUnprocessable()->assertJsonValidationErrors(array_keys($change));
        }
        $otherAsset = $this->postJson('/api/admin/assets', ['nama' => 'Server', 'stock' => 1])->assertCreated()->json('data.id');
        $this->postJson('/api/admin/risk-assessments', [...$this->payload(), 'asset_id' => $otherAsset])->assertUnprocessable()->assertJsonValidationErrors('threat_id');
        $this->getJson('/api/admin/risk-assessments/threat-options?asset_id='.$otherAsset)->assertOk()->assertJsonPath('data.total', 0);
        DB::table('threats')->where('id', $this->threatId)->update(['asset_id' => null]);
        $this->getJson('/api/admin/risk-assessments/threat-options?asset_id='.$otherAsset)->assertOk()->assertJsonPath('data.total', 1);
        DB::table('threats')->where('id', $this->threatId)->update(['is_active' => false]);
        $this->postJson('/api/admin/risk-assessments', $this->payload())->assertUnprocessable()->assertJsonValidationErrors('threat_id');
    }

    public function test_multiple_threats_are_saved_searched_and_replaced(): void
    {
        $second = $this->postJson('/api/admin/threats', ['kode_ancaman' => 'TH-2', 'nama_ancaman' => 'Kebakaran', 'asset_id' => $this->assetId])->assertCreated()->json('data.id');
        $input = $this->payload();
        unset($input['threat_id']);
        $input['threat_ids'] = [$this->threatId, $second];
        $id = $this->postJson('/api/admin/risk-assessments', $input)->assertCreated()->assertJsonCount(2, 'data.threats')->json('data.id');
        $this->getJson('/api/admin/risk-assessments?search=Kebakaran')->assertOk()->assertJsonPath('data.total', 1)->assertJsonCount(2, 'data.data.0.threats');
        $this->putJson('/api/admin/risk-assessments/'.$id, [...$input, 'threat_ids' => [$second]])->assertOk()->assertJsonCount(1, 'data.threats')->assertJsonPath('data.threat_id', $second);
        $this->assertDatabaseMissing('risk_assessment_threat', ['risk_assessment_id' => $id, 'threat_id' => $this->threatId]);
        $this->postJson('/api/admin/risk-assessments', [...$input, 'threat_ids' => []])->assertUnprocessable();
        $this->postJson('/api/admin/risk-assessments', [...$input, 'threat_ids' => [$second, $second]])->assertUnprocessable();
        DB::table('threats')->where('id', $second)->update(['is_active' => false]);
        $this->putJson('/api/admin/risk-assessments/'.$id, $input)->assertUnprocessable()->assertJsonValidationErrors('threat_ids.1');
        $this->assertDatabaseCount('risk_assessment_threat', 1);
    }

    public function test_vulnerability_links_are_saved_and_preserved(): void
    {
        $vulnerability = $this->postJson('/api/admin/vulnerabilities', ['nama_kerentanan' => 'Celah akses', 'threat_ids' => [$this->threatId]])->assertCreated()->assertJsonCount(1, 'data.threats')->json('data.id');
        $this->getJson('/api/admin/vulnerabilities')->assertOk()->assertJsonPath('data.data.0.threats_count', 1)->assertJsonCount(1, 'data.data.0.threats');
        $this->putJson('/api/admin/vulnerabilities/'.$vulnerability, ['nama_kerentanan' => 'Celah akses baru'])->assertOk()->assertJsonCount(1, 'data.threats');
        $input = [...$this->payload(), 'vulnerability_ids' => [$vulnerability]];
        $id = $this->postJson('/api/admin/risk-assessments', $input)->assertCreated()->assertJsonCount(1, 'data.vulnerabilities')->json('data.id');
        $this->getJson('/api/admin/risk-assessments')->assertOk()->assertJsonPath('data.data.0.vulnerabilities.0.id', $vulnerability);
        $this->putJson('/api/admin/risk-assessments/'.$id, $this->payload())->assertOk()->assertJsonCount(1, 'data.vulnerabilities');
        $this->putJson('/api/admin/vulnerabilities/'.$vulnerability, ['nama_kerentanan' => 'Celah akses', 'threat_ids' => []])->assertOk()->assertJsonCount(0, 'data.threats');
        $this->deleteJson('/api/admin/vulnerabilities/'.$vulnerability)->assertUnprocessable();
        $this->putJson('/api/admin/risk-assessments/'.$id, [...$input, 'vulnerability_ids' => [999999]])->assertUnprocessable();
        $this->assertDatabaseHas('risk_assessment_vulnerability', ['risk_assessment_id' => $id, 'vulnerability_id' => $vulnerability]);
        $this->putJson('/api/admin/risk-assessments/'.$id, [...$input, 'vulnerability_ids' => []])->assertOk()->assertJsonCount(0, 'data.vulnerabilities');
        $this->postJson('/api/admin/vulnerabilities', ['nama_kerentanan' => 'Invalid', 'threat_ids' => [999999]])->assertUnprocessable();
    }

    public function test_non_admin_cannot_access_assessment_endpoints(): void
    {
        $role = DB::table('roles')->insertGetId(['name' => 'user']);
        Sanctum::actingAs(User::factory()->create(['role_id' => $role]));
        $this->getJson('/api/admin/risk-assessments')->assertForbidden();
        $this->postJson('/api/admin/risk-assessments', $this->payload())->assertForbidden();
    }
}
