@extends('layouts.admin')

@section('title', 'Tax Tables')

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Tax Tables']])

@section('content')
    <x-admin.payroll-subnav active="tax-tables" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('payroll.create') ? route('admin.payroll.tax-tables.create') : null"
        create-label="Add table"
        error-key="taxTable"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Effective from</th>
                <th>Effective to</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($taxTables as $table)
                <tr>
                    <td><a href="{{ route('admin.payroll.tax-tables.show', $table) }}">{{ $table->name }}</a></td>
                    <td>{{ $table->company->name }}</td>
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
                        <a href="{{ route('admin.payroll.tax-tables.show', $table) }}" class="btn btn-sm btn-outline-secondary">Brackets</a>
                        @can('payroll.create')
                            <a href="{{ route('admin.payroll.tax-tables.edit', $table) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('admin.payroll.tax-tables.destroy', $table) }}" class="d-inline"
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
                    <td colspan="6" class="text-center text-body-secondary py-3">No tax tables yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $taxTables->links() }}</div>
@endsection
