@php($leavePolicy = $leavePolicy ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $leavePolicy?->company_id) === $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
    @error('company_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="leave_type_id">Leave type</label>
    <select id="leave_type_id" name="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required>
        <option value="">Select a leave type</option>
        @foreach ($leaveTypes as $leaveType)
            <option value="{{ $leaveType->id }}" {{ (int) old('leave_type_id', $leavePolicy?->leave_type_id) === $leaveType->id ? 'selected' : '' }}>
                {{ $leaveType->name }} ({{ $leaveType->company->name }})
            </option>
        @endforeach
    </select>
    <div class="form-text">Must belong to the selected company.</div>
    @error('leave_type_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="name">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $leavePolicy?->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-6 mb-3">
        <label class="form-label" for="accrual_rate">Accrual rate (days)</label>
        <input id="accrual_rate" type="number" step="0.01" min="0" max="31" name="accrual_rate" value="{{ old('accrual_rate', $leavePolicy?->accrual_rate) }}"
               class="form-control @error('accrual_rate') is-invalid @enderror" required>
        @error('accrual_rate')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-6 mb-3">
        <label class="form-label" for="accrual_frequency">Accrual frequency</label>
        <select id="accrual_frequency" name="accrual_frequency" class="form-select @error('accrual_frequency') is-invalid @enderror" required>
            @foreach (\App\Enums\AccrualFrequency::cases() as $case)
                <option value="{{ $case->value }}" {{ old('accrual_frequency', $leavePolicy?->accrual_frequency?->value) === $case->value ? 'selected' : '' }}>
                    {{ ucwords(str_replace('_', ' ', $case->value)) }}
                </option>
            @endforeach
        </select>
        @error('accrual_frequency')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-6 mb-3">
        <label class="form-label" for="max_balance">Max balance</label>
        <input id="max_balance" type="number" step="0.01" min="0" name="max_balance" value="{{ old('max_balance', $leavePolicy?->max_balance) }}"
               class="form-control @error('max_balance') is-invalid @enderror">
        @error('max_balance')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-6 mb-3">
        <label class="form-label" for="carry_over_days">Carry-over days</label>
        <input id="carry_over_days" type="number" step="0.01" min="0" name="carry_over_days" value="{{ old('carry_over_days', $leavePolicy?->carry_over_days) }}"
               class="form-control @error('carry_over_days') is-invalid @enderror">
        @error('carry_over_days')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $leavePolicy?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
