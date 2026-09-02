@php($competency = $competency ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $competency?->company_id) === $company->id ? 'selected' : '' }}>
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
    <input id="name" type="text" name="name" value="{{ old('name', $competency?->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="description">Description</label>
    <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $competency?->description) }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $competency?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
