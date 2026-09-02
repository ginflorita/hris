@php($review = $review ?? null)
@php($idPrefix = $review?->id ?? 'new')

<div class="mb-3">
    <label class="form-label" for="performance_cycle_id_review_{{ $idPrefix }}">Cycle</label>
    <select id="performance_cycle_id_review_{{ $idPrefix }}" name="performance_cycle_id" class="form-select" required>
        <option value="">Select a cycle</option>
        @foreach ($performanceCycles as $cycle)
            <option value="{{ $cycle->id }}" {{ $review?->performance_cycle_id === $cycle->id ? 'selected' : '' }}>{{ $cycle->name }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label" for="type_{{ $idPrefix }}">Type</label>
    <select id="type_{{ $idPrefix }}" name="type" class="form-select" required>
        <option value="">Select a type</option>
        @foreach (\App\Enums\PerformanceReviewType::cases() as $case)
            <option value="{{ $case->value }}" {{ $review?->type->value === $case->value ? 'selected' : '' }}>{{ ucfirst($case->value) }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label" for="reviewer_id_{{ $idPrefix }}">Reviewer</label>
    <select id="reviewer_id_{{ $idPrefix }}" name="reviewer_id" class="form-select" required>
        <option value="">Select a reviewer</option>
        @foreach ($companyEmployees as $companyEmployee)
            <option value="{{ $companyEmployee->id }}" {{ $review?->reviewer_id === $companyEmployee->id ? 'selected' : '' }}>
                {{ $companyEmployee->full_name }}{{ $companyEmployee->id === $employee->id ? ' (self)' : '' }}
            </option>
        @endforeach
    </select>
    <div class="form-text">For a self-review, choose {{ $employee->full_name }} as the reviewer.</div>
</div>

<div class="mb-3">
    <label class="form-label" for="rating_{{ $idPrefix }}">Rating</label>
    <select id="rating_{{ $idPrefix }}" name="rating" class="form-select">
        <option value="">Not rated</option>
        @foreach (range(1, 5) as $n)
            <option value="{{ $n }}" {{ $review?->rating === $n ? 'selected' : '' }}>{{ $n }}</option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label class="form-label" for="comments_{{ $idPrefix }}">Comments</label>
    <textarea id="comments_{{ $idPrefix }}" name="comments" rows="3" class="form-control">{{ $review?->comments }}</textarea>
</div>
