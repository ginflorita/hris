@php($jobGrade = $jobGrade ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $jobGrade?->company_id) === $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
    @error('company_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-12 col-md-5 mb-3">
        <label class="form-label" for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $jobGrade?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="code">Code</label>
        <input id="code" type="text" name="code" value="{{ old('code', $jobGrade?->code) }}"
               class="form-control @error('code') is-invalid @enderror" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-3 mb-3">
        <label class="form-label" for="rank">Rank</label>
        <input id="rank" type="number" name="rank" min="0" max="65535" value="{{ old('rank', $jobGrade?->rank ?? 0) }}"
               class="form-control @error('rank') is-invalid @enderror" required>
        <div class="form-text">Lower ranks first.</div>
        @error('rank')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $jobGrade?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
