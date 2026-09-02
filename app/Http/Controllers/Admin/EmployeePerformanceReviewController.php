<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PerformanceReviewStatus;
use App\Enums\PerformanceReviewType;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PerformanceReview;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Self-review, manager review, and peer review (blueprint §22) are one
 * controller/table differentiated by `type` -- gated by performance.manage,
 * same as PerformanceGoal since Performance has its own seeded permission
 * group. Status only moves Draft -> Submitted -> Acknowledged through
 * submit()/acknowledge(), the same lifecycle-guard shape PerformanceCycle
 * uses; update()/destroy() are only allowed while Draft, so a review
 * already shown to (or acknowledged by) the employee can't silently
 * change after the fact.
 */
class EmployeePerformanceReviewController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('performance.manage');

        $validated = $this->validated($request, $employee);
        $this->assertReviewerMatchesType($employee, (int) $validated['reviewer_id'], PerformanceReviewType::from($validated['type']));

        $employee->performanceReviews()->create([
            ...$validated,
            'status' => PerformanceReviewStatus::Draft,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Review added.');
    }

    public function update(Request $request, Employee $employee, PerformanceReview $review): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($review->employee_id === $employee->id, 404);
        abort_unless($review->status === PerformanceReviewStatus::Draft, 422, 'Only a draft review can be edited.');

        $validated = $this->validated($request, $employee, $review);
        $this->assertReviewerMatchesType($employee, (int) $validated['reviewer_id'], PerformanceReviewType::from($validated['type']));

        $review->update($validated);

        return back()->with('status', 'Review updated.');
    }

    public function submit(Employee $employee, PerformanceReview $review): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($review->employee_id === $employee->id, 404);
        abort_unless($review->status === PerformanceReviewStatus::Draft, 422, 'Only a draft review can be submitted.');
        abort_if($review->rating === null, 422, 'Set a rating before submitting.');

        $review->update(['status' => PerformanceReviewStatus::Submitted, 'submitted_at' => now()]);

        return back()->with('status', 'Review submitted.');
    }

    public function acknowledge(Employee $employee, PerformanceReview $review): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($review->employee_id === $employee->id, 404);
        abort_unless($review->status === PerformanceReviewStatus::Submitted, 422, 'Only a submitted review can be acknowledged.');

        $review->update(['status' => PerformanceReviewStatus::Acknowledged, 'acknowledged_at' => now()]);

        return back()->with('status', 'Review acknowledged.');
    }

    public function destroy(Employee $employee, PerformanceReview $review): RedirectResponse
    {
        $this->authorize('performance.manage');
        abort_unless($review->employee_id === $employee->id, 404);
        abort_unless($review->status === PerformanceReviewStatus::Draft, 422, 'Only a draft review can be removed.');

        $review->delete();

        return back()->with('status', 'Review removed.');
    }

    /**
     * A self-review must name the employee as their own reviewer; a
     * manager/peer review must not -- same "checked in the controller,
     * not a validation rule, since it needs the route's $employee" shape
     * Employment's manager_id self-check uses.
     */
    private function assertReviewerMatchesType(Employee $employee, int $reviewerId, PerformanceReviewType $type): void
    {
        if ($type === PerformanceReviewType::Self) {
            abort_unless($reviewerId === $employee->id, 422, 'A self-review must name the employee as their own reviewer.');
        } else {
            abort_if($reviewerId === $employee->id, 422, 'A manager or peer review cannot name the employee as their own reviewer.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Employee $employee, ?PerformanceReview $review = null): array
    {
        return $request->validate([
            'performance_cycle_id' => ['required', Rule::exists('performance_cycles', 'id')->where('company_id', $employee->company_id)],
            'reviewer_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $employee->company_id)],
            'type' => ['required', Rule::enum(PerformanceReviewType::class), $this->oneSelfOrManagerReviewPerCycleRule($request, $employee, $review)],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comments' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    /**
     * At most one Self and one Manager review per employee per cycle;
     * Peer reviews are unrestricted (several peers can weigh in). App-level
     * rule on top of the FK, same shape as PayrollPeriod's overlap check
     * and Application::hasPendingJobOffer() -- doesn't fit a DB unique
     * index since it only applies to two of the three types.
     */
    private function oneSelfOrManagerReviewPerCycleRule(Request $request, Employee $employee, ?PerformanceReview $review): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($request, $employee, $review) {
            if (! in_array($value, [PerformanceReviewType::Self->value, PerformanceReviewType::Manager->value], true)) {
                return;
            }

            $exists = PerformanceReview::where('employee_id', $employee->id)
                ->where('performance_cycle_id', $request->input('performance_cycle_id'))
                ->where('type', $value)
                ->when($review, fn ($q) => $q->whereKeyNot($review->id))
                ->exists();

            if ($exists) {
                $fail("This employee already has a {$value} review for this cycle.");
            }
        };
    }
}
