<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin',
            'kabid',
            'kasub',
            'satker',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
            ]);
        }

        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}