<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SatkerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_satker_code_and_bidang_are_saved_and_validated(): void
    {
        $role = DB::table('roles')->insertGetId(['name' => 'admin']);
        Sanctum::actingAs(User::factory()->create(['role_id' => $role]));
        $bidang = $this->postJson('/api/admin/bidangs', ['nama_bidang' => 'Operasi'])->assertCreated()->json('data.id');
        $payload = ['bidang_id' => $bidang, 'kode_satker' => '0014152312', 'nama_satker' => 'Ragil'];
        $id = $this->postJson('/api/admin/satkers', $payload)->assertCreated()->assertJsonPath('data.kode_satker', '0014152312')->assertJsonPath('data.bidang.nama_bidang', 'Operasi')->json('data.id');
        $this->assertDatabaseHas('satkers', [...$payload, 'id' => $id]);
        $this->getJson('/api/admin/satkers')->assertOk()->assertJsonPath('data.0.kode_satker', '0014152312');
        $this->putJson('/api/admin/satkers/'.$id, [...$payload, 'nama_satker' => 'Unit Ragil'])->assertOk()->assertJsonPath('data.kode_satker', '0014152312');
        $this->postJson('/api/admin/satkers', $payload)->assertUnprocessable()->assertJsonValidationErrors('kode_satker');
        $this->postJson('/api/admin/satkers', [...$payload, 'kode_satker' => null])->assertUnprocessable()->assertJsonValidationErrors('kode_satker');
        $this->postJson('/api/admin/satkers', [...$payload, 'kode_satker' => '123', 'bidang_id' => 99999])->assertUnprocessable()->assertJsonValidationErrors('bidang_id');
    }
}
