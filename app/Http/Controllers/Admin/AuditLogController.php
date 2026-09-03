<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only by design (blueprint §51 17.16) — no store/update/destroy
 * actions exist at all, the same "protection by omission" every other
 * immutable record in this app relies on (Employment, finalized Payroll).
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('audit-logs.view');

        $logs = AuditLog::query()
            ->with('user', 'auditable')
            ->when($request->string('module')->trim()->value(), fn ($query, $module) => $query->where('module', $module))
            ->when($request->string('action')->trim()->value(), fn ($query, $action) => $query->where('action', $action))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'selectedModule' => $request->string('module')->value(),
            'selectedAction' => $request->string('action')->value(),
        ]);
    }
}
