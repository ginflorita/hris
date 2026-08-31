<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssessmentType;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Assessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssessmentController extends Controller
{
    public function store(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('recruitment.manage');

        $validated = $request->validate([
            'type' => ['required', Rule::enum(AssessmentType::class)],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $application->assessments()->create($validated);

        return back()->with('status', 'Assessment assigned.');
    }

    /**
     * Recording a result stamps assessed_by as whoever submits it --
     * there's no separate "assign a grader" step in v1.
     */
    public function update(Request $request, Application $application, Assessment $assessment): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_unless($assessment->application_id === $application->id, 404);

        $validated = $request->validate([
            'completed_at' => ['nullable', 'date'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'passed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $assessment->update([
            ...$validated,
            'assessed_by' => $validated['completed_at'] ?? null ? $request->user()->id : $assessment->assessed_by,
        ]);

        return back()->with('status', 'Assessment updated.');
    }
}
