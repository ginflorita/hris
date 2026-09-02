@php($candidate = $candidate ?? null)
@php($idPrefix = $candidate?->id ?? 'new')

<div class="mb-3">
    <label class="form-label" for="position_id_{{ $idPrefix }}">Position</label>
    <select id="position_id_{{ $idPrefix }}" name="position_id" class="form-select" required>
        <option value="">Select a position</option>
        @foreach ($positions as $position)
            <option value="{{ $position->id }}" {{ $candidate?->position_id === $position->id ? 'selected' : '' }}>{{ $position->title }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label" for="readiness_{{ $idPrefix }}">Readiness</label>
    <select id="readiness_{{ $idPrefix }}" name="readiness" class="form-select" required>
        @foreach (\App\Enums\SuccessionReadiness::cases() as $case)
            <option value="{{ $case->value }}" {{ $candidate?->readiness->value === $case->value ? 'selected' : '' }}>
                {{ $case->label() }}
            </option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label" for="notes_{{ $idPrefix }}">Notes</label>
    <textarea id="notes_{{ $idPrefix }}" name="notes" rows="2" class="form-control">{{ $candidate?->notes }}</textarea>
</div>
