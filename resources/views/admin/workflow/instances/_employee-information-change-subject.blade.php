@php($employee = $request->employee)

<div class="mt-3">
    <div class="text-body-secondary small mb-1">Employee Information Change</div>
    <div class="mb-2">
        <a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->full_name }}</a>
    </div>

    <table class="table table-sm table-borderless mb-2">
        <thead>
            <tr class="text-body-secondary small">
                <th>Field</th>
                <th>Current</th>
                <th>Requested</th>
            </tr>
        </thead>
        <tbody>
            @if ($request->requested_mobile !== null)
                <tr>
                    <td>Mobile</td>
                    <td>{{ $employee->mobile ?? '—' }}</td>
                    <td>{{ $request->requested_mobile }}</td>
                </tr>
            @endif
            @if ($request->requested_email !== null)
                <tr>
                    <td>Email</td>
                    <td>{{ $employee->email ?? '—' }}</td>
                    <td>{{ $request->requested_email }}</td>
                </tr>
            @endif
            @if ($request->requested_civil_status !== null)
                <tr>
                    <td>Civil status</td>
                    <td>{{ $employee->civil_status?->value ?? '—' }}</td>
                    <td>{{ $request->requested_civil_status->value }}</td>
                </tr>
            @endif
            @if ($request->requested_nationality !== null)
                <tr>
                    <td>Nationality</td>
                    <td>{{ $employee->nationality ?? '—' }}</td>
                    <td>{{ $request->requested_nationality }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="small">
        <span class="text-body-secondary">Reason:</span> {{ $request->reason }}
    </div>
</div>
