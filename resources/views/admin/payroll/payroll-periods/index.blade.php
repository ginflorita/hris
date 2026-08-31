@extends('layouts.admin')

@section('title', 'Payroll Periods')

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Payroll Periods']])

@section('content')
    <x-admin.payroll-subnav active="payroll-periods" />

    <x-admin.resource-index
        :create-url="auth()->user()->can('payroll.create') ? route('admin.payroll.payroll-periods.create') : null"
        create-label="Add period"
        error-key="payrollPeriod"
    >
        <thead>
            <tr>
                <th>Name</th>
                <th>Company</th>
                <th>Payroll group</th>
                <th>Period</th>
                <th>Pay date</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payrollPeriods as $period)
                @php($badgeClass = match ($period->status) {
                    \App\Enums\PayrollPeriodStatus::Draft => 'text-bg-secondary',
                    \App\Enums\PayrollPeriodStatus::Processing => 'text-bg-info',
                    \App\Enums\PayrollPeriodStatus::ForReview, \App\Enums\PayrollPeriodStatus::ForApproval => 'text-bg-warning',
                    \App\Enums\PayrollPeriodStatus::Approved, \App\Enums\PayrollPeriodStatus::Finalized, \App\Enums\PayrollPeriodStatus::Published => 'text-bg-primary',
                    \App\Enums\PayrollPeriodStatus::Locked => 'text-bg-dark',
                    \App\Enums\PayrollPeriodStatus::Cancelled => 'text-bg-danger',
                })
                <tr>
                    <td><a href="{{ route('admin.payroll.payroll-periods.show', $period) }}">{{ $period->name }}</a></td>
                    <td>{{ $period->company->name }}</td>
                    <td>{{ $period->payrollGroup->name }}</td>
                    <td>{{ $period->start_date->format('M d, Y') }} &ndash; {{ $period->end_date->format('M d, Y') }}</td>
                    <td>{{ $period->pay_date->format('M d, Y') }}</td>
                    <td><span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $period->status->value)) }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('admin.payroll.payroll-periods.show', $period) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        @can('payroll.create')
                            @if ($period->status === \App\Enums\PayrollPeriodStatus::Draft)
                                <a href="{{ route('admin.payroll.payroll-periods.edit', $period) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.payroll.payroll-periods.destroy', $period) }}" class="d-inline"
                                      onsubmit="return confirm('Delete {{ $period->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-body-secondary py-3">No payroll periods yet.</td>
                </tr>
            @endforelse
        </tbody>
    </x-admin.resource-index>

    <div class="mt-3">{{ $payrollPeriods->links() }}</div>
@endsection
