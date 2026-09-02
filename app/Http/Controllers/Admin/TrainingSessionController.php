<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TrainingSessionStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\TrainingCourse;
use App\Models\TrainingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Add/edit/complete/cancel/remove are managed entirely from
 * TrainingCourseController::show() via modals, the same "child entity,
 * no index/create/edit views of its own" shape ContributionRateBracket
 * uses for its parent rate table. update()/destroy() are only allowed
 * while Scheduled -- a Completed/Cancelled session is a settled record,
 * the same "don't silently rewrite it" guard PerformanceReview's
 * Draft-only edit uses.
 *
 * show() is new in 15f: once a session has its own roster of
 * TrainingEnrollments to manage, it earns a dedicated page -- the same
 * "enough of its own related data to deserve a page" call 14c made
 * moving Interview/Assessment onto Admin\ApplicationController::show().
 */
class TrainingSessionController extends Controller
{
    public function show(TrainingCourse $course, TrainingSession $session): View
    {
        $this->authorize('training.view');
        abort_unless($session->training_course_id === $course->id, 404);

        return view('admin.training.courses.sessions.show', [
            'course' => $course,
            'session' => $session,
            'enrollments' => $session->enrollments()->with('employee')->orderByDesc('enrolled_at')->get(),
            'companyEmployees' => Employee::where('company_id', $course->company_id)->orderBy('last_name')->get(),
        ]);
    }

    public function store(Request $request, TrainingCourse $course): RedirectResponse
    {
        $this->authorize('training.manage');

        $course->sessions()->create([
            ...$this->validated($request),
            'company_id' => $course->company_id,
            'status' => TrainingSessionStatus::Scheduled,
        ]);

        return back()->with('status', 'Training session added.');
    }

    public function update(Request $request, TrainingCourse $course, TrainingSession $session): RedirectResponse
    {
        $this->authorize('training.manage');
        abort_unless($session->training_course_id === $course->id, 404);
        abort_unless($session->status === TrainingSessionStatus::Scheduled, 422, 'Only a scheduled session can be edited.');

        $session->update($this->validated($request));

        return back()->with('status', 'Training session updated.');
    }

    public function complete(TrainingCourse $course, TrainingSession $session): RedirectResponse
    {
        $this->authorize('training.manage');
        abort_unless($session->training_course_id === $course->id, 404);
        abort_unless($session->status === TrainingSessionStatus::Scheduled, 422, 'Only a scheduled session can be completed.');

        $session->update(['status' => TrainingSessionStatus::Completed]);

        return back()->with('status', 'Training session marked completed.');
    }

    public function cancel(TrainingCourse $course, TrainingSession $session): RedirectResponse
    {
        $this->authorize('training.manage');
        abort_unless($session->training_course_id === $course->id, 404);
        abort_unless($session->status === TrainingSessionStatus::Scheduled, 422, 'Only a scheduled session can be cancelled.');

        $session->update(['status' => TrainingSessionStatus::Cancelled]);

        return back()->with('status', 'Training session cancelled.');
    }

    public function destroy(TrainingCourse $course, TrainingSession $session): RedirectResponse
    {
        $this->authorize('training.manage');
        abort_unless($session->training_course_id === $course->id, 404);
        abort_unless($session->status === TrainingSessionStatus::Scheduled, 422, 'Only a scheduled session can be removed.');

        $session->delete();

        return back()->with('status', 'Training session removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'cost' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
