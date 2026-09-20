<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $roleId = DB::table('roles')->insertGetId(['name' => 'admin']);
        Sanctum::actingAs(User::factory()->create(['role_id' => $roleId]));
    }

    private function payload(): array
    {
        return ['idx' => 257, 'noinven' => 'INV-0257', 'nama' => 'Laptop', 'kondisi' => 'Baik', 'merk' => 'Contoh', 'snumber' => 'SN-001', 'stock' => 5, 'pengadaan' => '2024-09-18', 'lokasi' => 'Ruang A', 'keterangan' => 'Perangkat kerja'];
    }

    public function test_inventory_payload_is_saved_updated_and_searchable(): void
    {
        $input = $this->payload();
        $response = $this->postJson('/api/admin/assets', $input)->assertCreated();
        foreach ($input as $key => $value) {
            $response->assertJsonPath('data.'.$key, $value);
        }
        $id = $response->json('data.id');
        $response->assertJsonPath('data.kategori', 'physical');
        $input['stock'] = 9;
        $input['lokasi'] = 'Ruang B';
        $this->putJson('/api/admin/assets/'.$id, $input)->assertOk()->assertJsonPath('data.stock', 9)->assertJsonPath('data.id', $id);
        $this->assertDatabaseHas('assets', ['id' => $id, 'jumlah' => 9, 'idx' => 257]);
        foreach (['INV-0257', 'Laptop', 'Contoh', 'SN-001', 'Ruang B'] as $search) {
            $this->getJson('/api/admin/assets?search='.urlencode($search))->assertOk()->assertJsonPath('data.data.0.id', $id);
        }
        $this->getJson('/api/admin/assets/'.$id)->assertOk()->assertJsonPath('data.noinven', 'INV-0257');
    }

    public function test_duplicate_inventory_identifiers_and_invalid_stock_are_rejected(): void
    {
        $input = $this->payload();
        $this->postJson('/api/admin/assets', $input)->assertCreated();
        $this->postJson('/api/admin/assets', $input)->assertUnprocessable()->assertJsonValidationErrors(['idx', 'noinven']);
        $input['idx'] = -1;
        $input['stock'] = 1.5;
        $this->postJson('/api/admin/assets', $input)->assertUnprocessable()->assertJsonValidationErrors(['idx', 'stock']);
    }

    public function test_legacy_updates_preserve_inventory_fields_and_threat_relationship(): void
    {
        $id = $this->postJson('/api/admin/assets', $this->payload())->assertCreated()->json('data.id');
        $threatId = $this->postJson('/api/admin/threats', ['kode_ancaman' => 'TH-1', 'nama_ancaman' => 'Pencurian', 'asset_id' => $id])->assertCreated()->json('data.id');
        $this->putJson('/api/admin/assets/'.$id, ['kode_aset' => 'INV-0257', 'nama_aset' => 'Laptop baru', 'jumlah' => 3, 'kategori' => 'software'])
            ->assertOk()->assertJsonPath('data.nama', 'Laptop baru')->assertJsonPath('data.idx', 257)->assertJsonPath('data.snumber', 'SN-001');
        $this->getJson('/api/admin/threats/'.$threatId)->assertOk()->assertJsonPath('data.asset.nama', 'Laptop baru')->assertJsonPath('data.asset.id', $id);
    }

    public function test_inventory_migration_preserves_existing_data(): void
    {
        $migration = require database_path('migrations/2026_09_18_065747_add_inventory_fields_to_assets_table.php');
        $migration->down();
        $id = DB::table('assets')->insertGetId(['kode_aset' => 'OLD-01', 'nama_aset' => 'Aset lama', 'kategori' => 'digital', 'jumlah' => 8, 'deskripsi' => 'Catatan lama']);
        $migration->up();
        $this->getJson('/api/admin/assets/'.$id)->assertOk()->assertJsonPath('data.noinven', 'OLD-01')->assertJsonPath('data.stock', 8)->assertJsonPath('data.keterangan', 'Catatan lama')->assertJsonPath('data.idx', null);
    }

    public function test_asset_can_be_created_and_edited_without_inventory_number(): void
    {
        $input = $this->payload();
        unset($input['noinven']);
        $response = $this->postJson('/api/admin/assets', $input)->assertCreated()->assertJsonPath('data.pengadaan', '2024-09-18');
        $id = $response->json('data.id');
        $code = $response->json('data.kode_aset');
        $input['pengadaan'] = '2026-09-19';
        $this->putJson('/api/admin/assets/'.$id, $input)->assertOk()->assertJsonPath('data.pengadaan', '2026-09-19')->assertJsonPath('data.kode_aset', $code);
        foreach (['2024', '2025-02-29', '18/09/2024', 'invalid'] as $invalid) {
            $input['pengadaan'] = $invalid;
            $this->putJson('/api/admin/assets/'.$id, $input)->assertUnprocessable()->assertJsonValidationErrors('pengadaan');
        }
        $input['pengadaan'] = null;
        $this->putJson('/api/admin/assets/'.$id, $input)->assertOk()->assertJsonPath('data.pengadaan', null);
    }

    public function test_date_migration_preserves_legacy_text_and_valid_dates(): void
    {
        $migration = require database_path('migrations/2026_09_18_072101_convert_asset_pengadaan_to_date.php');
        $migration->down();
        $ids = [];
        foreach (['2024', '2024-02-29', '2025-02-29', 'Pembelian lama'] as $index => $value) {
            $ids[] = DB::table('assets')->insertGetId(['kode_aset' => 'DATE-'.$index, 'nama_aset' => 'Aset', 'kategori' => 'physical', 'pengadaan' => $value]);
        }
        $migration->up();
        $this->assertDatabaseHas('assets', ['id' => $ids[0], 'pengadaan' => null, 'pengadaan_lama' => '2024']);
        $this->assertDatabaseHas('assets', ['id' => $ids[1], 'pengadaan' => '2024-02-29']);
        $this->assertDatabaseHas('assets', ['id' => $ids[2], 'pengadaan' => null, 'pengadaan_lama' => '2025-02-29']);
        $this->assertDatabaseHas('assets', ['id' => $ids[3], 'pengadaan' => null, 'pengadaan_lama' => 'Pembelian lama']);
        $migration->down();
        $this->assertDatabaseHas('assets', ['id' => $ids[0], 'pengadaan' => '2024']);
        $this->assertDatabaseHas('assets', ['id' => $ids[1], 'pengadaan' => '2024-02-29']);
        $migration->up();
    }
}
