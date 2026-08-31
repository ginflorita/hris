@extends('layouts.admin')

@section('title', $payrollItem->employee->full_name.' -- '.$payrollItem->payrollPeriod->name)

@php($breadcrumbs = [
    ['label' => 'Payroll'],
    ['label' => 'Payroll Periods', 'url' => route('admin.payroll.payroll-periods.index')],
    ['label' => $payrollItem->payrollPeriod->name, 'url' => route('admin.payroll.payroll-periods.show', $payrollItem->payrollPeriod)],
    ['label' => $payrollItem->employee->full_name],
])

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <h1 class="h5 mb-1">{{ $payrollItem->employee->full_name }}</h1>
            <div class="text-body-secondary small">
                {{ $payrollItem->payrollPeriod->name }}
                ({{ $payrollItem->payrollPeriod->start_date->format('M d, Y') }} &ndash; {{ $payrollItem->payrollPeriod->end_date->format('M d, Y') }})
                &middot; Computed {{ $payrollItem->computed_at->format('M d, Y g:i A') }}
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">Earnings</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <tbody>
                            @foreach ($payrollItem->lines->where('type', \App\Enums\PayrollItemLineType::Earning) as $line)
                                <tr>
                                    <td>
                                        {{ $line->label }}
                                        @if ($line->is_adjustment)
                                            <span class="badge text-bg-info">Adjustment</span>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($line->amount, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-semibold">
                                <td>Gross earnings</td>
                                <td class="text-end">{{ number_format($payrollItem->gross_earnings, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
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
@endsection
