<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkflowApproverType;
use App\Http\Controllers\Controller;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkflowStepController extends Controller
{
    public function store(Request $request, WorkflowDefinition $workflowDefinition): RedirectResponse
    {
        $this->authorize('workflow.manage');

        $workflowDefinition->steps()->create($this->validated($request, $workflowDefinition));

        return back()->with('status', 'Step added.');
    }

    public function update(Request $request, WorkflowDefinition $workflowDefinition, WorkflowStep $step): RedirectResponse
    {
        $this->authorize('workflow.manage');
        abort_unless($step->workflow_definition_id === $workflowDefinition->id, 404);

        $step->update($this->validated($request, $workflowDefinition, $step));

        return back()->with('status', 'Step updated.');
    }

    public function destroy(WorkflowDefinition $workflowDefinition, WorkflowStep $step): RedirectResponse
    {
        $this->authorize('workflow.manage');
        abort_unless($step->workflow_definition_id === $workflowDefinition->id, 404);

        $step->delete();

        return back()->with('status', 'Step removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, WorkflowDefinition $workflowDefinition, ?WorkflowStep $step = null): array
    {
        return $request->validate([
            'step_order' => [
                'required', 'integer', 'min:1',
                Rule::unique('workflow_steps')->where('workflow_definition_id', $workflowDefinition->id)->ignore($step?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'approver_type' => ['required', Rule::enum(WorkflowApproverType::class)],
            'required_permission' => [
                'required_if:approver_type,'.WorkflowApproverType::Permission->value,
                'nullable', 'string', 'exists:permissions,name',
            ],
        ]);
    }
}
