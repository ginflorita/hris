<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TrainingEnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\TrainingCourse;
use App\Models\TrainingEnrollment;
use App\Models\TrainingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A session's roster, managed from TrainingSessionController::show().
 * update() is the one combined "record the outcome" action (status plus
 * certificate fields together) rather than a separate attendance step
 * and a separate certificate step -- the same one-action preference
 * Assessment's completed_at/score/passed/notes update uses. Guarded to
 * only fire once, from Enrolled, the same "a decision can't be
 * re-applied by resubmitting" rule AttendanceCorrectionRequest/CoeRequest
 * already use.
 */
class TrainingEnrollmentController extends Controller
{
    public function store(Request $request, TrainingCourse $course, TrainingSession $session): RedirectResponse
    {
        $this->authorize('training.manage');
        abort_unless($session->training_course_id === $course->id, 404);

        if ($session->capacity !== null && $session->occupiedSeats() >= $session->capacity) {
            return back()->withErrors(['employee_id' => 'This session is at capacity.']);
        }

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $session->company_id),
                Rule::unique('training_enrollments')->where('training_session_id', $session->id),
            ],
        ]);

        $session->enrollments()->create([
            'employee_id' => $validated['employee_id'],
            'status' => TrainingEnrollmentStatus::Enrolled,
            'enrolled_at' => now(),
        ]);

        return back()->with('status', 'Employee enrolled.');
    }

    public function update(Request $request, TrainingCourse $course, TrainingSession $session, TrainingEnrollment $enrollment): RedirectResponse
    {
        $this->authorize('training.manage');
        abort_unless($session->training_course_id === $course->id, 404);
        abort_unless($enrollment->training_session_id === $session->id, 404);
        abort_unless($enrollment->status === TrainingEnrollmentStatus::Enrolled, 422, 'This enrollment has already been decided.');

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                TrainingEnrollmentStatus::Completed->value,
                TrainingEnrollmentStatus::Cancelled->value,
                TrainingEnrollmentStatus::NoShow->value,
            ])],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'certificate_issued_at' => ['nullable', 'date'],
            'certificate_expires_at' => ['nullable', 'date', 'after:certificate_issued_at'],
        ]);

        if ($validated['status'] !== TrainingEnrollmentStatus::Completed->value) {
            $validated['certificate_number'] = null;
            $validated['certificate_issued_at'] = null;
            $validated['certificate_expires_at'] = null;
        }

        $enrollment->update($validated);

        return back()->with('status', 'Enrollment updated.');
    }

    public function destroy(TrainingCourse $course, TrainingSession $session, TrainingEnrollment $enrollment): RedirectResponse
    {
        $this->authorize('training.manage');
        abort_unless($session->training_course_id === $course->id, 404);
        abort_unless($enrollment->training_session_id === $session->id, 404);
        abort_unless($enrollment->status === TrainingEnrollmentStatus::Enrolled, 422, 'Only an active enrollment can be removed.');

        $enrollment->delete();

        return back()->with('status', 'Enrollment removed.');
    }
}
