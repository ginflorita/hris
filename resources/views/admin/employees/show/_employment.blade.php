@can('employees.update')
    <div class="d-flex justify-content-end mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEmploymentModal">Record change</button>
    </div>
@endcan

<div class="card">
    <div class="table-responsive">
        <table class="table table-compact mb-0">
            <thead>
                <tr>
                    <th>Effective</th>
                    <th>Change</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Salary Grade</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Salary</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employee->employments as $record)
                    <tr class="{{ $record->isCurrent() ? 'table-active' : '' }}">
                        <td>
                            {{ $record->effective_date->format('M d, Y') }}
                            @if ($record->isCurrent())
                                <span class="badge text-bg-success">Current</span>
                            @endif
                        </td>
                        <td>{{ ucwords(str_replace('_', ' ', $record->change_type->value)) }}</td>
                        <td>{{ $record->position?->title ?? '—' }}</td>
                        <td>{{ $record->department?->name ?? '—' }}</td>
                        <td>{{ $record->salaryGrade?->name ?? '—' }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $record->employment_type->value)) }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $record->status->value)) }}</td>
                        <td>{{ $record->basic_salary !== null ? number_format($record->basic_salary, 2) : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-body-secondary py-3">No employment history yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('employees.update')
    @include('admin.employees.show._employment-modal')
@endcan
