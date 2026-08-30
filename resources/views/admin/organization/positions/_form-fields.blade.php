@php($position = $position ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $position?->company_id) === $company->id ? 'selected' : '' }}>
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
            <option value="{{ $department->id }}" {{ (int) old('department_id', $position?->department_id) === $department->id ? 'selected' : '' }}>
                {{ $department->name }} ({{ $department->company->name }})
            </option>
        @endforeach
    </select>
    <div class="form-text">Must belong to the selected company.</div>
    @error('department_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="job_level_id">Job Level</label>
        <select id="job_level_id" name="job_level_id" class="form-select @error('job_level_id') is-invalid @enderror">
            <option value="">None</option>
            @foreach ($jobLevels as $jobLevel)
                <option value="{{ $jobLevel->id }}" {{ (int) old('job_level_id', $position?->job_level_id) === $jobLevel->id ? 'selected' : '' }}>
                    {{ $jobLevel->name }} ({{ $jobLevel->company->name }})
                </option>
            @endforeach
        </select>
        <div class="form-text">Must belong to the selected company.</div>
        @error('job_level_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="job_grade_id">Job Grade</label>
        <select id="job_grade_id" name="job_grade_id" class="form-select @error('job_grade_id') is-invalid @enderror">
            <option value="">None</option>
            @foreach ($jobGrades as $jobGrade)
                <option value="{{ $jobGrade->id }}" {{ (int) old('job_grade_id', $position?->job_grade_id) === $jobGrade->id ? 'selected' : '' }}>
                    {{ $jobGrade->name }} ({{ $jobGrade->company->name }})
                </option>
            @endforeach
        </select>
        <div class="form-text">Must belong to the selected company.</div>
        @error('job_grade_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-8 mb-3">
        <label class="form-label" for="title">Title</label>
        <input id="title" type="text" name="title" value="{{ old('title', $position?->title) }}"
               class="form-control @error('title') is-invalid @enderror" required>
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="code">Code</label>
        <input id="code" type="text" name="code" value="{{ old('code', $position?->code) }}"
               class="form-control @error('code') is-invalid @enderror" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="description">Description</label>
    <textarea id="description" name="description" rows="3"
              class="form-control @error('description') is-invalid @enderror">{{ old('description', $position?->description) }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $position?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
