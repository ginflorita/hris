@extends('layouts.admin')

@section('title', 'Shifts')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Shifts']])

@section('content')
    <x-admin.attendance-subnav active="shifts" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('attendance.manage') ? route('admin.attendance.shifts.create') : null"
        create-label="Add shift"
        error-key="shift"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Code</th>
                <th>Time</th>
                <th>Grace</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($shifts as $shift)
                <tr>
                    <td>{{ $shift->name }} @if($shift->is_night_shift)<span class="badge text-bg-dark">Night</span>@endif</td>
                    <td>{{ $shift->company->name }}</td>
                    <td><code>{{ $shift->code }}</code></td>
                    <td>{{ $shift->start_time }} – {{ $shift->end_time }}</td>
                    <td>{{ $shift->grace_minutes }} min</td>
                    <td>
                        @if ($shift->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('attendance.manage')
                            <a href="{{ route('admin.attendance.shifts.edit', $shift) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.attendance.shifts.destroy', $shift) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $shift->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-body-secondary py-3">No shifts yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $shifts->links() }}</div>
@endsection
