@extends('layouts.admin')

@section('title', $contributionRateTable->name)

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Contribution Rate Tables', 'url' => route('admin.payroll.contribution-rate-tables.index')], ['label' => $contributionRateTable->name]])

@section('content')
    <x-admin.payroll-subnav active="contribution-rate-tables" />

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h5 mb-1">{{ $contributionRateTable->name }}</h1>
                <div class="text-body-secondary small">
                    {{ $contributionRateTable->company->name }} &middot; {{ strtoupper($contributionRateTable->agency->value) }}
                    &middot; Effective {{ $contributionRateTable->effective_from->format('M d, Y') }}
                    @if ($contributionRateTable->effective_to)
                        &ndash; {{ $contributionRateTable->effective_to->format('M d, Y') }}
                    @endif
                </div>
            </div>
            @can('payroll.create')
                <a href="{{ route('admin.payroll.contribution-rate-tables.edit', $contributionRateTable) }}" class="btn btn-sm btn-outline-secondary">Edit table</a>
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
                        <th>Salary range</th>
                        <th>Employee share</th>
                        <th>Employer share</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contributionRateTable->brackets as $bracket)
                        <tr>
                            <td>{{ $bracket->order }}</td>
                            <td>{{ number_format($bracket->min_salary, 2) }} &ndash; {{ $bracket->max_salary !== null ? number_format($bracket->max_salary, 2) : 'and up' }}</td>
                            <td>{{ number_format($bracket->employee_amount, 2) }}</td>
                            <td>{{ number_format($bracket->employer_amount, 2) }}</td>
                            <td class="text-end">
                                @can('payroll.create')
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editBracketModal{{ $bracket->id }}">Edit</button>
                                    <form method="POST" action="{{ route('admin.payroll.contribution-rate-tables.brackets.destroy', [$contributionRateTable, $bracket]) }}" class="d-inline"
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
        @include('admin.payroll.contribution-rate-tables._bracket-modal', ['bracket' => null, 'modalId' => 'addBracketModal'])
        @foreach ($contributionRateTable->brackets as $bracket)
            @include('admin.payroll.contribution-rate-tables._bracket-modal', ['bracket' => $bracket, 'modalId' => 'editBracketModal'.$bracket->id])
        @endforeach
    @endcan
@endsection
