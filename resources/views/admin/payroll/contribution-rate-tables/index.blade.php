@extends('layouts.admin')

@section('title', 'Contribution Rate Tables')

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Contribution Rate Tables']])

@section('content')
    <x-admin.payroll-subnav active="contribution-rate-tables" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('payroll.create') ? route('admin.payroll.contribution-rate-tables.create') : null"
        create-label="Add table"
        error-key="contributionRateTable"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Agency</th>
                <th>Effective from</th>
                <th>Effective to</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($contributionRateTables as $table)
                <tr>
                    <td><a href="{{ route('admin.payroll.contribution-rate-tables.show', $table) }}">{{ $table->name }}</a></td>
                    <td>{{ $table->company->name }}</td>
                    <td>{{ strtoupper($table->agency->value) }}</td>
                    <td>{{ $table->effective_from->format('M d, Y') }}</td>
                    <td>{{ $table->effective_to?->format('M d, Y') ?? '—' }}</td>
                    <td>
                        @if ($table->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.payroll.contribution-rate-tables.show', $table) }}" class="btn btn-sm btn-outline-secondary">Brackets</a>
                        @can('payroll.create')
                            <a href="{{ route('admin.payroll.contribution-rate-tables.edit', $table) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.payroll.contribution-rate-tables.destroy', $table) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ $table->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-body-secondary py-3">No contribution rate tables yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $contributionRateTables->links() }}</div>
@endsection
