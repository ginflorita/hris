@php($salaryGrade = $salaryGrade ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $salaryGrade?->company_id) === $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
    @error('company_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="salary_structure_id">Salary structure</label>
    <select id="salary_structure_id" name="salary_structure_id" class="form-select @error('salary_structure_id') is-invalid @enderror" required>
        <option value="">Select a structure</option>
        @foreach ($salaryStructures as $structure)
            <option value="{{ $structure->id }}" {{ (int) old('salary_structure_id', $salaryGrade?->salary_structure_id) === $structure->id ? 'selected' : '' }}>
                {{ $structure->name }} ({{ $structure->company->name }})
            </option>
        @endforeach
    </select>
    <div class="form-text">Must belong to the selected company.</div>
    @error('salary_structure_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-12 col-md-8 mb-3">
        <label class="form-label" for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $salaryGrade?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="code">Code</label>
        <input id="code" type="text" name="code" value="{{ old('code', $salaryGrade?->code) }}"
               class="form-control @error('code') is-invalid @enderror" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-4 mb-3">
        <label class="form-label" for="min_salary">Min salary</label>
        <input id="min_salary" type="number" step="0.01" min="0" name="min_salary" value="{{ old('min_salary', $salaryGrade?->min_salary) }}"
               class="form-control @error('min_salary') is-invalid @enderror" required>
        @error('min_salary')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-4 mb-3">
        <label class="form-label" for="mid_salary">Mid salary</label>
        <input id="mid_salary" type="number" step="0.01" min="0" name="mid_salary" value="{{ old('mid_salary', $salaryGrade?->mid_salary) }}"
               class="form-control @error('mid_salary') is-invalid @enderror">
        @error('mid_salary')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-4 mb-3">
        <label class="form-label" for="max_salary">Max salary</label>
        <input id="max_salary" type="number" step="0.01" min="0" name="max_salary" value="{{ old('max_salary', $salaryGrade?->max_salary) }}"
               class="form-control @error('max_salary') is-invalid @enderror" required>
        @error('max_salary')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $salaryGrade?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
