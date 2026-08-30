<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Local/dev only — never run automatically in production. Creates one
     * account so Phase 3's login can actually be exercised before Phase 4
     * (RBAC) exists to provision users properly.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Dev Admin',
            'email' => 'admin@example.test',
            'password' => 'Password!2026',
        ]);
    }
}
