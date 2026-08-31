@php($applicant = $applicant ?? null)

<div class="row">
    <div class="col-6 mb-3">
        <label class="form-label" for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $applicant?->first_name) }}" required>
        @error('first_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-6 mb-3">
        <label class="form-label" for="last_name">Last Name</label>
        <input type="text" id="last_name" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $applicant?->last_name) }}" required>
        @error('last_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-6 mb-3">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $applicant?->email) }}" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-6 mb-3">
        <label class="form-label" for="phone">Phone</label>
        <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $applicant?->phone) }}">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="source">Source</label>
    <select id="source" name="source" class="form-select @error('source') is-invalid @enderror" required>
        @foreach (\App\Enums\ApplicantSource::cases() as $case)
            <option value="{{ $case->value }}" {{ old('source', $applicant?->source?->value) === $case->value ? 'selected' : '' }}>
                {{ $case->label() }}
            </option>
        @endforeach
    </select>
    @error('source')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="resume">Resume {{ $applicant?->resume_path ? '(replace)' : '' }}</label>
    <input type="file" id="resume" name="resume" class="form-control @error('resume') is-invalid @enderror" accept=".pdf,.doc,.docx">
    @if ($applicant?->resume_path)
        <div class="form-text">Current: {{ $applicant->resume_original_filename }}</div>
    @endif
    @error('resume')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="notes">Notes</label>
    <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $applicant?->notes) }}</textarea>
    @error('notes')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
