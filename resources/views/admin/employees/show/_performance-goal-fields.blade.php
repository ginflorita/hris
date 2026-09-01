@php($goal = $goal ?? null)
@php($idPrefix = $goal?->id ?? 'new')

<div class="mb-3">
    <label class="form-label" for="performance_cycle_id_{{ $idPrefix }}">Cycle</label>
    <select id="performance_cycle_id_{{ $idPrefix }}" name="performance_cycle_id" class="form-select" required>
        <option value="">Select a cycle</option>
        @foreach ($performanceCycles as $cycle)
            <option value="{{ $cycle->id }}" {{ $goal?->performance_cycle_id === $cycle->id ? 'selected' : '' }}>{{ $cycle->name }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label" for="title_{{ $idPrefix }}">Title</label>
    <input type="text" id="title_{{ $idPrefix }}" name="title" class="form-control" value="{{ $goal?->title }}" required>
</div>

<div class="mb-3">
    <label class="form-label" for="description_{{ $idPrefix }}">Description</label>
    <textarea id="description_{{ $idPrefix }}" name="description" rows="2" class="form-control">{{ $goal?->description }}</textarea>
</div>

<div class="row">
    <div class="col-6 mb-3">
        <label class="form-label" for="target_date_{{ $idPrefix }}">Target Date</label>
        <input type="date" id="target_date_{{ $idPrefix }}" name="target_date" class="form-control" value="{{ $goal?->target_date?->format('Y-m-d') }}">
    </div>
    <div class="col-6 mb-3">
        <label class="form-label" for="weight_{{ $idPrefix }}">Weight (%)</label>
        <input type="number" id="weight_{{ $idPrefix }}" name="weight" min="1" max="100" class="form-control" value="{{ $goal?->weight }}">
    </div>
</div>

<div class="row">
    <div class="col-4 mb-3">
        <label class="form-label" for="target_value_{{ $idPrefix }}">Target Value</label>
        <input type="number" step="0.01" id="target_value_{{ $idPrefix }}" name="target_value" class="form-control" value="{{ $goal?->target_value }}">
    </div>
    <div class="col-4 mb-3">
        <label class="form-label" for="actual_value_{{ $idPrefix }}">Actual Value</label>
        <input type="number" step="0.01" id="actual_value_{{ $idPrefix }}" name="actual_value" class="form-control" value="{{ $goal?->actual_value }}">
    </div>
    <div class="col-4 mb-3">
        <label class="form-label" for="unit_{{ $idPrefix }}">Unit</label>
        <input type="text" id="unit_{{ $idPrefix }}" name="unit" class="form-control" value="{{ $goal?->unit }}" placeholder="e.g. %, units">
    </div>
</div>

@if ($goal)
    <div class="mb-3">
        <label class="form-label" for="status_{{ $idPrefix }}">Status</label>
        <select id="status_{{ $idPrefix }}" name="status" class="form-select">
            @foreach (\App\Enums\PerformanceGoalStatus::cases() as $case)
                <option value="{{ $case->value }}" {{ $goal->status->value === $case->value ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $case->value)) }}
                </option>
            @endforeach
        </select>
    </div>
@endif
