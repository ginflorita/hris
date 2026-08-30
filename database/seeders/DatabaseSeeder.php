<?php

namespace Database\Seeders;

use App\Enums\DefaultRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Local/dev only — never run automatically in production. Creates the
     * roles/permissions catalog and one Superadmin account so the system
     * is usable before Phase 5+ builds real user-provisioning workflows
     * beyond what this Phase 4 admin UI already offers.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Dev Admin',
            'email' => 'admin@example.test',
            'password' => 'Password!2026',
            'is_system_account' => true,
            'is_protected' => true,
        ]);

        $admin->assignRole(DefaultRole::Superadmin->value);
    }
}
