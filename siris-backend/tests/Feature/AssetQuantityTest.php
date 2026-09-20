<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetQuantityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $roleId = DB::table('roles')->insertGetId(['name' => 'admin']);
        Sanctum::actingAs(User::factory()->create(['role_id' => $roleId]));
    }

    public function test_quantity_is_saved_updated_and_returned_by_api(): void
    {
        $input = ['kode_aset' => 'AST-TEST', 'nama_aset' => 'Laptop', 'kategori' => 'physical', 'jumlah' => 12];
        $id = $this->postJson('/api/admin/assets', $input)->assertCreated()->assertJsonPath('data.jumlah', 12)->json('data.id');
        $input['jumlah'] = 7;
        $this->putJson('/api/admin/assets/'.$id, $input)->assertOk()->assertJsonPath('data.jumlah', 7);
        $this->assertDatabaseHas('assets', ['id' => $id, 'jumlah' => 7]);
        $this->getJson('/api/admin/assets')->assertOk()->assertJsonPath('data.data.0.jumlah', 7);
        $this->getJson('/api/admin/assets/'.$id)->assertOk()->assertJsonPath('data.jumlah', 7);
    }

    public function test_invalid_quantities_are_rejected_on_create_and_update(): void
    {
        $input = ['kode_aset' => 'AST-TEST', 'nama_aset' => 'Laptop', 'kategori' => 'physical', 'jumlah' => 1];
        $id = $this->postJson('/api/admin/assets', $input)->assertCreated()->json('data.id');
        foreach ([null, 0, -1, 1.5, 'abc', 2147483648] as $quantity) {
            $input['jumlah'] = $quantity;
            $input['kode_aset'] = 'AST-NEW';
            $this->postJson('/api/admin/assets', $input)->assertUnprocessable()->assertJsonValidationErrors('stock');
            $this->putJson('/api/admin/assets/'.$id, $input)->assertUnprocessable()->assertJsonValidationErrors('stock');
        }
        unset($input['jumlah']);
        $this->postJson('/api/admin/assets', $input)->assertUnprocessable()->assertJsonValidationErrors('stock');
        $this->putJson('/api/admin/assets/'.$id, $input)->assertUnprocessable()->assertJsonValidationErrors('stock');
        $this->assertDatabaseHas('assets', ['id' => $id, 'jumlah' => 1]);
    }

    public function test_migration_sets_quantity_for_existing_assets(): void
    {
        $migration = require database_path('migrations/2026_09_18_061623_add_jumlah_to_assets_table.php');
        $migration->down();
        $id = DB::table('assets')->insertGetId(['kode_aset' => 'OLD-1', 'nama_aset' => 'Aset lama', 'kategori' => 'physical']);
        $migration->up();
        $this->assertDatabaseHas('assets', ['id' => $id, 'jumlah' => 1]);
    }
}
