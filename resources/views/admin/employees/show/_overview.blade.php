@php($current = $employee->employments->firstWhere('end_date', null))
@php($currentSchedule = $employee->employeeSchedules->firstWhere('end_date', null))
@php($rows = [
    'Full name' => $employee->full_name,
    'Preferred name' => $employee->preferred_name,
    'Birth date' => $employee->birth_date?->format('M d, Y'),
    'Gender' => $employee->gender ? ucwords(str_replace('_', ' ', $employee->gender->value)) : null,
    'Civil status' => $employee->civil_status ? ucfirst($employee->civil_status->value) : null,
    'Nationality' => $employee->nationality,
    'Email' => $employee->email,
    'Mobile' => $employee->mobile,
    'Company' => $employee->company->name,
])

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">Personal</div>
            <div class="card-body">
                <dl class="row mb-0">
                    @foreach ($rows as $label => $value)
                        <dt class="col-sm-5 text-body-secondary fw-normal">{{ $label }}</dt>
                        <dd class="col-sm-7">{{ $value ?? '—' }}</dd>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">Current employment</div>
            <div class="card-body">
                @if ($current)
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-body-secondary fw-normal">Position</dt>
                        <dd class="col-sm-7">{{ $current->position?->title ?? '—' }}</dd>
                        <dt class="col-sm-5 text-body-secondary fw-normal">Department</dt>
                        <dd class="col-sm-7">{{ $current->department?->name ?? '—' }}</dd>
                        <dt class="col-sm-5 text-body-secondary fw-normal">Salary grade</dt>
                        <dd class="col-sm-7">{{ $current->salaryGrade?->name ?? '—' }}</dd>
                        <dt class="col-sm-5 text-body-secondary fw-normal">Basic salary</dt>
                        <dd class="col-sm-7">{{ $current->basic_salary !== null ? number_format($current->basic_salary, 2) : '—' }}</dd>
                        <dt class="col-sm-5 text-body-secondary fw-normal">Branch</dt>
                        <dd class="col-sm-7">{{ $current->branch?->name ?? '—' }}</dd>
                        <dt class="col-sm-5 text-body-secondary fw-normal">Manager</dt>
                        <dd class="col-sm-7">{{ $current->manager?->full_name ?? '—' }}</dd>
                        <dt class="col-sm-5 text-body-secondary fw-normal">Employment type</dt>
                        <dd class="col-sm-7">{{ ucwords(str_replace('_', ' ', $current->employment_type->value)) }}</dd>
                        <dt class="col-sm-5 text-body-secondary fw-normal">Status</dt>
                        <dd class="col-sm-7">{{ ucwords(str_replace('_', ' ', $current->status->value)) }}</dd>
                        <dt class="col-sm-5 text-body-secondary fw-normal">Since</dt>
                        <dd class="col-sm-7">{{ $current->effective_date->format('M d, Y') }}</dd>
                    </dl>
                @else
                    <p class="text-body-secondary mb-0">No employment record yet. Use the Employment History tab to record a hire.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                Current schedule
                @can('employees.update')
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignScheduleModal">Assign</button>
                @endcan
            </div>
            <div class="card-body">
                @if ($currentSchedule)
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-body-secondary fw-normal">Schedule</dt>
                        <dd class="col-sm-7">{{ $currentSchedule->schedule->name }}</dd>
                        <dt class="col-sm-5 text-body-secondary fw-normal">Type</dt>
                        <dd class="col-sm-7">{{ ucfirst($currentSchedule->schedule->type->value) }}</dd>
                        <dt class="col-sm-5 text-body-secondary fw-normal">Since</dt>
                        <dd class="col-sm-7">{{ $currentSchedule->effective_date->format('M d, Y') }}</dd>
                    </dl>
                @else
                    <p class="text-body-secondary mb-0">No schedule assigned.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@can('employees.update')
    <div class="modal fade" id="assignScheduleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.employees.schedules.store', $employee) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Assign schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Schedule</label>
                            <select name="schedule_id" class="form-select" required>
                                @foreach ($schedules as $scheduleOption)
                                    <option value="{{ $scheduleOption->id }}">{{ $scheduleOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Effective date</label>
                            <input type="date" name="effective_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
