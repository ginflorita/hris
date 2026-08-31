@extends('layouts.admin')

@section('title', 'Payroll Groups')

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Payroll Groups']])

@section('content')
    <x-admin.payroll-subnav active="payroll-groups" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('payroll.create') ? route('admin.payroll.payroll-groups.create') : null"
        create-label="Add group"
        error-key="payrollGroup"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Code</th>
                <th>Pay frequency</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payrollGroups as $group)
                <tr>
                    <td>{{ $group->name }}</td>
                    <td>{{ $group->company->name }}</td>
                    <td><code>{{ $group->code }}</code></td>
                    <td>{{ ucwords(str_replace('_', ' ', $group->pay_frequency->value)) }}</td>
                    <td>
                        @if ($group->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('payroll.create')
                            <a href="{{ route('admin.payroll.payroll-groups.edit', $group) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.payroll.payroll-groups.destroy', $group) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $group->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-body-secondary py-3">No payroll groups yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $payrollGroups->links() }}</div>
@endsection
