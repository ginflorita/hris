<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\OnboardingTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Assigning a checklist to an employee is a per-employee record, the
 * same shape as Employment/CompensationItem -- gated by employees.update
 * like those, not recruitment.manage (which gates the template
 * definitions themselves, in OnboardingTemplateController). Onboarding
 * happens post-hire, once a real Employee row already exists, so this
 * belongs with the rest of Employee's per-employee sub-resources rather
 * than the recruitment pipeline.
 */
class EmployeeOnboardingController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update');

        $hasIncomplete = $employee->onboardings()
            ->whereHas('tasks', fn ($q) => $q->where('is_completed', false))
            ->exists();
        abort_if($hasIncomplete, 422, 'This employee already has an onboarding checklist in progress.');

        $validated = $request->validate([
            'onboarding_template_id' => [
                'required',
                Rule::exists('onboarding_templates', 'id')->where('company_id', $employee->company_id)->where('is_active', true),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $template = OnboardingTemplate::with('tasks')->findOrFail($validated['onboarding_template_id']);

        DB::transaction(function () use ($employee, $template, $validated, $request) {
            $onboarding = $employee->onboardings()->create([
                'onboarding_template_id' => $template->id,
                'assigned_by' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($template->tasks as $task) {
                $onboarding->tasks()->create([
                    'title' => $task->title,
                    'description' => $task->description,
                    'sequence' => $task->sequence,
                ]);
            }
        });

        return back()->with('status', 'Onboarding checklist assigned.');
    }
}
