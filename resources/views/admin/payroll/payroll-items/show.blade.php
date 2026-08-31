@extends('layouts.admin')

@section('title', $payrollItem->employee->full_name.' -- '.$payrollItem->payrollPeriod->name)

@php($breadcrumbs = [
    ['label' => 'Payroll'],
    ['label' => 'Payroll Periods', 'url' => route('admin.payroll.payroll-periods.index')],
    ['label' => $payrollItem->payrollPeriod->name, 'url' => route('admin.payroll.payroll-periods.show', $payrollItem->payrollPeriod)],
    ['label' => $payrollItem->employee->full_name],
])

@php($reviewable = $payrollItem->payrollPeriod->status === \App\Enums\PayrollPeriodStatus::ForReview)
@php($issues = $payrollItem->validationIssues())

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h5 mb-1">{{ $payrollItem->employee->full_name }}</h1>
                <div class="text-body-secondary small">
                    {{ $payrollItem->payrollPeriod->name }}
                    ({{ $payrollItem->payrollPeriod->start_date->format('M d, Y') }} &ndash; {{ $payrollItem->payrollPeriod->end_date->format('M d, Y') }})
                    &middot; Computed {{ $payrollItem->computed_at->format('M d, Y g:i A') }}
                </div>
            </div>
            @can('payroll.create')
                @if ($reviewable)
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAdjustmentModal">Add adjustment</button>
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
    @foreach ($issues as $issue)
        <div class="alert alert-warning">{{ $issue }}</div>
    @endforeach

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">Earnings</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <tbody>
                            @foreach ($payrollItem->lines->where('type', \App\Enums\PayrollItemLineType::Earning) as $line)
                                <tr>
                                    <td>
                                        {{ $line->label }}
                                        @if ($line->is_adjustment)
                                            <span class="badge text-bg-info" title="{{ $line->remarks }}">Adjustment</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($line->amount, 2) }}</td>
                                    <td class="text-end" style="width: 1%;">
                                        @can('payroll.create')
                                            @if ($line->is_adjustment && $reviewable)
                                                <form method="POST" action="{{ route('admin.payroll.payroll-items.adjustments.destroy', [$payrollItem, $line]) }}"
                                                      onsubmit="return confirm('Remove this adjustment?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="fw-semibold">
                                <td>Gross earnings</td>
                                <td class="text-end">{{ number_format($payrollItem->gross_earnings, 2) }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card mb-3">
                <div class="card-header">Government Contributions</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <thead>
                            <tr>
                                <th>Agency</th>
                                <th class="text-end">Employee</th>
                                <th class="text-end">Employer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payrollItem->contributions as $contribution)
                                <tr>
                                    <td>{{ strtoupper($contribution->agency->value) }}</td>
                                    <td class="text-end">{{ number_format($contribution->employee_amount, 2) }}</td>
                                    <td class="text-end">{{ number_format($contribution->employer_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-body-secondary py-3">No contribution tables matched this period.</td>
                                </tr>
                            @endforelse
                            <tr class="fw-semibold">
                                <td>Total (employee share)</td>
                                <td class="text-end">{{ number_format($payrollItem->total_employee_contributions, 2) }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Deductions</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <tbody>
                            @forelse ($payrollItem->lines->where('type', \App\Enums\PayrollItemLineType::Deduction) as $line)
                                <tr>
                                    <td>
                                        {{ $line->label }}
                                        @if ($line->is_adjustment)
                                            <span class="badge text-bg-info" title="{{ $line->remarks }}">Adjustment</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($line->amount, 2) }}</td>
                                    <td class="text-end" style="width: 1%;">
                                        @can('payroll.create')
                                            @if ($line->is_adjustment && $reviewable)
                                                <form method="POST" action="{{ route('admin.payroll.payroll-items.adjustments.destroy', [$payrollItem, $line]) }}"
                                                      onsubmit="return confirm('Remove this adjustment?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-body-secondary py-3">No deductions.</td>
                                </tr>
                            @endforelse
                            <tr class="fw-semibold">
                                <td>Total</td>
                                <td class="text-end">{{ number_format($payrollItem->total_deductions, 2) }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">Summary</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <tbody>
                            <tr>
                                <td>Basic salary (this period)</td>
                                <td class="text-end">{{ number_format($payrollItem->basic_salary, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Gross earnings</td>
                                <td class="text-end">{{ number_format($payrollItem->gross_earnings, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Government contributions (employee share)</td>
                                <td class="text-end">&minus;{{ number_format($payrollItem->total_employee_contributions, 2) }}</td>
                            </tr>
                            <tr>
                                <td>
                                    Withholding tax
                                    @if ($payrollItem->taxTable)
                                        <span class="text-body-secondary small">({{ $payrollItem->taxTable->name }})</span>
                                    @endif
                                </td>
                                <td class="text-end">&minus;{{ number_format($payrollItem->tax_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Other deductions</td>
                                <td class="text-end">&minus;{{ number_format($payrollItem->total_deductions, 2) }}</td>
                            </tr>
                            <tr class="fw-semibold fs-5">
                                <td>Net pay</td>
                                <td class="text-end">{{ number_format($payrollItem->net_pay, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @can('payroll.create')
        @if ($reviewable)
            <div class="modal fade" id="addAdjustmentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.payroll.payroll-items.adjustments.store', $payrollItem) }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Add adjustment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Type</label>
                                    <select name="type" class="form-select" required>
                                        <option value="earning">Earning (adds to gross)</option>
                                        <option value="deduction">Deduction (subtracts from net)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Label</label>
                                    <input type="text" name="label" class="form-control" placeholder="e.g. Loan repayment" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Amount</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reason</label>
                                    <textarea name="remarks" rows="2" class="form-control" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan
@endsection
