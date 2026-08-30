@extends('layouts.admin')

@section('title', 'Employees')

@php($breadcrumbs = [['label' => 'Employees']])

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto flex-grow-1">
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search name, employee #, or email">
        </div>
        <div class="col-auto">
            <div class="form-check form-switch pt-2">
                <input class="form-check-input" type="checkbox" name="with_archived" value="1" id="with_archived"
                       {{ $withArchived ? 'checked' : '' }} onchange="this.form.submit()">
                <label class="form-check-label" for="with_archived">Show archived</label>
            </div>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Search</button>
        </div>
    </form>

    <x-admin.resource-index
        :create-url="auth()->user()->can('employees.create') ? route('admin.employees.create') : null"
        create-label="Add employee"
    >
        <thead>
            <tr>
                <th>Employee #</th>
                <th>Name</th>
                <th>Company</th>
                <th>Email</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $employee)
                <tr>
                    <td><code>{{ $employee->employee_number }}</code></td>
                    <td><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->full_name }}</a></td>
                    <td>{{ $employee->company->name }}</td>
                    <td>{{ $employee->email ?? '—' }}</td>
                    <td>
                        @if ($employee->isArchived())
                            <span class="badge text-bg-secondary">Archived</span>
                        @else
                            <span class="badge text-bg-success">Active</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.employees.show', $employee) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        @can('employees.update')
                            <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No employees yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $employees->links() }}</div>
@endsection
