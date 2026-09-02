@php($session = $session ?? null)
@php($idPrefix = $session?->id ?? 'new')

<div class="row">
    <div class="col-6 mb-3">
        <label class="form-label" for="start_date_{{ $idPrefix }}">Start Date</label>
        <input type="date" id="start_date_{{ $idPrefix }}" name="start_date" class="form-control" value="{{ $session?->start_date?->format('Y-m-d') }}" required>
    </div>
    <div class="col-6 mb-3">
        <label class="form-label" for="end_date_{{ $idPrefix }}">End Date</label>
        <input type="date" id="end_date_{{ $idPrefix }}" name="end_date" class="form-control" value="{{ $session?->end_date?->format('Y-m-d') }}" required>
    </div>
</div>

<div class="mb-3">
    <label class="form-label" for="location_{{ $idPrefix }}">Location</label>
    <input type="text" id="location_{{ $idPrefix }}" name="location" class="form-control" value="{{ $session?->location }}">
</div>

<div class="row">
    <div class="col-6 mb-3">
        <label class="form-label" for="capacity_{{ $idPrefix }}">Capacity</label>
        <input type="number" min="1" id="capacity_{{ $idPrefix }}" name="capacity" class="form-control" value="{{ $session?->capacity }}">
    </div>
    <div class="col-6 mb-3">
        <label class="form-label" for="cost_{{ $idPrefix }}">Cost</label>
        <input type="number" step="0.01" min="0" id="cost_{{ $idPrefix }}" name="cost" class="form-control" value="{{ $session?->cost }}">
    </div>
</div>
