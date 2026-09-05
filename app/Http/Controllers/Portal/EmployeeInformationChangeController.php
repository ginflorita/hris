<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Workflow\Services\WorkflowEngine;
use App\Enums\CivilStatus;
use App\Enums\WorkflowProcessType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeInformationChangeRequest;
use App\Models\WorkflowDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Blueprint §18 "Update permitted information" -- the self-service gap
 * Portal\ProfileController's own doc comment has flagged since Phase
 * 13a. Submitting a change here doesn't touch the Employee record at
 * all; it starts a real WorkflowInstance against the company's active
 * EmployeeInformationChange WorkflowDefinition, and the record only
 * changes once every step approves (WorkflowEngine's own
 * applyWorkflowApproval() callback). No employee_id spoofing surface --
 * same as every other portal controller, this hard-codes the acting
 * user's own employee, never a request-supplied id.
 */
class EmployeeInformationChangeController extends Controller
{
    public function __construct(private readonly WorkflowEngine $engine) {}

    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return view('portal.information-change.index', ['employee' => null]);
        }

        $employee->load(['informationChangeRequests' => fn ($query) => $query->latest()->with('workflowInstance')]);

        return view('portal.information-change.index', [
            'employee' => $employee,
            'definitionAvailable' => $this->activeDefinitionFor($employee) !== null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 404);

        $definition = $this->activeDefinitionFor($employee);
        abort_unless($definition !== null, 422, 'No approval workflow is configured for information changes yet. Contact HR.');

        $validated = $request->validate([
            'requested_mobile' => ['nullable', 'string', 'max:255'],
            'requested_email' => ['nullable', 'email', 'max:255'],
            'requested_civil_status' => ['nullable', Rule::enum(CivilStatus::class)],
            'requested_nationality' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $hasAChange = collect($validated)->except('reason')->filter(fn ($value) => $value !== null)->isNotEmpty();

        if (! $hasAChange) {
            return back()->withErrors(['requested_mobile' => 'Change at least one field.'])->withInput();
        }

        $subject = EmployeeInformationChangeRequest::create([
            ...$validated,
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
        ]);

        $this->engine->start($definition, $subject, $request->user());

        return back()->with('status', 'Request submitted for approval.');
    }

    private function activeDefinitionFor(Employee $employee): ?WorkflowDefinition
    {
        return WorkflowDefinition::query()
            ->where('company_id', $employee->company_id)
            ->where('process_type', WorkflowProcessType::EmployeeInformationChange)
            ->where('is_active', true)
            ->first();
    }
}
