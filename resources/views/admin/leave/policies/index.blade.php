@extends('layouts.admin')

@section('title', 'Leave Policies')

@php($breadcrumbs = [['label' => 'Leave'], ['label' => 'Policies']])

@section('content')
    <x-admin.leave-subnav active="policies" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('leave.create') ? route('admin.leave.policies.create') : null"
        create-label="Add policy"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Leave type</th>
                <th>Accrual</th>
                <th>Max balance</th>
                <th>Carry-over</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leavePolicies as $leavePolicy)
                <tr>
                    <td>{{ $leavePolicy->name }}</td>
                    <td>{{ $leavePolicy->company->name }}</td>
                    <td>{{ $leavePolicy->leaveType->name }}</td>
                    <td>{{ $leavePolicy->accrual_rate }} / {{ ucwords(str_replace('_', ' ', $leavePolicy->accrual_frequency->value)) }}</td>
                    <td>{{ $leavePolicy->max_balance ?? '—' }}</td>
                    <td>{{ $leavePolicy->carry_over_days ?? '—' }}</td>
                    <td>
                        @if ($leavePolicy->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('leave.create')
                            <a href="{{ route('admin.leave.policies.edit', $leavePolicy) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.leave.policies.destroy', $leavePolicy) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $leavePolicy->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-body-secondary py-3">No leave policies yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $leavePolicies->links() }}</div>
@endsection
