@php($plan = $plan ?? null)
@php($idPrefix = $plan?->id ?? 'new')

<div class="mb-3">
    <label class="form-label" for="target_position_id_{{ $idPrefix }}">Target Position</label>
    <select id="target_position_id_{{ $idPrefix }}" name="target_position_id" class="form-select">
        <option value="">Unspecified</option>
        @foreach ($positions as $position)
            <option value="{{ $position->id }}" {{ $plan?->target_position_id === $position->id ? 'selected' : '' }}>{{ $position->title }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label" for="target_date_{{ $idPrefix }}">Target Date</label>
    <input type="date" id="target_date_{{ $idPrefix }}" name="target_date" class="form-control" value="{{ $plan?->target_date?->format('Y-m-d') }}">
</div>

<div class="mb-3">
    <label class="form-label" for="development_actions_{{ $idPrefix }}">Development Actions</label>
    <textarea id="development_actions_{{ $idPrefix }}" name="development_actions" rows="3" class="form-control" required>{{ $plan?->development_actions }}</textarea>
</div>
