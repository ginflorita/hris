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
                @if ($payrollPeriod->finalized_at)
                    <div class="text-body-secondary small">
                        Finalized {{ $payrollPeriod->finalized_at->format('M d, Y g:i A') }}
                        @if ($payrollPeriod->finalizedBy) by {{ $payrollPeriod->finalizedBy->name }} @endif
                        -- immutable from this point on.
                    </div>
                @endif
                @if ($payrollPeriod->published_at)
                    <div class="text-body-secondary small">
                        Published {{ $payrollPeriod->published_at->format('M d, Y g:i A') }}
                        @if ($payrollPeriod->publishedBy) by {{ $payrollPeriod->publishedBy->name }} @endif
                        -- payslips are visible to employees.
                    </div>
                @endif
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @can('payroll.process')
                    @if (in_array($payrollPeriod->status, [\App\Enums\PayrollPeriodStatus::Draft, \App\Enums\PayrollPeriodStatus::ForReview], true))
                        <form method="POST" action="{{ route('admin.payroll.payroll-periods.process', $payrollPeriod) }}"
                              onsubmit="return confirm('{{ $payrollPeriod->status === \App\Enums\PayrollPeriodStatus::Draft ? 'Process this period?' : 'Reprocess this period? Existing computed amounts will be replaced.' }}');">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                {{ $payrollPeriod->status === \App\Enums\PayrollPeriodStatus::Draft ? 'Process payroll' : 'Reprocess payroll' }}
                            </button>
                        </form>
                        @if ($payrollPeriod->status === \App\Enums\PayrollPeriodStatus::ForReview)
                            <form method="POST" action="{{ route('admin.payroll.payroll-periods.submit-for-approval', $payrollPeriod) }}"
                                  onsubmit="return confirm('Submit this period for approval?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm">Submit for approval</button>
                            </form>
                        @endif
                    @endif
                @endcan
                @can('payroll.approve')
                    @if ($payrollPeriod->status === \App\Enums\PayrollPeriodStatus::ForApproval)
                        <form method="POST" action="{{ route('admin.payroll.payroll-periods.approve', $payrollPeriod) }}"
                              onsubmit="return confirm('Approve this payroll period?');">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                        </form>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">Reject</button>
                    @endif
                @endcan
                @can('payroll.finalize')
                    @if ($payrollPeriod->status === \App\Enums\PayrollPeriodStatus::Approved)
                        <form method="POST" action="{{ route('admin.payroll.payroll-periods.finalize', $payrollPeriod) }}"
                              onsubmit="return confirm('Finalize this payroll period? It becomes immutable -- no more adjustments or reprocessing.');">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">Finalize</button>
                        </form>
                    @endif
                @endcan
                @can('payroll.lock')
                    @if ($payrollPeriod->status === \App\Enums\PayrollPeriodStatus::Finalized)
                        <form method="POST" action="{{ route('admin.payroll.payroll-periods.lock', $payrollPeriod) }}"
                              onsubmit="return confirm('Lock this payroll period?');">
                            @csrf
                            <button type="submit" class="btn btn-dark btn-sm">Lock</button>
                        </form>
                    @endif
                    @if ($payrollPeriod->status === \App\Enums\PayrollPeriodStatus::Locked)
                        <form method="POST" action="{{ route('admin.payroll.payroll-periods.publish', $payrollPeriod) }}"
                              onsubmit="return confirm('Publish this payroll period? Payslips become visible to employees.');">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">Publish</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @if ($payrollPeriod->status === \App\Enums\PayrollPeriodStatus::ForReview && $payrollPeriod->rejection_reason)
        <div class="alert alert-warning">
            <strong>Sent back for review:</strong> {{ $payrollPeriod->rejection_reason }}
        </div>
    @endif

    @can('payroll.approve')
        @if ($payrollPeriod->status === \App\Enums\PayrollPeriodStatus::ForApproval)
            <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.payroll.payroll-periods.reject', $payrollPeriod) }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Reject payroll period</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label">Reason</label>
                                <textarea name="rejection_reason" rows="3" class="form-control" required></textarea>
                                <div class="form-text">Sends the period back to For Review so it can be corrected.</div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger">Reject</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan

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
                        @php($issues = $item->validationIssues())
                        <tr>
                            <td>
                                {{ $item->employee->full_name }}
                                @if ($issues)
                                    <span class="badge text-bg-danger" title="{{ implode(' ', $issues) }}">{{ count($issues) }} issue{{ count($issues) > 1 ? 's' : '' }}</span>
                                @endif
                            </td>
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
                @if ($payrollItems->isNotEmpty())
                    <tfoot>
                        <tr class="fw-semibold">
                            <td colspan="5">{{ $payrollItems->count() }} employee(s)</td>
                            <td>{{ number_format($payrollItems->sum('net_pay'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
@endsection
