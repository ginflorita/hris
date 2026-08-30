@php($shift = $shift ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $shift?->company_id) === $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
    @error('company_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-12 col-md-8 mb-3">
        <label class="form-label" for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $shift?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="code">Code</label>
        <input id="code" type="text" name="code" value="{{ old('code', $shift?->code) }}"
               class="form-control @error('code') is-invalid @enderror" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-6 col-md-3 mb-3">
        <label class="form-label" for="start_time">Start</label>
        <input id="start_time" type="time" name="start_time" value="{{ old('start_time', $shift?->start_time) }}"
               class="form-control @error('start_time') is-invalid @enderror" required>
        @error('start_time')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-6 col-md-3 mb-3">
        <label class="form-label" for="end_time">End</label>
        <input id="end_time" type="time" name="end_time" value="{{ old('end_time', $shift?->end_time) }}"
               class="form-control @error('end_time') is-invalid @enderror" required>
        @error('end_time')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-6 col-md-3 mb-3">
        <label class="form-label" for="break_start">Break start</label>
        <input id="break_start" type="time" name="break_start" value="{{ old('break_start', $shift?->break_start) }}"
               class="form-control @error('break_start') is-invalid @enderror">
        @error('break_start')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-6 col-md-3 mb-3">
        <label class="form-label" for="break_end">Break end</label>
        <input id="break_end" type="time" name="break_end" value="{{ old('break_end', $shift?->break_end) }}"
               class="form-control @error('break_end') is-invalid @enderror">
        @error('break_end')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-4 mb-3">
        <label class="form-label" for="grace_minutes">Grace period (minutes)</label>
        <input id="grace_minutes" type="number" min="0" max="120" name="grace_minutes" value="{{ old('grace_minutes', $shift?->grace_minutes ?? 0) }}"
               class="form-control @error('grace_minutes') is-invalid @enderror" required>
        @error('grace_minutes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-8 mb-3 d-flex align-items-end">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_night_shift" value="1" id="is_night_shift"
                   {{ old('is_night_shift', $shift?->is_night_shift) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_night_shift">Night shift</label>
        </div>
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $shift?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
