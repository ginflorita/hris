@php($department = $department ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $department?->company_id) === $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
    @error('company_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="division_id">Division</label>
    <select id="division_id" name="division_id" class="form-select @error('division_id') is-invalid @enderror">
        <option value="">None</option>
        @foreach ($divisions as $division)
            <option value="{{ $division->id }}" {{ (int) old('division_id', $department?->division_id) === $division->id ? 'selected' : '' }}>
                {{ $division->name }} ({{ $division->company->name }})
            </option>
        @endforeach
    </select>
    <div class="form-text">Must belong to the selected company.</div>
    @error('division_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $department?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="code">Code</label>
        <input id="code" type="text" name="code" value="{{ old('code', $department?->code) }}"
               class="form-control @error('code') is-invalid @enderror" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $department?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
