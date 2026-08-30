@php($company = $company ?? null)

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $company?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="code">Code</label>
        <input id="code" type="text" name="code" value="{{ old('code', $company?->code) }}"
               class="form-control @error('code') is-invalid @enderror" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="registration_no">Registration no.</label>
        <input id="registration_no" type="text" name="registration_no" value="{{ old('registration_no', $company?->registration_no) }}"
               class="form-control @error('registration_no') is-invalid @enderror">
        @error('registration_no')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="tax_id">Tax ID</label>
        <input id="tax_id" type="text" name="tax_id" value="{{ old('tax_id', $company?->tax_id) }}"
               class="form-control @error('tax_id') is-invalid @enderror">
        @error('tax_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 mb-3">
        <label class="form-label" for="address">Address</label>
        <textarea id="address" name="address" rows="2"
                  class="form-control @error('address') is-invalid @enderror">{{ old('address', $company?->address) }}</textarea>
        @error('address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="phone">Phone</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone', $company?->phone) }}"
               class="form-control @error('phone') is-invalid @enderror">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $company?->email) }}"
               class="form-control @error('email') is-invalid @enderror">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                   {{ old('is_active', $company?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>
