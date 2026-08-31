@extends('layouts.admin')

@section('title', $taxTable->name)

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Tax Tables', 'url' => route('admin.payroll.tax-tables.index')], ['label' => $taxTable->name]])

@section('content')
    <x-admin.payroll-subnav active="tax-tables" />

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h5 mb-1">{{ $taxTable->name }}</h1>
                <div class="text-body-secondary small">
                    {{ $taxTable->company->name }}
                    &middot; Effective {{ $taxTable->effective_from->format('M d, Y') }}
                    @if ($taxTable->effective_to)
                        &ndash; {{ $taxTable->effective_to->format('M d, Y') }}
                    @endif
                </div>
            </div>
            @can('payroll.create')
                <a href="{{ route('admin.payroll.tax-tables.edit', $taxTable) }}" class="btn btn-sm btn-outline-secondary">Edit table</a>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @can('payroll.create')
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBracketModal">Add bracket</button>
        </div>
    @endcan

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Income range</th>
                        <th>Base tax</th>
                        <th>Excess rate</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($taxTable->brackets as $bracket)
                        <tr>
                            <td>{{ $bracket->order }}</td>
                            <td>{{ number_format($bracket->min_income, 2) }} &ndash; {{ $bracket->max_income !== null ? number_format($bracket->max_income, 2) : 'and up' }}</td>
                            <td>{{ number_format($bracket->base_tax, 2) }}</td>
                            <td>{{ number_format($bracket->excess_rate_percent, 2) }}%</td>
                            <td class="text-end">
                                @can('payroll.create')
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editBracketModal{{ $bracket->id }}">Edit</button>
                                    <form method="POST" action="{{ route('admin.payroll.tax-tables.brackets.destroy', [$taxTable, $bracket]) }}" class="d-inline"
                                          onsubmit="return confirm('Remove this bracket?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-body-secondary py-3">No brackets yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('payroll.create')
        @include('admin.payroll.tax-tables._bracket-modal', ['bracket' => null, 'modalId' => 'addBracketModal'])
        @foreach ($taxTable->brackets as $bracket)
            @include('admin.payroll.tax-tables._bracket-modal', ['bracket' => $bracket, 'modalId' => 'editBracketModal'.$bracket->id])
        @endforeach
    @endcan
@endsection
