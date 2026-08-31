@php($payrollPeriod = $payrollPeriod ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $payrollPeriod?->company_id) === $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
    @error('company_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="payroll_group_id">Payroll group</label>
    <select id="payroll_group_id" name="payroll_group_id" class="form-select @error('payroll_group_id') is-invalid @enderror" required>
        <option value="">Select a payroll group</option>
        @foreach ($payrollGroups as $group)
            <option value="{{ $group->id }}" {{ (int) old('payroll_group_id', $payrollPeriod?->payroll_group_id) === $group->id ? 'selected' : '' }}>
                {{ $group->name }} ({{ $group->company->name }})
            </option>
        @endforeach
    </select>
    <div class="form-text">Must belong to the selected company.</div>
    @error('payroll_group_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="name">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $payrollPeriod?->name) }}"
           class="form-control @error('name') is-invalid @enderror" placeholder="e.g. January 2026" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-4 mb-3">
        <label class="form-label" for="start_date">Start date</label>
        <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $payrollPeriod?->start_date?->format('Y-m-d')) }}"
               class="form-control @error('start_date') is-invalid @enderror" required>
        @error('start_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-4 mb-3">
        <label class="form-label" for="end_date">End date</label>
        <input id="end_date" type="date" name="end_date" value="{{ old('end_date', $payrollPeriod?->end_date?->format('Y-m-d')) }}"
               class="form-control @error('end_date') is-invalid @enderror" required>
        @error('end_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-4 mb-3">
        <label class="form-label" for="pay_date">Pay date</label>
        <input id="pay_date" type="date" name="pay_date" value="{{ old('pay_date', $payrollPeriod?->pay_date?->format('Y-m-d')) }}"
               class="form-control @error('pay_date') is-invalid @enderror" required>
        @error('pay_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
