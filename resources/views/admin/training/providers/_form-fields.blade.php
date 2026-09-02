@php($provider = $provider ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $provider?->company_id) === $company->id ? 'selected' : '' }}>
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
    <input id="name" type="text" name="name" value="{{ old('name', $provider?->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="contact_name">Contact Name</label>
        <input id="contact_name" type="text" name="contact_name" value="{{ old('contact_name', $provider?->contact_name) }}"
               class="form-control @error('contact_name') is-invalid @enderror">
        @error('contact_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="contact_email">Contact Email</label>
        <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', $provider?->contact_email) }}"
               class="form-control @error('contact_email') is-invalid @enderror">
        @error('contact_email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="contact_phone">Contact Phone</label>
        <input id="contact_phone" type="text" name="contact_phone" value="{{ old('contact_phone', $provider?->contact_phone) }}"
               class="form-control @error('contact_phone') is-invalid @enderror">
        @error('contact_phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $provider?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
