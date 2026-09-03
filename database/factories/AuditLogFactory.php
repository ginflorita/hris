<?php

namespace Database\Factories;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => AuditAction::Updated,
            'module' => 'User Management',
            'before' => ['name' => 'Old Name'],
            'after' => ['name' => 'New Name'],
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ];
    }
}
