<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Security\Services\DataScopeResolver;
use App\Enums\CivilStatus;
use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Competency;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\OnboardingTemplate;
use App\Models\PayrollGroup;
use App\Models\PerformanceCycle;
use App\Models\Position;
use App\Models\SalaryGrade;
use App\Models\Schedule;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request, DataScopeResolver $scopeResolver): View
    {
        $this->authorize('employees.view');

        $query = Employee::with('company')->orderBy('last_name')->orderBy('first_name');

        $employeeIds = $scopeResolver->employeeIdsFor($request->user(), 'employees.view');
        if ($employeeIds !== null) {
            $query->whereIn('id', $employeeIds);
        }

        if (! $request->boolean('with_archived')) {
            $query->whereNull('archived_at');
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return view('admin.employees.index', [
            'employees' => $query->paginate(20)->withQueryString(),
            'q' => $search,
            'withArchived' => $request->boolean('with_archived'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('employees.create');

        return view('admin.employees.create', ['companies' => $this->companies()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employees.create');

        $employee = Employee::create($this->validated($request));

        return redirect()->route('admin.employees.show', $employee)->with('status', 'Employee created.');
    }

    public function show(Request $request, Employee $employee, DataScopeResolver $scopeResolver): View
    {
        $this->authorize('employees.view');

        $employeeIds = $scopeResolver->employeeIdsFor($request->user(), 'employees.view');
        abort_if($employeeIds !== null && ! in_array($employee->id, $employeeIds, true), 403);

        $employee->load([
            'company', 'addresses', 'contacts', 'emergencyContacts', 'governmentIds', 'dependents',
            'documents.uploadedBy', 'notes.createdBy',
            'employments' => fn ($q) => $q->with(['department', 'position', 'branch', 'location', 'manager']),
            'employeeSchedules' => fn ($q) => $q->with('schedule'),
            'leaveBalances' => fn ($q) => $q->with('leaveType'),
            'leaveTransactions' => fn ($q) => $q->with(['leaveType', 'createdBy'])->orderByDesc('date')->orderByDesc('id'),
            'compensationItems' => fn ($q) => $q->orderByDesc('effective_date'),
            'onboardings' => fn ($q) => $q->with(['template', 'assignedBy', 'tasks.completedBy']),
            'performanceGoals' => fn ($q) => $q->with('performanceCycle'),
            'performanceReviews' => fn ($q) => $q->with(['performanceCycle', 'reviewer']),
            'performanceImprovementPlans' => fn ($q) => $q->with(['performanceReview', 'initiatedBy']),
            'employeeCompetencies' => fn ($q) => $q->with(['competency', 'assessedBy']),
            'employeeSkills' => fn ($q) => $q->with(['skill', 'assessedBy']),
            'trainingEnrollments' => fn ($q) => $q->with('session.course'),
            'careerDevelopmentPlans' => fn ($q) => $q->with('targetPosition'),
            'successionCandidacies' => fn ($q) => $q->with('position'),
        ]);

        return view('admin.employees.show', [
            'employee' => $employee,
            'departments' => Department::where('company_id', $employee->company_id)->orderBy('name')->get(),
            'positions' => Position::where('company_id', $employee->company_id)->orderBy('title')->get(),
            'branches' => Branch::where('company_id', $employee->company_id)->orderBy('name')->get(),
            'locations' => Location::where('company_id', $employee->company_id)->orderBy('name')->get(),
            'managers' => Employee::where('company_id', $employee->company_id)->where('id', '!=', $employee->id)->orderBy('last_name')->get(),
            'schedules' => Schedule::where('company_id', $employee->company_id)->orderBy('name')->get(),
            'leaveTypes' => LeaveType::where('company_id', $employee->company_id)->orderBy('name')->get(),
            'salaryGrades' => SalaryGrade::where('company_id', $employee->company_id)->orderBy('name')->get(),
            'payrollGroups' => PayrollGroup::where('company_id', $employee->company_id)->orderBy('name')->get(),
            'onboardingTemplates' => OnboardingTemplate::where('company_id', $employee->company_id)->where('is_active', true)->orderBy('name')->get(),
            'performanceCycles' => PerformanceCycle::where('company_id', $employee->company_id)->orderByDesc('start_date')->get(),
            'companyEmployees' => Employee::where('company_id', $employee->company_id)->orderBy('last_name')->get(),
            'companyCompetencies' => Competency::where('company_id', $employee->company_id)->where('is_active', true)->orderBy('name')->get(),
            'companySkills' => Skill::where('company_id', $employee->company_id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('employees.update');

        return view('admin.employees.edit', ['employee' => $employee, 'companies' => $this->companies()]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        $employee->update($this->validated($request, $employee));

        return redirect()->route('admin.employees.show', $employee)->with('status', 'Employee updated.');
    }

    public function archive(Employee $employee): RedirectResponse
    {
        $this->authorize('employees.archive');

        $employee->update(['archived_at' => now()]);

        return back()->with('status', 'Employee archived.');
    }

    public function restore(Employee $employee): RedirectResponse
    {
        $this->authorize('employees.archive');

        $employee->update(['archived_at' => null]);

        return back()->with('status', 'Employee restored.');
    }

    /**
     * @return Collection<int, Company>
     */
    private function companies(): Collection
    {
        return Company::orderBy('name')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'employee_number' => [
                'required', 'string', 'max:50',
                Rule::unique('employees', 'employee_number')->where('company_id', $request->input('company_id'))->ignore($employee?->id),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'civil_status' => ['nullable', Rule::enum(CivilStatus::class)],
            'nationality' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('employees', 'email')->where('company_id', $request->input('company_id'))->ignore($employee?->id),
            ],
            'mobile' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
