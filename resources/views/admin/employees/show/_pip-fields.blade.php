@php($plan = $plan ?? null)
@php($idPrefix = $plan?->id ?? 'new')

<div class="mb-3">
    <label class="form-label" for="initiated_by_{{ $idPrefix }}">Initiated By</label>
    <select id="initiated_by_{{ $idPrefix }}" name="initiated_by" class="form-select" required>
        <option value="">Select an employee</option>
        @foreach ($companyEmployees as $companyEmployee)
            <option value="{{ $companyEmployee->id }}" {{ $plan?->initiated_by === $companyEmployee->id ? 'selected' : '' }}>
                {{ $companyEmployee->full_name }}
            </option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label" for="performance_review_id_{{ $idPrefix }}">Triggering Review (optional)</label>
    <select id="performance_review_id_{{ $idPrefix }}" name="performance_review_id" class="form-select">
        <option value="">None</option>
        @foreach ($employee->performanceReviews as $review)
            <option value="{{ $review->id }}" {{ $plan?->performance_review_id === $review->id ? 'selected' : '' }}>
                {{ ucfirst($review->type->value) }} review &middot; {{ $review->performanceCycle->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label" for="reason_{{ $idPrefix }}">Reason</label>
    <textarea id="reason_{{ $idPrefix }}" name="reason" rows="2" class="form-control" required>{{ $plan?->reason }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label" for="goals_{{ $idPrefix }}">Improvement Goals</label>
    <textarea id="goals_{{ $idPrefix }}" name="goals" rows="3" class="form-control" required>{{ $plan?->goals }}</textarea>
</div>

<div class="row">
    <div class="col-6 mb-3">
        <label class="form-label" for="start_date_{{ $idPrefix }}">Start Date</label>
        <input type="date" id="start_date_{{ $idPrefix }}" name="start_date" class="form-control" value="{{ $plan?->start_date?->format('Y-m-d') }}" required>
    </div>
    <div class="col-6 mb-3">
        <label class="form-label" for="end_date_{{ $idPrefix }}">End Date</label>
        <input type="date" id="end_date_{{ $idPrefix }}" name="end_date" class="form-control" value="{{ $plan?->end_date?->format('Y-m-d') }}" required>
    </div>
</div>
