@php($taxTable = $taxTable ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $taxTable?->company_id) === $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
    @error('company_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="name">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $taxTable?->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-6 mb-3">
        <label class="form-label" for="effective_from">Effective from</label>
        <input id="effective_from" type="date" name="effective_from" value="{{ old('effective_from', $taxTable?->effective_from?->format('Y-m-d')) }}"
               class="form-control @error('effective_from') is-invalid @enderror" required>
        @error('effective_from')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-6 mb-3">
        <label class="form-label" for="effective_to">Effective to</label>
        <input id="effective_to" type="date" name="effective_to" value="{{ old('effective_to', $taxTable?->effective_to?->format('Y-m-d')) }}"
               class="form-control @error('effective_to') is-invalid @enderror">
        <div class="form-text">Leave blank while this table is the current one.</div>
        @error('effective_to')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $taxTable?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
