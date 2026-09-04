<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Workflow\Services\WorkflowEngine;
use App\Enums\WorkflowInstanceStepStatus;
use App\Http\Controllers\Controller;
use App\Models\WorkflowInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The generic side of the engine: an approvals inbox and the
 * approve/reject action, usable against *any* workflow instance
 * regardless of what its subject is. "Who can act" is resolved
 * dynamically per step (`WorkflowEngine::canAct()`), not a static
 * permission -- there's no single `$this->authorize(...)` call that
 * would mean the right thing here, unlike every other admin
 * controller in this app.
 */
class WorkflowInstanceController extends Controller
{
    public function __construct(private readonly WorkflowEngine $engine) {}

    public function index(Request $request): View
    {
        $pendingSteps = $this->engine->pendingStepsFor($request->user());

        return view('admin.workflow.instances.index', ['pendingSteps' => $pendingSteps]);
    }

    public function show(Request $request, WorkflowInstance $workflowInstance): View
    {
        $workflowInstance->load(['workflowDefinition', 'subject', 'instanceSteps.actedBy', 'initiatedBy']);
        $currentStep = $workflowInstance->currentInstanceStep();
        $user = $request->user();

        $canAct = $currentStep !== null && $this->engine->canAct($currentStep, $user);
        $hasActed = $workflowInstance->instanceSteps->contains(fn ($step) => $step->acted_by === $user->id);

        abort_unless($canAct || $hasActed, 403);

        return view('admin.workflow.instances.show', [
            'workflowInstance' => $workflowInstance,
            'currentStep' => $currentStep,
            'canAct' => $canAct,
        ]);
    }

    public function approve(Request $request, WorkflowInstance $workflowInstance): RedirectResponse
    {
        return $this->act($request, $workflowInstance, WorkflowInstanceStepStatus::Approved);
    }

    public function reject(Request $request, WorkflowInstance $workflowInstance): RedirectResponse
    {
        $request->validate(['comments' => ['required', 'string']]);

        return $this->act($request, $workflowInstance, WorkflowInstanceStepStatus::Rejected);
    }

    private function act(Request $request, WorkflowInstance $workflowInstance, WorkflowInstanceStepStatus $decision): RedirectResponse
    {
        $step = $workflowInstance->currentInstanceStep();
        abort_if($step === null, 422, 'This request has no step awaiting action.');

        $this->engine->act($workflowInstance, $step, $request->user(), $decision, $request->input('comments'));

        return redirect()->route('admin.workflow.instances.index')->with('status', 'Decision recorded.');
    }
}
