@extends('layouts.admin')

@section('title', 'Holidays')

@php($breadcrumbs = [['label' => 'Attendance'], ['label' => 'Holidays']])

@section('content')
    <x-admin.attendance-subnav active="holidays" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('attendance.manage') ? route('admin.attendance.holidays.create') : null"
        create-label="Add holiday"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Date</th>
                <th>Type</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($holidays as $holiday)
                <tr>
                    <td>{{ $holiday->name }}</td>
                    <td>{{ $holiday->company->name }}</td>
                    <td>{{ $holiday->date->format('M d, Y') }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $holiday->type->value)) }}</td>
                    <td>
                        @if ($holiday->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('attendance.manage')
                            <a href="{{ route('admin.attendance.holidays.edit', $holiday) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.attendance.holidays.destroy', $holiday) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $holiday->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No holidays yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $holidays->links() }}</div>
@endsection
