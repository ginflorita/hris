<?php

namespace App\Domain\Security\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The one place that writes to audit_logs, per blueprint §51 17.16 — every
 * call site building its own record inline would risk each one shaping the
 * row slightly differently. $before/$after are plain associative arrays of
 * only the fields that actually matter for that action (blueprint's own
 * example is a single field: basic_salary before/after), not a full model
 * dump — the caller decides what's meaningful to show a reviewer.
 */
class AuditLogger
{
    public function log(
        ?User $actor,
        AuditAction $action,
        string $module,
        ?Model $auditable = null,
        ?array $before = null,
        ?array $after = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'module' => $module,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => request()->ip(),
        ]);
    }
}
