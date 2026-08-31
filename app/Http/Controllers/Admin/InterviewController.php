<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InterviewRecommendation;
use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Interview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * One primary interviewer per row -- see the interviews migration and
 * CLAUDE.md "Recruitment" for why there's no separate interviewers/
 * interview_evaluations table. update() handles both rescheduling and
 * recording the outcome through one form, since a single interview row
 * only ever needs one edit surface, not a separate "schedule" vs
 * "record result" action.
 */
class InterviewController extends Controller
{
    public function store(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('recruitment.manage');

        $validated = $request->validate([
            'interviewer_id' => ['nullable', 'exists:employees,id'],
            'type' => ['required', Rule::enum(InterviewType::class)],
            'scheduled_at' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $application->interviews()->create([
            ...$validated,
            'status' => InterviewStatus::Scheduled,
        ]);

        return back()->with('status', 'Interview scheduled.');
    }

    public function update(Request $request, Application $application, Interview $interview): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_unless($interview->application_id === $application->id, 404);

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(InterviewStatus::class)],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'recommendation' => ['nullable', Rule::enum(InterviewRecommendation::class)],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $interview->update($validated);

        return back()->with('status', 'Interview updated.');
    }
}
