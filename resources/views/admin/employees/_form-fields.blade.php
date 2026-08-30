@php($employee = $employee ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $employee?->company_id) === $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
    @error('company_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="employee_number">Employee Number</label>
    <input id="employee_number" type="text" name="employee_number" value="{{ old('employee_number', $employee?->employee_number) }}"
           class="form-control @error('employee_number') is-invalid @enderror" required>
    @error('employee_number')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="first_name">First Name</label>
        <input id="first_name" type="text" name="first_name" value="{{ old('first_name', $employee?->first_name) }}"
               class="form-control @error('first_name') is-invalid @enderror" required>
        @error('first_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="middle_name">Middle Name</label>
        <input id="middle_name" type="text" name="middle_name" value="{{ old('middle_name', $employee?->middle_name) }}"
               class="form-control @error('middle_name') is-invalid @enderror">
        @error('middle_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="last_name">Last Name</label>
        <input id="last_name" type="text" name="last_name" value="{{ old('last_name', $employee?->last_name) }}"
               class="form-control @error('last_name') is-invalid @enderror" required>
        @error('last_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="suffix">Suffix</label>
        <input id="suffix" type="text" name="suffix" value="{{ old('suffix', $employee?->suffix) }}"
               class="form-control @error('suffix') is-invalid @enderror">
        @error('suffix')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-8 mb-3">
        <label class="form-label" for="preferred_name">Preferred Name</label>
        <input id="preferred_name" type="text" name="preferred_name" value="{{ old('preferred_name', $employee?->preferred_name) }}"
               class="form-control @error('preferred_name') is-invalid @enderror">
        @error('preferred_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="birth_date">Birth Date</label>
        <input id="birth_date" type="date" name="birth_date"
               value="{{ old('birth_date', $employee?->birth_date?->format('Y-m-d')) }}"
               class="form-control @error('birth_date') is-invalid @enderror">
        @error('birth_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="gender">Gender</label>
        <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
            <option value="">Select</option>
            @foreach (\App\Enums\Gender::cases() as $case)
                <option value="{{ $case->value }}" {{ old('gender', $employee?->gender?->value) === $case->value ? 'selected' : '' }}>
                    {{ ucwords(str_replace('_', ' ', $case->value)) }}
                </option>
            @endforeach
        </select>
        @error('gender')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="civil_status">Civil Status</label>
        <select id="civil_status" name="civil_status" class="form-select @error('civil_status') is-invalid @enderror">
            <option value="">Select</option>
            @foreach (\App\Enums\CivilStatus::cases() as $case)
                <option value="{{ $case->value }}" {{ old('civil_status', $employee?->civil_status?->value) === $case->value ? 'selected' : '' }}>
                    {{ ucfirst($case->value) }}
                </option>
            @endforeach
        </select>
        @error('civil_status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="nationality">Nationality</label>
        <input id="nationality" type="text" name="nationality" value="{{ old('nationality', $employee?->nationality) }}"
               class="form-control @error('nationality') is-invalid @enderror">
        @error('nationality')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $employee?->email) }}"
               class="form-control @error('email') is-invalid @enderror">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="mobile">Mobile</label>
        <input id="mobile" type="text" name="mobile" value="{{ old('mobile', $employee?->mobile) }}"
               class="form-control @error('mobile') is-invalid @enderror">
        @error('mobile')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
