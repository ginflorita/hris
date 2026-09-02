{{-- Shared by the Competency and Skill add/edit modals -- identical shape aside
     from which catalog backs the select, so one partial serves both. --}}
@php($rating = $rating ?? null)
{{-- $kind is folded into the id prefix, not just the rating id, because both
     the Add Competency and Add Skill modals are present in the DOM at once
     with $rating null -- without it both "new" forms would share ids like
     "assessed_at_new", which (besides being invalid HTML) let Chromium's
     form-autofill carry values from one modal's fields into the other's. --}}
@php($idPrefix = ($rating?->id ?? 'new').'-'.$kind)
@php($fieldName = "{$kind}_id")

<div class="mb-3">
    <label class="form-label" for="{{ $fieldName }}_{{ $idPrefix }}">{{ ucfirst($kind) }}</label>
    <select id="{{ $fieldName }}_{{ $idPrefix }}" name="{{ $fieldName }}" class="form-select" required>
        <option value="">Select a {{ $kind }}</option>
        @foreach ($catalog as $option)
            <option value="{{ $option->id }}" {{ $rating?->{$fieldName} === $option->id ? 'selected' : '' }}>{{ $option->name }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label" for="proficiency_level_{{ $idPrefix }}">Proficiency Level</label>
    <select id="proficiency_level_{{ $idPrefix }}" name="proficiency_level" class="form-select" required>
        <option value="">Select a level</option>
        @foreach (\App\Enums\ProficiencyLevel::cases() as $case)
            <option value="{{ $case->value }}" {{ $rating?->proficiency_level->value === $case->value ? 'selected' : '' }}>{{ ucfirst($case->value) }}</option>
        @endforeach
    </select>
</div>

<div class="row">
    <div class="col-6 mb-3">
        <label class="form-label" for="assessed_at_{{ $idPrefix }}">Assessed On</label>
        <input type="date" id="assessed_at_{{ $idPrefix }}" name="assessed_at" class="form-control" value="{{ $rating?->assessed_at?->format('Y-m-d') }}">
    </div>
    <div class="col-6 mb-3">
        <label class="form-label" for="assessed_by_{{ $idPrefix }}">Assessed By</label>
        <select id="assessed_by_{{ $idPrefix }}" name="assessed_by" class="form-select">
            <option value="">Unspecified</option>
            @foreach ($companyEmployees as $companyEmployee)
                <option value="{{ $companyEmployee->id }}" {{ $rating?->assessed_by === $companyEmployee->id ? 'selected' : '' }}>{{ $companyEmployee->full_name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="notes_{{ $idPrefix }}">Notes</label>
    <textarea id="notes_{{ $idPrefix }}" name="notes" rows="2" class="form-control">{{ $rating?->notes }}</textarea>
</div>
