@extends('layouts.admin')

@section('title', 'Schedules')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Schedules']])

@section('content')
    <x-admin.attendance-subnav active="schedules" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('attendance.manage') ? route('admin.attendance.schedules.create') : null"
        create-label="Add schedule"
        error-key="schedule"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Code</th>
                <th>Type</th>
                <th>Shift</th>
                <th>Rest days</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($schedules as $schedule)
                <tr>
                    <td>{{ $schedule->name }}</td>
                    <td>{{ $schedule->company->name }}</td>
                    <td><code>{{ $schedule->code }}</code></td>
                    <td>{{ ucfirst($schedule->type->value) }}</td>
                    <td>{{ $schedule->shift?->name ?? '—' }}</td>
                    <td>{{ collect($schedule->rest_days ?? [])->map(fn ($d) => $weekdays[$d])->implode(', ') ?: '—' }}</td>
                    <td>
                        @if ($schedule->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('attendance.manage')
                            <a href="{{ route('admin.attendance.schedules.edit', $schedule) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.attendance.schedules.destroy', $schedule) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $schedule->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-body-secondary py-3">No schedules yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $schedules->links() }}</div>
@endsection
