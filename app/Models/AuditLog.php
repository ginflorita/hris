<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only: no controller action updates or deletes a row (blueprint
 * §51 17.16 — "Audit logs should be protected from ordinary modification
 * and deletion"). Write through App\Domain\Security\Services\AuditLogger,
 * never directly, so every entry goes through one consistent shape.
 */
class AuditLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'auditable_type',
        'auditable_id',
        'before',
        'after',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
