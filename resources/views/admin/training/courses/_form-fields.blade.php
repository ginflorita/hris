@php($course = $course ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $course?->company_id) === $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
    @error('company_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="training_provider_id">Provider</label>
    <select id="training_provider_id" name="training_provider_id" class="form-select @error('training_provider_id') is-invalid @enderror">
        <option value="">None</option>
        @foreach ($providers as $provider)
            <option value="{{ $provider->id }}" {{ (int) old('training_provider_id', $course?->training_provider_id) === $provider->id ? 'selected' : '' }}>
                {{ $provider->name }} ({{ $provider->company->name }})
            </option>
        @endforeach
    </select>
    <div class="form-text">Must belong to the same company selected above.</div>
    @error('training_provider_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="name">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $course?->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="description">Description</label>
    <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $course?->description) }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="duration_hours">Duration (hours)</label>
    <input id="duration_hours" type="number" min="1" name="duration_hours" value="{{ old('duration_hours', $course?->duration_hours) }}"
           class="form-control @error('duration_hours') is-invalid @enderror">
    @error('duration_hours')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $course?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
