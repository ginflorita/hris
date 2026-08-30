@extends('layouts.admin')

@section('title', 'Attendance')

@php($breadcrumbs = [['label' => 'Attendance']])

@section('content')
    <x-admin.attendance-subnav active="attendances" />

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="employee_id" class="form-select" onchange="this.form.submit()">
                <option value="">All employees</option>
                @foreach ($employees as $employeeOption)
                    <option value="{{ $employeeOption->id }}" {{ (int) ($filters['employee_id'] ?? null) === $employeeOption->id ? 'selected' : '' }}>
                        {{ $employeeOption->full_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" placeholder="From">
        </div>
        <div class="col-auto">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" placeholder="To">
        </div>
        <div class="col-auto">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach (\App\Enums\AttendanceStatus::cases() as $case)
                    <option value="{{ $case->value }}" {{ ($filters['status'] ?? '') === $case->value ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $case->value)) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Filter</button>
        </div>
    </form>

    <x-admin.resource-index
        :create-url="auth()->user()->can('attendance.manage') ? route('admin.attendance.attendances.create') : null"
        create-label="Record attendance"
    >
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee</th>
                <th>Time in</th>
                <th>Time out</th>
                <th>Status</th>
                <th>Late</th>
                <th>Undertime</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->date->format('M d, Y') }}</td>
                    <td>{{ $attendance->employee->full_name }}</td>
                    <td>{{ $attendance->time_in?->format('h:i A') ?? '—' }}</td>
                    <td>{{ $attendance->time_out?->format('h:i A') ?? '—' }}</td>
                    <td>
                        {{ ucwords(str_replace('_', ' ', $attendance->status->value)) }}
                        @if ($attendance->is_corrected)
                            <span class="badge text-bg-warning">Corrected</span>
                        @endif
                    </td>
                    <td>{{ $attendance->late_minutes > 0 ? $attendance->late_minutes.' min' : '—' }}</td>
                    <td>{{ $attendance->undertime_minutes > 0 ? $attendance->undertime_minutes.' min' : '—' }}</td>
                    <td class="text-end">
                        @can('attendance.correct')
                            <a href="{{ route('admin.attendance.attendances.edit', $attendance) }}" class="btn btn-sm btn-outline-secondary">Correct</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-body-secondary py-3">No attendance records yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $attendances->links() }}</div>
@endsection
