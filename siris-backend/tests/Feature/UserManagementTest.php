<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_and_filter_users(): void
    {
        $adminRole = DB::table('roles')->insertGetId(['name' => 'admin']);
        $userRole = DB::table('roles')->insertGetId(['name' => 'user']);
        $admin = User::factory()->create(['role_id' => $adminRole]);
        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/users/roles')->assertOk()->assertJsonCount(2, 'data');
        $payload = ['name' => 'Pengguna Baru', 'email' => 'baru@example.test', 'password' => 'Password123!', 'role_id' => $userRole, 'is_active' => true];
        $id = $this->postJson('/api/admin/users', $payload)->assertCreated()->assertJsonMissingPath('data.password')->json('data.id');
        $this->assertTrue(Hash::check($payload['password'], User::findOrFail($id)->password));
        $this->postJson('/api/admin/users', $payload)->assertUnprocessable()->assertJsonValidationErrors('email');
        unset($payload['password']);
        $this->putJson('/api/admin/users/'.$id, [...$payload, 'is_active' => false])->assertOk();
        $this->assertTrue(Hash::check('Password123!', User::findOrFail($id)->password));
        $this->getJson('/api/admin/users?search=baru&is_active=0')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson('/api/admin/users?search=baru&is_active=1')->assertOk()->assertJsonPath('data.total', 0);
        $this->putJson('/api/admin/users/'.$admin->id, ['name' => $admin->name, 'email' => $admin->email, 'role_id' => $userRole])->assertUnprocessable();
        $this->putJson('/api/admin/users/'.$admin->id, ['name' => $admin->name, 'email' => $admin->email, 'role_id' => $adminRole, 'is_active' => false])->assertUnprocessable();
        Sanctum::actingAs(User::factory()->create(['role_id' => $userRole]));
        $this->getJson('/api/admin/users')->assertForbidden();
        $this->getJson('/api/admin/users/roles')->assertForbidden();
        $this->postJson('/api/admin/users', $payload)->assertForbidden();
    }
}
