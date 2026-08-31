@extends('layouts.portal')

@section('title', $payrollItem->payrollPeriod->name)

@php($breadcrumbs = [['label' => 'My Payslips', 'url' => route('portal.payslips.index')], ['label' => $payrollItem->payrollPeriod->name]])

@section('content')
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h5 mb-1">{{ $payrollItem->payrollPeriod->name }}</h1>
                <div class="text-body-secondary small">
                    {{ $payrollItem->payrollPeriod->start_date->format('M d, Y') }} &ndash; {{ $payrollItem->payrollPeriod->end_date->format('M d, Y') }}
                    &middot; Pay date {{ $payrollItem->payrollPeriod->pay_date->format('M d, Y') }}
                </div>
            </div>
            <a href="{{ route('portal.payslips.download', $payrollItem) }}" class="btn btn-primary btn-sm">Download PDF</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">Earnings</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <tbody>
                            @foreach ($payrollItem->lines->where('type', \App\Enums\PayrollItemLineType::Earning) as $line)
                                <tr>
                                    <td>{{ $line->label }}</td>
                                    <td class="text-end">{{ number_format($line->amount, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-semibold">
                                <td>Gross Pay</td>
                                <td class="text-end">{{ number_format($payrollItem->gross_earnings, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">Deductions</div>
                <div class="table-responsive">
                    <table class="table table-compact mb-0">
                        <tbody>
                            @foreach ($payrollItem->contributions as $contribution)
                                <tr>
                                    <td>{{ strtoupper($contribution->agency->value) }}</td>
                                    <td class="text-end">{{ number_format($contribution->employee_amount, 2) }}</td>
                                </tr>
                            @endforeach
                            @if ($payrollItem->tax_amount > 0)
                                <tr>
                                    <td>Withholding Tax</td>
                                    <td class="text-end">{{ number_format($payrollItem->tax_amount, 2) }}</td>
                                </tr>
                            @endif
                            @foreach ($payrollItem->lines->where('type', \App\Enums\PayrollItemLineType::Deduction) as $line)
                                <tr>
                                    <td>{{ $line->label }}</td>
                                    <td class="text-end">{{ number_format($line->amount, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="fw-semibold">
                                <td>Total Deductions</td>
                                <td class="text-end">{{ number_format($payrollItem->total_employee_contributions + $payrollItem->tax_amount + $payrollItem->total_deductions, 2) }}</td>
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
                                <td>Gross Pay</td>
                                <td class="text-end">{{ number_format($payrollItem->gross_earnings, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Total Deductions</td>
                                <td class="text-end">-{{ number_format($payrollItem->total_employee_contributions + $payrollItem->tax_amount + $payrollItem->total_deductions, 2) }}</td>
                            </tr>
                            <tr class="fw-semibold fs-5">
                                <td>Net Pay</td>
                                <td class="text-end">{{ number_format($payrollItem->net_pay, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
