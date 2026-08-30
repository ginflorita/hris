@extends('layouts.admin')

@section('title', 'Departments')

@php($breadcrumbs = [['label' => 'Organization'], ['label' => 'Departments']])

@section('content')
    <x-admin.org-subnav active="departments" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('organization.manage') ? route('admin.organization.departments.create') : null"
        create-label="Add department"
        error-key="department"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Division</th>
                <th>Code</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($departments as $department)
                <tr>
                    <td>{{ $department->name }}</td>
                    <td>{{ $department->company->name }}</td>
                    <td>{{ $department->division?->name ?? '—' }}</td>
                    <td><code>{{ $department->code }}</code></td>
                    <td>
                        @if ($department->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('organization.manage')
                            <a href="{{ route('admin.organization.departments.edit', $department) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.organization.departments.destroy', $department) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $department->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No departments yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $departments->links() }}</div>
@endsection
