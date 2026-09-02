<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OffboardingStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\OffboardingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Blueprint §26's whole pipeline moves through one generic advance()
 * action rather than ten separately-named guarded methods -- see
 * App\Enums\OffboardingStatus::sequence(). "Never delete the employee
 * record" (blueprint's own explicit note on this section) is already
 * true here by construction: nothing in this controller touches
 * Employee::delete() or SoftDeletes at all, only OffboardingRequest's own
 * status and, at the Account Disabled step, the linked User's
 * disabled_at (the same Phase 3 mechanism admin-disabling any other
 * account already uses).
 */
class OffboardingRequestController extends Controller
{
    public function index(): View
    {
        $this->authorize('employees.view');

        return view('admin.offboarding-requests.index', [
            'requests' => OffboardingRequest::with('employee.company')->orderByDesc('created_at')->paginate(20),
        ]);
    }

    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        if ($employee->offboardingRequests()->whereNotIn('status', ['separated', 'cancelled'])->exists()) {
            return back()->withErrors(['offboarding' => 'This employee already has an offboarding request in progress.']);
        }

        $validated = $request->validate([
            'resignation_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $employee->offboardingRequests()->create([
            ...$validated,
            'status' => OffboardingStatus::Resignation,
            'status_changed_at' => now(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Offboarding request created.');
    }

    public function advance(Request $request, Employee $employee, OffboardingRequest $offboarding): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($offboarding->employee_id === $employee->id, 404);
        abort_if($offboarding->status->isTerminal(), 422, 'This request has already been closed out.');

        $next = $offboarding->status->next();
        abort_if($next === null, 422, 'This request has no further steps.');

        $update = ['status' => $next, 'status_changed_at' => now()];

        if ($next === OffboardingStatus::Approved) {
            $update['approved_at'] = now();
            $update['approved_by'] = $request->user()->id;
        }

        if ($next === OffboardingStatus::AccountDisabled) {
            $employee->user?->update(['disabled_at' => now()]);
        }

        $offboarding->update($update);

        return back()->with('status', "Moved to {$next->label()}.");
    }

    public function cancel(Request $request, Employee $employee, OffboardingRequest $offboarding): RedirectResponse
    {
        $this->authorize('employees.update');
        abort_unless($offboarding->employee_id === $employee->id, 404);
        abort_if($offboarding->status->isTerminal(), 422, 'This request has already been closed out.');

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:2000'],
        ]);

        $offboarding->update([
            'status' => OffboardingStatus::Cancelled,
            'status_changed_at' => now(),
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return back()->with('status', 'Offboarding request cancelled.');
    }
}
