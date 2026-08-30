@php($schedule = $schedule ?? null)

<div class="mb-3">
    <label class="form-label" for="company_id">Company</label>
    <select id="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
        <option value="">Select a company</option>
        @foreach ($companies as $company)
            <option value="{{ $company->id }}" {{ (int) old('company_id', $schedule?->company_id) === $company->id ? 'selected' : '' }}>
                {{ $company->name }}
            </option>
        @endforeach
    </select>
    @error('company_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label" for="shift_id">Shift</label>
    <select id="shift_id" name="shift_id" class="form-select @error('shift_id') is-invalid @enderror">
        <option value="">None</option>
        @foreach ($shifts as $shift)
            <option value="{{ $shift->id }}" {{ (int) old('shift_id', $schedule?->shift_id) === $shift->id ? 'selected' : '' }}>
                {{ $shift->name }} ({{ $shift->company->name }})
            </option>
        @endforeach
    </select>
    <div class="form-text">Must belong to the selected company.</div>
    @error('shift_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $schedule?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="code">Code</label>
        <input id="code" type="text" name="code" value="{{ old('code', $schedule?->code) }}"
               class="form-control @error('code') is-invalid @enderror" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="type">Type</label>
    <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
        @foreach (\App\Enums\ScheduleType::cases() as $case)
            <option value="{{ $case->value }}" {{ old('type', $schedule?->type?->value) === $case->value ? 'selected' : '' }}>
                {{ ucfirst($case->value) }}
            </option>
        @endforeach
    </select>
    @error('type')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label d-block">Rest days</label>
    @php($restDays = old('rest_days', $schedule?->rest_days ?? [0, 6]))
    @foreach ($weekdays as $index => $label)
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="rest_days[]" value="{{ $index }}" id="rest_day_{{ $index }}"
                   {{ in_array($index, $restDays ?? []) ? 'checked' : '' }}>
            <label class="form-check-label" for="rest_day_{{ $index }}">{{ $label }}</label>
        </div>
    @endforeach
    @error('rest_days')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
               {{ old('is_active', $schedule?->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>
