<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        User::updateOrCreate(
            [
                'email' => 'kirana@kemhan.go.id',
            ],
            [
                'name' => 'Administrator SIRIS',
                'password' => Hash::make('krn@3131'),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );
    }
}
