@extends('layouts.admin')

@section('title', 'Leave Types')

@php($breadcrumbs = [['label' => 'Leave'], ['label' => 'Leave Types']])

@section('content')
    <x-admin.leave-subnav active="types" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('leave.create') ? route('admin.leave.types.create') : null"
        create-label="Add leave type"
        error-key="leaveType"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Code</th>
                <th>Paid</th>
                <th>Max days/year</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leaveTypes as $leaveType)
                <tr>
                    <td>{{ $leaveType->name }}</td>
                    <td>{{ $leaveType->company->name }}</td>
                    <td><code>{{ $leaveType->code }}</code></td>
                    <td>{{ $leaveType->is_paid ? 'Yes' : 'No' }}</td>
                    <td>{{ $leaveType->max_days_per_year ?? '—' }}</td>
                    <td>
                        @if ($leaveType->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('leave.create')
                            <a href="{{ route('admin.leave.types.edit', $leaveType) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.leave.types.destroy', $leaveType) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $leaveType->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-body-secondary py-3">No leave types yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $leaveTypes->links() }}</div>
@endsection
