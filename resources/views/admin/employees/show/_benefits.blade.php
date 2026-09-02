@can('benefits.manage')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBenefitEnrollmentModal">Enroll</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Type</th>
                    <th>Effective</th>
                    <th>Employee Contribution</th>
                    <th>Employer Contribution</th>
                    <th>Covered Dependents</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->benefitEnrollments as $enrollment)
                    <tr class="{{ $enrollment->isCurrent() ? 'table-active' : '' }}">
                        <td>{{ $enrollment->plan->name }}</td>
                        <td>{{ $enrollment->plan->type->label() }}</td>
                        <td>
                            {{ $enrollment->effective_date->format('M d, Y') }} &ndash; {{ $enrollment->end_date?->format('M d, Y') ?? 'present' }}
                            @if ($enrollment->isCurrent())
                                <span class="badge text-bg-success">Current</span>
                            @endif
                        </td>
                        <td>{{ $enrollment->employee_contribution !== null ? number_format((float) $enrollment->employee_contribution, 2) : '—' }}</td>
                        <td>{{ $enrollment->employer_contribution !== null ? number_format((float) $enrollment->employer_contribution, 2) : '—' }}</td>
                        <td>{{ $enrollment->coveredDependents->pluck('name')->implode(', ') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-body-secondary py-3">No benefit enrollments yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('benefits.manage')
    <div class="modal fade" id="addBenefitEnrollmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.employees.benefit-enrollments.store', $employee) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Enroll in Benefit Plan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="benefit_plan_id">Plan</label>
                            <select id="benefit_plan_id" name="benefit_plan_id" class="form-select" required>
                                <option value="">Select a plan</option>
                                @foreach ($benefitPlans as $benefitPlan)
                                    <option value="{{ $benefitPlan->id }}">{{ $benefitPlan->name }} ({{ $benefitPlan->type->label() }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label" for="employee_contribution">Employee Contribution</label>
                                <input type="number" step="0.01" min="0" id="employee_contribution" name="employee_contribution" class="form-control">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label" for="employer_contribution">Employer Contribution</label>
                                <input type="number" step="0.01" min="0" id="employer_contribution" name="employer_contribution" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="effective_date">Effective Date</label>
                            <input type="date" id="effective_date" name="effective_date" class="form-control" required>
                        </div>
                        @if ($employee->dependents->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label">Covered Dependents</label>
                                @foreach ($employee->dependents as $dependent)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="covered_dependent_ids[]" value="{{ $dependent->id }}" id="dependent_{{ $dependent->id }}">
                                        <label class="form-check-label" for="dependent_{{ $dependent->id }}">{{ $dependent->name }} ({{ $dependent->relationship }})</label>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Enroll</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
