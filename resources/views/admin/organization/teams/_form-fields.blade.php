@php($team = $team ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $team?->company_id) === $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
    @error('company_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="department_id">Department</label>
    <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror">
        <option value="">None</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}" {{ (int) old('department_id', $team?->department_id) === $department->id ? 'selected' : '' }}>
                {{ $department->name }} ({{ $department->company->name }})
            </option>
        @endforeach
    </select>
    <div class="form-text">Must belong to the selected company.</div>
    @error('department_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="section_id">Section</label>
    <select id="section_id" name="section_id" class="form-select @error('section_id') is-invalid @enderror">
        <option value="">None</option>
        @foreach ($sections as $section)
            <option value="{{ $section->id }}" {{ (int) old('section_id', $team?->section_id) === $section->id ? 'selected' : '' }}>
                {{ $section->name }} ({{ $section->company->name }})
            </option>
        @endforeach
    </select>
    <div class="form-text">Must belong to the selected company.</div>
    @error('section_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $team?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="code">Code</label>
        <input id="code" type="text" name="code" value="{{ old('code', $team?->code) }}"
               class="form-control @error('code') is-invalid @enderror" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $team?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
