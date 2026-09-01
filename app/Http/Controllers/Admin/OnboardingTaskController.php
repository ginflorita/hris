<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OnboardingTask;
use App\Models\OnboardingTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnboardingTaskController extends Controller
{
    public function store(Request $request, OnboardingTemplate $onboardingTemplate): RedirectResponse
    {
        $this->authorize('recruitment.manage');

        $onboardingTemplate->tasks()->create($this->validated($request));

        return back()->with('status', 'Task added.');
    }

    public function update(Request $request, OnboardingTemplate $onboardingTemplate, OnboardingTask $task): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_unless($task->onboarding_template_id === $onboardingTemplate->id, 404);

        $task->update($this->validated($request));

        return back()->with('status', 'Task updated.');
    }

    public function destroy(OnboardingTemplate $onboardingTemplate, OnboardingTask $task): RedirectResponse
    {
        $this->authorize('recruitment.manage');
        abort_unless($task->onboarding_template_id === $onboardingTemplate->id, 404);

        $task->delete();

        return back()->with('status', 'Task removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sequence' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['sequence'] ??= 0;

        return $validated;
    }
}
