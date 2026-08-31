@extends('layouts.admin')

@section('title', $payrollPeriod->name)

@php($breadcrumbs = [['label' => 'Payroll'], ['label' => 'Payroll Periods', 'url' => route('admin.payroll.payroll-periods.index')], ['label' => $payrollPeriod->name]])

@php($badgeClass = match ($payrollPeriod->status) {
    \App\Enums\PayrollPeriodStatus::Draft => 'text-bg-secondary',
    \App\Enums\PayrollPeriodStatus::Processing => 'text-bg-info',
    \App\Enums\PayrollPeriodStatus::ForReview, \App\Enums\PayrollPeriodStatus::ForApproval => 'text-bg-warning',
    \App\Enums\PayrollPeriodStatus::Approved, \App\Enums\PayrollPeriodStatus::Finalized, \App\Enums\PayrollPeriodStatus::Published => 'text-bg-primary',
    \App\Enums\PayrollPeriodStatus::Locked => 'text-bg-dark',
    \App\Enums\PayrollPeriodStatus::Cancelled => 'text-bg-danger',
})

@section('content')
    <x-admin.payroll-subnav active="payroll-periods" />

    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h5 mb-1">
                    {{ $payrollPeriod->name }}
                    <span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $payrollPeriod->status->value)) }}</span>
                </h1>
                <div class="text-body-secondary small">
                    {{ $payrollPeriod->company->name }} &middot; {{ $payrollPeriod->payrollGroup->name }}
                    &middot; {{ $payrollPeriod->start_date->format('M d, Y') }} &ndash; {{ $payrollPeriod->end_date->format('M d, Y') }}
                    &middot; Pay date {{ $payrollPeriod->pay_date->format('M d, Y') }}
                </div>
                @if ($payrollPeriod->processed_at)
                    <div class="text-body-secondary small">
                        Last processed {{ $payrollPeriod->processed_at->format('M d, Y g:i A') }}
                        @if ($payrollPeriod->processedBy) by {{ $payrollPeriod->processedBy->name }} @endif
                    </div>
                @endif
            </div>
            @can('payroll.process')
                @if (in_array($payrollPeriod->status, [\App\Enums\PayrollPeriodStatus::Draft, \App\Enums\PayrollPeriodStatus::ForReview], true))
                    <form method="POST" action="{{ route('admin.payroll.payroll-periods.process', $payrollPeriod) }}"
                          onsubmit="return confirm('{{ $payrollPeriod->status === \App\Enums\PayrollPeriodStatus::Draft ? 'Process this period?' : 'Reprocess this period? Existing computed amounts will be replaced.' }}');">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            {{ $payrollPeriod->status === \App\Enums\PayrollPeriodStatus::Draft ? 'Process payroll' : 'Reprocess payroll' }}
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Basic salary</th>
                        <th>Gross earnings</th>
                        <th>Contributions</th>
                        <th>Tax</th>
                        <th>Net pay</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payrollItems as $item)
                        <tr>
                            <td>{{ $item->employee->full_name }}</td>
                            <td>{{ number_format($item->basic_salary, 2) }}</td>
                            <td>{{ number_format($item->gross_earnings, 2) }}</td>
                            <td>{{ number_format($item->total_employee_contributions, 2) }}</td>
                            <td>{{ number_format($item->tax_amount, 2) }}</td>
                            <td><strong>{{ number_format($item->net_pay, 2) }}</strong></td>
                            <td class="text-end">
                                <a href="{{ route('admin.payroll.payroll-items.show', $item) }}" class="btn btn-sm btn-outline-secondary">Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-body-secondary py-3">
                                No payroll items yet -- process this period to calculate them.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
