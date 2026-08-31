<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Employment -- {{ $coeRequest->employee->full_name }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #212529; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #6c757d; }
        .header { border-bottom: 2px solid #212529; padding-bottom: 8px; margin-bottom: 24px; }
        .title { text-align: center; font-size: 15px; font-weight: bold; text-decoration: underline; margin-bottom: 24px; }
        .body-text { line-height: 1.8; text-align: justify; margin-bottom: 16px; }
        .signature { margin-top: 60px; }
        .signature .line { width: 240px; border-top: 1px solid #212529; padding-top: 4px; }
        .footer { margin-top: 40px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $coeRequest->company->name }}</h1>
        @if ($coeRequest->company->address)
            <div class="muted">{{ $coeRequest->company->address }}</div>
        @endif
    </div>

    <div class="title">Certificate of Employment</div>

    <div class="body-text">TO WHOM IT MAY CONCERN:</div>

    <div class="body-text">
        This is to certify that <strong>{{ $coeRequest->employee->full_name }}</strong>
        is {{ $coeRequest->snapshot_employment_status === 'active' ? 'an' : 'a former' }} employee of
        <strong>{{ $coeRequest->company->name }}</strong>,
        @if ($coeRequest->snapshot_position)
            holding the position of <strong>{{ $coeRequest->snapshot_position }}</strong>
            @if ($coeRequest->snapshot_department)
                under the <strong>{{ $coeRequest->snapshot_department }}</strong> department,
            @endif
        @endif
        @if ($coeRequest->snapshot_date_hired)
            since <strong>{{ $coeRequest->snapshot_date_hired->format('F d, Y') }}</strong>.
        @else
            with this company.
        @endif
    </div>

    @if ($coeRequest->type->value === 'with_compensation' && $coeRequest->snapshot_monthly_salary !== null)
        <div class="body-text">
            {{ $coeRequest->employee->full_name }} receives a monthly compensation of
            <strong>{{ number_format($coeRequest->snapshot_monthly_salary, 2) }}</strong>.
        </div>
    @endif

    <div class="body-text">
        This certification is issued upon the request of the employee
        @if ($coeRequest->purpose)
            for the purpose of <strong>{{ $coeRequest->purpose }}</strong>,
        @endif
        this <strong>{{ $coeRequest->approved_at->format('jS') }}</strong> day of
        <strong>{{ $coeRequest->approved_at->format('F, Y') }}</strong> at
        {{ $coeRequest->company->address ?? $coeRequest->company->name }}.
    </div>

    <div class="signature">
        <div class="line">Authorized Signatory</div>
    </div>

    <div class="footer muted">
        Certificate reference #{{ $coeRequest->id }} &middot; Generated {{ now()->format('M d, Y g:i A') }}
    </div>
</body>
</html>
