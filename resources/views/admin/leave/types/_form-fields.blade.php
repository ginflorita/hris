@php($leaveType = $leaveType ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $leaveType?->company_id) === $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
    @error('company_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-12 col-md-8 mb-3">
        <label class="form-label" for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $leaveType?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="code">Code</label>
        <input id="code" type="text" name="code" value="{{ old('code', $leaveType?->code) }}"
               class="form-control @error('code') is-invalid @enderror" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="max_days_per_year">Max days per year</label>
    <input id="max_days_per_year" type="number" min="0" max="365" name="max_days_per_year" value="{{ old('max_days_per_year', $leaveType?->max_days_per_year) }}"
           class="form-control @error('max_days_per_year') is-invalid @enderror">
    <div class="form-text">Leave blank for no fixed cap.</div>
    @error('max_days_per_year')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_paid" value="1" id="is_paid"
               {{ old('is_paid', $leaveType?->is_paid ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_paid">Paid leave</label>
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="requires_approval" value="1" id="requires_approval"
               {{ old('requires_approval', $leaveType?->requires_approval ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="requires_approval">Requires approval</label>
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $leaveType?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
