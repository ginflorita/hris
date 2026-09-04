@php($workflowDefinition = $workflowDefinition ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $workflowDefinition?->company_id) === $company->id ? 'selected' : '' }}>
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
    <input id="name" type="text" name="name" value="{{ old('name', $workflowDefinition?->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="process_type">Process</label>
    <select id="process_type" name="process_type" class="form-select @error('process_type') is-invalid @enderror" required>
        @foreach (\App\Enums\WorkflowProcessType::cases() as $case)
            <option value="{{ $case->value }}" {{ old('process_type', $workflowDefinition?->process_type?->value) === $case->value ? 'selected' : '' }}>
                {{ $case->label() }}
            </option>
        @endforeach
    </select>
    <div class="form-text">Which real request this workflow governs. Only one active workflow per process per company is used.</div>
    @error('process_type')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="description">Description</label>
    <textarea id="description" name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $workflowDefinition?->description) }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $workflowDefinition?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
