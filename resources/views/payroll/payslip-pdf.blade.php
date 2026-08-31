<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip -- {{ $payrollItem->employee->full_name }} -- {{ $payrollItem->payrollPeriod->name }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #212529; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #6c757d; }
        .header { border-bottom: 2px solid #212529; padding-bottom: 8px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { padding: 4px 6px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: #f1f3f5; font-size: 10px; text-transform: uppercase; }
        .text-end { text-align: right; }
        .totals td { border-bottom: none; padding-top: 6px; }
        .net-pay { font-size: 14px; font-weight: bold; border-top: 2px solid #212529; }
        .section-title { font-size: 12px; font-weight: bold; margin: 0 0 4px; }
        .cols { width: 100%; }
        .cols td { border: none; padding: 0; vertical-align: top; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $payrollItem->company->name }}</h1>
        <div class="muted">Payslip &middot; {{ $payrollItem->payrollPeriod->name }}
            ({{ $payrollItem->payrollPeriod->start_date->format('M d, Y') }} &ndash; {{ $payrollItem->payrollPeriod->end_date->format('M d, Y') }})
            &middot; Pay date {{ $payrollItem->payrollPeriod->pay_date->format('M d, Y') }}
        </div>
    </div>

    <table class="cols">
        <tr>
            <td style="width: 50%;">
                <strong>{{ $payrollItem->employee->full_name }}</strong><br>
                <span class="muted">{{ $payrollItem->employee->employee_number }}</span>
            </td>
            <td style="width: 50%;" class="text-end">
                <span class="muted">Generated {{ now()->format('M d, Y g:i A') }}</span>
            </td>
        </tr>
    </table>

    <div class="section-title">Earnings</div>
    <table>
        <thead>
            <tr><th>Description</th><th class="text-end">Amount</th></tr>
        </thead>
        <tbody>
            @foreach ($payrollItem->lines->where('type', \App\Enums\PayrollItemLineType::Earning) as $line)
                <tr>
                    <td>{{ $line->label }}</td>
                    <td class="text-end">{{ number_format($line->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <td><strong>Gross Pay</strong></td>
                <td class="text-end"><strong>{{ number_format($payrollItem->gross_earnings, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">Deductions</div>
    <table>
        <thead>
            <tr><th>Description</th><th class="text-end">Amount</th></tr>
        </thead>
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
        </tbody>
        <tfoot>
            <tr class="totals">
                <td><strong>Total Deductions</strong></td>
                <td class="text-end"><strong>{{ number_format($payrollItem->total_employee_contributions + $payrollItem->tax_amount + $payrollItem->total_deductions, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <table>
        <tr class="totals">
            <td>Gross Pay</td>
            <td class="text-end">{{ number_format($payrollItem->gross_earnings, 2) }}</td>
        </tr>
        <tr class="totals">
            <td>Total Deductions</td>
            <td class="text-end">-{{ number_format($payrollItem->total_employee_contributions + $payrollItem->tax_amount + $payrollItem->total_deductions, 2) }}</td>
        </tr>
        <tr class="net-pay">
            <td>Net Pay</td>
            <td class="text-end">{{ number_format($payrollItem->net_pay, 2) }}</td>
        </tr>
    </table>

    <p class="muted" style="margin-top: 20px;">
        This is a system-generated payslip and does not require a signature.
    </p>
</body>
</html>
