@extends('layouts.admin')

@section('title', 'Payroll Report')

@php($breadcrumbs = [['label' => 'Reports', 'url' => route('admin.reports.index')], ['label' => 'Payroll Report']])

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="company_id" class="form-select" onchange="this.form.submit()">
                <option value="">All companies</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" {{ (int) $companyId === $company->id ? 'selected' : '' }}>
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="payroll_period_id" class="form-select" onchange="this.form.submit()">
                @forelse ($periods as $period)
                    <option value="{{ $period->id }}" {{ $selectedPeriod?->id === $period->id ? 'selected' : '' }}>
                        {{ $period->name }} ({{ ucwords(str_replace('_', ' ', $period->status->value)) }})
                    </option>
                @empty
                    <option value="">No payroll periods</option>
                @endforelse
            </select>
        </div>
    </form>

    @if (! $selectedPeriod)
        <div class="alert alert-secondary">No payroll periods to report on{{ $companyId ? ' for this company' : '' }}.</div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small text-uppercase">Employees Paid</div>
                        <div class="fs-3 fw-semibold">{{ $totals['employeeCount'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small text-uppercase">Gross Earnings</div>
                        <div class="fs-3 fw-semibold">{{ number_format($totals['grossEarnings'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small text-uppercase">Total Deductions</div>
                        <div class="fs-3 fw-semibold">{{ number_format($totals['totalDeductions'], 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-body-secondary small text-uppercase">Net Pay</div>
                        <div class="fs-3 fw-semibold">{{ number_format($totals['netPay'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header">Deductions by Category</div>
                    <div class="table-responsive">
                        <table class="table table-compact mb-0">
                            <tbody>
                                @forelse ($byDeductionCategory as $category => $amount)
                                    <tr>
                                        <td>{{ ucwords(str_replace('_', ' ', $category)) }}</td>
                                        <td class="text-end">{{ number_format($amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center text-body-secondary py-3">No deductions in this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
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
                                @forelse ($byAgency as $agency => $amounts)
                                    <tr>
                                        <td>{{ strtoupper($agency) }}</td>
                                        <td class="text-end">{{ number_format($amounts['employee'], 2) }}</td>
                                        <td class="text-end">{{ number_format($amounts['employer'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-body-secondary py-3">No contributions in this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header">Tax Withheld</div>
                    <div class="card-body">
                        <div class="fs-3 fw-semibold">{{ number_format($totals['tax'], 2) }}</div>
                        <div class="text-body-secondary small">Employer contributions: {{ number_format($totals['employerContributions'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mb-2">
            <a href="{{ route('admin.payroll.payroll-periods.show', $selectedPeriod) }}" class="btn btn-outline-secondary btn-sm">
                View period detail
            </a>
        </div>
    @endif

    <div class="card">
        <div class="card-header">Recent Periods</div>
        <div class="table-responsive">
            <table class="table table-compact mb-0">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Status</th>
                        <th class="text-end">Net Pay</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentPeriods as $row)
                        <tr>
                            <td>{{ $row['period']->name }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $row['period']->status->value)) }}</td>
                            <td class="text-end">{{ number_format($row['netPay'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-body-secondary py-3">No payroll periods yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
